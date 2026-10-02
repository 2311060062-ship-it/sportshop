<?php

class MomoPaymentRejectedException extends RuntimeException
{
}

class MomoPaymentService
{
    public function isConfigured(): bool
    {
        return MOMO_PARTNER_CODE !== ''
            && MOMO_ACCESS_KEY !== ''
            && MOMO_SECRET_KEY !== '';
    }

    /**
     * Tạo giao dịch payWithATM theo chuỗi ký chính thức của MoMo v2.
     */
    public function createPayment(array $payment): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException(
                'Chưa cấu hình MOMO_PARTNER_CODE, MOMO_ACCESS_KEY và MOMO_SECRET_KEY.'
            );
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL chưa được bật.');
        }

        $request = [
            'partnerCode' => MOMO_PARTNER_CODE,
            'partnerName' => APP_NAME,
            'storeId' => APP_NAME,
            'requestId' => (string) $payment['request_id'],
            'amount' => (int) $payment['amount'],
            'orderId' => (string) $payment['provider_order_id'],
            'orderInfo' => (string) $payment['order_info'],
            'redirectUrl' => MOMO_REDIRECT_URL,
            'ipnUrl' => MOMO_IPN_URL,
            'lang' => 'vi',
            'extraData' => (string) ($payment['extra_data'] ?? ''),
            'requestType' => 'payWithATM',
        ];

        $rawSignature = 'accessKey=' . MOMO_ACCESS_KEY
            . '&amount=' . $request['amount']
            . '&extraData=' . $request['extraData']
            . '&ipnUrl=' . $request['ipnUrl']
            . '&orderId=' . $request['orderId']
            . '&orderInfo=' . $request['orderInfo']
            . '&partnerCode=' . $request['partnerCode']
            . '&redirectUrl=' . $request['redirectUrl']
            . '&requestId=' . $request['requestId']
            . '&requestType=' . $request['requestType'];
        $request['signature'] = hash_hmac('sha256', $rawSignature, MOMO_SECRET_KEY);

        $curl = curl_init(MOMO_ENDPOINT);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => MOMO_VERIFY_SSL,
            CURLOPT_SSL_VERIFYHOST => MOMO_VERIFY_SSL ? 2 : 0,
        ]);

        $body = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false || $curlError !== '') {
            throw new RuntimeException('Không thể kết nối MoMo: ' . $curlError);
        }

        $response = json_decode($body, true);
        if (!is_array($response)) {
            throw new RuntimeException('MoMo trả về dữ liệu không hợp lệ.');
        }
        if ($httpCode < 200 || $httpCode >= 300 || (int) ($response['resultCode'] ?? -1) !== 0) {
            throw new MomoPaymentRejectedException(
                (string) ($response['message'] ?? 'MoMo từ chối yêu cầu thanh toán.')
            );
        }
        if (empty($response['payUrl'])) {
            throw new RuntimeException('MoMo không trả về payUrl.');
        }

        return ['request' => $request, 'response' => $response];
    }

    /**
     * Callback/IPN dùng đúng tập field và thứ tự raw string MoMo công bố.
     */
    public function verifyCallback(array $payload): bool
    {
        $signedKeys = [
            'amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType',
            'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId',
        ];
        foreach (array_merge($signedKeys, ['signature']) as $key) {
            if (!array_key_exists($key, $payload) || is_array($payload[$key])) {
                return false;
            }
        }
        if ((string) ($payload['partnerCode'] ?? '') !== MOMO_PARTNER_CODE) {
            return false;
        }

        $parts = ['accessKey=' . MOMO_ACCESS_KEY];
        foreach ($signedKeys as $key) {
            $parts[] = $key . '=' . (string) $payload[$key];
        }
        $rawSignature = implode('&', $parts);
        $expected = hash_hmac('sha256', $rawSignature, MOMO_SECRET_KEY);

        return hash_equals($expected, (string) $payload['signature']);
    }
}
