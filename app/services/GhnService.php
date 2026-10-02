<?php

class GhnService
{
    public function getProvinces(): array
    {
        return $this->request('GET', '/master-data/province');
    }

    public function getDistricts(int $provinceId): array
    {
        return $this->request('POST', '/master-data/district', [
            'province_id' => $provinceId,
        ]);
    }

    public function getWards(int $districtId): array
    {
        return $this->request('POST', '/master-data/ward', [
            'district_id' => $districtId,
        ]);
    }

    public function calculateFee(int $toDistrictId, string $toWardCode, int $weight): array
    {
        return $this->request('POST', '/v2/shipping-order/fee', array_merge([
            'from_district_id' => GHN_FROM_DISTRICT_ID,
            'to_district_id' => $toDistrictId,
            'to_ward_code' => $toWardCode,
            'insurance_value' => 0,
            'service_type_id' => 2,
        ], $this->packageParameters($weight)));
    }

    public function createOrder(array $orderData): array
    {
        return $this->request('POST', '/v2/shipping-order/create', array_merge([
            'shop_id' => GHN_SHOP_ID,
            'payment_type_id' => 2,
            'required_note' => 'KHONGCHOXEMHANG',
            'service_type_id' => 2,
        ], $orderData));
    }

    public function createCodShipment(string $clientCode, array $receiver, array $lines, int $weight): array
    {
        $phone = (string) ($receiver['phone'] ?? '');
        if (str_starts_with($phone, '+84')) {
            $phone = '0' . substr($phone, 3);
        }
        $address = trim((string) preg_replace('/\s+/u', ' ', (string) ($receiver['address'] ?? '')));
        $payload = [
            'client_order_code' => $clientCode,
            'note' => $clientCode,
            'content' => implode(', ', array_map(
                static fn (array $line): string => (string) ($line['name'] ?? 'Sản phẩm'),
                $lines
            )),
            'to_name' => trim((string) ($receiver['name'] ?? '')) !== '' ? trim((string) $receiver['name']) : 'Khách hàng',
            'to_phone' => $phone,
            'to_address' => $address,
            'to_ward_code' => (string) ($receiver['ward_code'] ?? ''),
            'to_district_id' => (int) ($receiver['district_id'] ?? 0),
            'cod_amount' => (int) ($receiver['cod_amount'] ?? 0),
            'weight' => max($weight, 200),
            'length' => 15,
            'width' => 15,
            'height' => 10,
            'items' => $lines,
        ];
        $response = $this->createOrder($payload);
        if (($response['code_message'] ?? '') === 'WAREHOUSE_NOT_FOUND') {
            $response = $this->createOrder($payload);
        }

        return $response;
    }

    public static function waybillError(array $response): string
    {
        $raw = trim((string) ($response['code_message_value'] ?? $response['message'] ?? ''));
        if ($raw === '' || stripos($raw, 'kho') !== false || stripos($raw, 'Connection') !== false || stripos($raw, 'route-by-address') !== false) {
            return 'Máy chủ kho thử nghiệm của GHN không phản hồi. Hãy thử lại sau vài phút.';
        }

        return $raw;
    }

    public function getOrderDetail(string $orderCode): array
    {
        return $this->request('POST', '/v2/shipping-order/detail', [
            'order_code' => $orderCode,
        ]);
    }

    public function interpretStatus(string $ghnStatus): array
    {
        $ghnStatus = strtolower(trim($ghnStatus));
        $orderStatus = null;
        if (in_array($ghnStatus, ['cancel', 'cancelled'], true)) {
            $orderStatus = 'Da_Huy';
        } elseif ($ghnStatus === 'delivered') {
            $orderStatus = 'Da_Giao';
        } elseif ($ghnStatus === 'returned') {
            $orderStatus = 'Da_Tra_Hang';
        } elseif (in_array($ghnStatus, [
            'picked', 'storing', 'transporting', 'sorting', 'delivering',
            'money_collect_delivering', 'delivery_fail', 'waiting_to_return',
            'return', 'return_transporting', 'return_sorting', 'returning', 'return_fail',
        ], true)) {
            $orderStatus = 'Dang_Giao';
        }

        return [
            'shipping_status' => $ghnStatus !== '' ? $ghnStatus : 'unknown',
            'order_status' => $orderStatus,
        ];
    }

    public static function statusLabel(string $status): string
    {
        $labels = [
            'ready_to_pick' => 'Chờ lấy hàng',
            'picking' => 'Đang lấy hàng',
            'money_collect_picking' => 'Đang lấy hàng',
            'picked' => 'Đã lấy hàng',
            'storing' => 'Đã nhập kho',
            'transporting' => 'Đang vận chuyển',
            'sorting' => 'Đang phân loại',
            'delivering' => 'Đang giao',
            'money_collect_delivering' => 'Đang giao',
            'delivered' => 'Đã giao',
            'delivery_fail' => 'Giao thất bại',
            'waiting_to_return' => 'Chờ trả hàng',
            'return' => 'Đang trả hàng',
            'return_transporting' => 'Đang trả hàng',
            'return_sorting' => 'Đang trả hàng',
            'returning' => 'Đang trả hàng',
            'return_fail' => 'Trả hàng thất bại',
            'returned' => 'Đã trả',
            'cancel' => 'Hủy',
            'cancelled' => 'Hủy',
            'pending' => 'Chờ tạo vận đơn',
            'not_shipped' => 'Chưa giao',
        ];

        $status = strtolower(trim($status));
        return $labels[$status] ?? ($status !== '' ? $status : 'Chưa giao');
    }

    public function cancelOrder(array $orderCodes): array
    {
        return $this->request('POST', '/v2/switch-status/cancel', [
            'order_codes' => array_values($orderCodes),
            'shop_id' => GHN_SHOP_ID,
        ]);
    }

    public function packageParameters(int $weight): array
    {
        return [
            'weight' => max($weight, 200),
            'length' => 15,
            'width' => 15,
            'height' => 10,
        ];
    }

    public function cartWeight(array $cartItems): int
    {
        $weight = 0;
        foreach ($cartItems as $item) {
            $weight += GHN_DEFAULT_WEIGHT * (int) ($item['quantity'] ?? 1);
        }

        return $weight;
    }

    private function request(string $method, string $uri, array $payload = []): array
    {
        if (GHN_TOKEN === '' || GHN_SHOP_ID <= 0) {
            return ['code' => -1, 'message' => 'Chưa cấu hình GHN.'];
        }

        $url = rtrim(GHN_BASE_URL, '/') . $uri;
        $headers = [
            'Token: ' . GHN_TOKEN,
            'ShopId: ' . GHN_SHOP_ID,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
        if ($payload !== []) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno !== 0 || !is_string($raw) || $raw === '') {
            return ['code' => -1, 'message' => 'Không kết nối được GHN.'];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['code' => -1, 'message' => 'GHN trả về dữ liệu không hợp lệ.'];
    }
}
