<?php

require_once APP_PATH . '/models/AdminRevenueModel.php';

class AdminRevenueController extends Controller
{
    private AdminRevenueModel $revenueModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->revenueModel = new AdminRevenueModel();
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return;
        }

        $today = new DateTimeImmutable('today');
        $from = $this->parseDate(
            (string) ($_GET['from'] ?? ''),
            $today->modify('first day of this month')
        );
        $to = $this->parseDate((string) ($_GET['to'] ?? ''), $today);
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        if ($from < $to->modify('-365 days')) {
            $from = $to->modify('-365 days');
        }

        $fromSql = $from->format('Y-m-d');
        $toSql = $to->format('Y-m-d');
        $chartYear = (int) $to->format('Y');
        $daily = $this->fillDaily(
            $from,
            $to,
            $this->revenueModel->dailyRevenue($fromSql, $toSql)
        );
        $monthly = $this->fillPeriods(
            1,
            12,
            $this->revenueModel->monthlyRevenue($chartYear),
            static fn (int $month): string => 'Tháng ' . $month
        );
        $yearly = $this->fillPeriods(
            $chartYear - 4,
            $chartYear,
            $this->revenueModel->yearlyRevenue($chartYear - 4, $chartYear),
            static fn (int $year): string => (string) $year
        );

        $this->loadView('admin/revenue/index', [
            'pageTitle' => 'Quản lý doanh thu',
            'summary' => $this->revenueModel->summary($fromSql, $toSql),
            'from' => $fromSql,
            'to' => $toSql,
            'chartYear' => $chartYear,
            'chartData' => [
                'daily' => $daily,
                'monthly' => $monthly,
                'yearly' => $yearly,
            ],
            'authUser' => $this->getAdminUser(),
        ], false);
    }

    private function parseDate(string $value, DateTimeImmutable $fallback): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        $valid = $date instanceof DateTimeImmutable
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
        return $valid ? $date : $fallback;
    }

    private function fillDaily(DateTimeImmutable $from, DateTimeImmutable $to, array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['period']] = $row;
        }

        $labels = [];
        $revenue = [];
        $orders = [];
        for ($date = $from; $date <= $to; $date = $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $revenue[] = (int) ($indexed[$key]['revenue'] ?? 0);
            $orders[] = (int) ($indexed[$key]['order_count'] ?? 0);
        }
        return compact('labels', 'revenue', 'orders');
    }

    private function fillPeriods(int $start, int $end, array $rows, callable $label): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['period']] = $row;
        }

        $labels = [];
        $revenue = [];
        $orders = [];
        for ($period = $start; $period <= $end; $period++) {
            $labels[] = $label($period);
            $revenue[] = (int) ($indexed[$period]['revenue'] ?? 0);
            $orders[] = (int) ($indexed[$period]['order_count'] ?? 0);
        }
        return compact('labels', 'revenue', 'orders');
    }

    /**
     * GET /admin-revenue/export?from=...&to=...
     * Xuất báo cáo doanh thu ra file Excel (.csv UTF-8 BOM chuẩn tiếng Việt)
     */
    public function export(): void
    {
        $today = new DateTimeImmutable('today');
        $from = $this->parseDate(
            (string) ($_GET['from'] ?? ''),
            $today->modify('first day of this month')
        );
        $to = $this->parseDate((string) ($_GET['to'] ?? ''), $today);
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $fromSql = $from->format('Y-m-d');
        $toSql = $to->format('Y-m-d');
        $summary = $this->revenueModel->summary($fromSql, $toSql);
        $daily = $this->revenueModel->dailyRevenue($fromSql, $toSql);
        $orders = $this->revenueModel->getDetailedOrdersForExport($fromSql, $toSql);

        $filename = 'Bao_Cao_Doanh_Thu_SportShop_' . $from->format('d-m-Y') . '_den_' . $to->format('d-m-Y') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");

        // 1. TIÊU ĐỀ
        fputcsv($output, ['SPORT SHOP - BÁO CÁO DOANH THU KINH DOANH']);
        fputcsv($output, ['Khoảng thời gian:', $from->format('d/m/Y') . ' đến ' . $to->format('d/m/Y')]);
        fputcsv($output, ['Ngày xuất file:', date('d/m/Y H:i:s')]);
        fputcsv($output, []);

        // 2. SỐ LIỆU TỔNG QUAN
        fputcsv($output, ['--- TỔNG QUAN KINH DOANH ---']);
        fputcsv($output, ['Chỉ số', 'Giá trị']);
        fputcsv($output, ['Tổng doanh thu', number_format((int)($summary['revenue'] ?? 0), 0, ',', '.') . ' đ']);
        fputcsv($output, ['Đơn đã thanh toán trong kỳ', number_format((int)($summary['orders'] ?? 0), 0, ',', '.')]);
        fputcsv($output, ['Giá trị đơn trung bình', number_format((int)($summary['average_order'] ?? 0), 0, ',', '.') . ' đ']);
        fputcsv($output, ['Tổng khách hàng', number_format((int)($summary['users'] ?? 0), 0, ',', '.')]);
        fputcsv($output, ['Sản phẩm đang bán', number_format((int)($summary['products'] ?? 0), 0, ',', '.')]);
        fputcsv($output, []);

        // 3. DOANH THU THEO NGÀY
        fputcsv($output, ['--- DOANH THU THEO NGÀY ---']);
        fputcsv($output, ['STT', 'Ngày', 'Số đơn hàng', 'Doanh thu (VND)']);
        $stt = 1;
        foreach ($daily as $row) {
            fputcsv($output, [
                $stt++,
                date('d/m/Y', strtotime((string)$row['period'])),
                $row['order_count'],
                number_format((int)$row['revenue'], 0, ',', '.') . ' đ'
            ]);
        }
        fputcsv($output, []);

        // 4. CHI TIẾT ĐƠN HÀNG ĐÃ THANH TOÁN
        fputcsv($output, ['--- CHI TIẾT ĐƠN HÀNG ĐÃ THANH TOÁN TRONG KỲ ---']);
        fputcsv($output, [
            'STT',
            'Mã Đơn',
            'Ngày Đặt',
            'Khách Hàng',
            'Tài Khoản',
            'Số Điện Thoại',
            'Địa Chỉ Giao Hàng',
            'Cổng Thanh Toán',
            'Trạng Thái',
            'Tổng Tiền (VND)'
        ]);

        $sttOrder = 1;
        foreach ($orders as $order) {
            $providerName = match ((string)($order['payment_provider'] ?? '')) {
                'vnpay', 'VNPay', 'VNPayStyleDemo' => 'VNPAY QR',
                'momo', 'MoMo' => 'Ví MoMo',
                'cod', 'COD' => 'COD (Tiền mặt)',
                default => (string)($order['payment_provider'] ?? 'N/A')
            };

            fputcsv($output, [
                $sttOrder++,
                'DH-' . str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT),
                date('d/m/Y H:i', strtotime((string)$order['date'])),
                $order['customer_name'] ?: ($order['customer_username'] ?: 'Khách vãng lai'),
                $order['customer_username'] ?? '',
                $order['order_phone'] ?: ($order['customer_phone'] ?? ''),
                $order['order_address'] ?? '',
                $providerName,
                'Đã thanh toán',
                number_format((int)$order['total'], 0, ',', '.') . ' đ'
            ]);
        }

        fclose($output);
        exit;
    }
}
