<?php

class VnpayPaymentService
{
    public function isConfigured(): bool
    {
        return VNPAY_TMN_CODE !== 'VNPAY_TMN_CODE'
            && VNPAY_HASH_SECRET !== 'VNPAY_HASH_SECRET';
    }

    /**
     * Tạo URL thanh toán VNPay Pay 2.1.0, ký HMAC-SHA512.
     */
    public function createPaymentUrl(array $payment): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('VNPay sandbox chưa được cấu hình.');
        }

        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $createdAt = new DateTimeImmutable('now', $timezone);
        $expiresAt = $createdAt->modify('+15 minutes');
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => VNPAY_TMN_CODE,
            'vnp_Amount' => (string) ((int) $payment['amount'] * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => (string) $payment['provider_order_id'],
            'vnp_OrderInfo' => (string) $payment['order_info'],
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => VNPAY_RETURN_URL,
            'vnp_IpAddr' => (string) ($payment['ip_address'] ?? '127.0.0.1'),
            'vnp_CreateDate' => $createdAt->format('YmdHis'),
            'vnp_ExpireDate' => $expiresAt->format('YmdHis'),
        ];

        ksort($params);
        $hashData = $this->buildQuery($params);
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

        return VNPAY_ENDPOINT . '?' . $this->buildQuery($params);
    }

    /**
     * Xác minh dữ liệu VNPay trả về ở Return URL hoặc IPN.
     */
    public function verifyCallback(array $payload): bool
    {
        $receivedHash = (string) ($payload['vnp_SecureHash'] ?? '');
        if ($receivedHash === '') {
            return false;
        }

        unset($payload['vnp_SecureHash'], $payload['vnp_SecureHashType']);
        foreach ($payload as $key => $value) {
            if (!str_starts_with((string) $key, 'vnp_') || is_array($value)) {
                unset($payload[$key]);
            } else {
                $payload[$key] = (string) $value;
            }
        }
        ksort($payload);
        $expected = hash_hmac('sha512', $this->buildQuery($payload), VNPAY_HASH_SECRET);

        return hash_equals($expected, $receivedHash);
    }

    public function isSuccessful(array $payload): bool
    {
        return (string) ($payload['vnp_ResponseCode'] ?? '') === '00'
            && (string) ($payload['vnp_TransactionStatus'] ?? '') === '00';
    }

    private function buildQuery(array $params): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $parts[] = urlencode((string) $key) . '=' . urlencode((string) $value);
        }

        return implode('&', $parts);
    }
}
