<?php
/**
 * ============================================================
 *  SPORT SHOP – Cấu hình ứng dụng & Database
 *  File: config/config.php
 * ============================================================
 */

$env = static function (string $key, string $fallback = ''): string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $fallback : $value;
};

// ── Cấu hình Database ───────────────────────────────────────
// Máy local giữ XAMPP. Render/Aiven điền DB_* qua biến môi trường.
define('DB_HOST',     $env('DB_HOST', 'localhost'));
define('DB_PORT',     $env('DB_PORT', '3306'));
define('DB_NAME',     $env('DB_NAME', $env('DB_DATABASE', 'sports')));
define('DB_USER',     $env('DB_USER', $env('DB_USERNAME', 'root')));
define('DB_PASS',     $env('DB_PASS', $env('DB_PASSWORD', '')));
define('DB_CHARSET',  'utf8mb4');
define('DB_SSL_CA',   $env('MYSQL_ATTR_SSL_CA', ''));

// ── Cấu hình ứng dụng ────────────────────────────────────────
define('APP_NAME',    'TrendStyle Fashion');
define('APP_VERSION', '1.0.0');

$mailLocal = __DIR__ . '/mail.local.php';
$mailConfig = is_file($mailLocal) ? require $mailLocal : [];
define('MAIL_HOST', (string) ($mailConfig['host'] ?? $env('MAIL_HOST', 'smtp.gmail.com')));
define('MAIL_PORT', (int) ($mailConfig['port'] ?? $env('MAIL_PORT', '587')));
define('MAIL_USERNAME', (string) ($mailConfig['username'] ?? $env('MAIL_USERNAME', '')));
define('MAIL_PASSWORD', (string) ($mailConfig['password'] ?? $env('MAIL_PASSWORD', '')));
define('MAIL_FROM_ADDRESS', (string) ($mailConfig['from_address'] ?? $env('MAIL_FROM_ADDRESS', $env('MAIL_USERNAME', ''))));
define('MAIL_FROM_NAME', (string) ($mailConfig['from_name'] ?? $env('MAIL_FROM_NAME', APP_NAME)));
unset($mailLocal, $mailConfig);

$ghnLocal = __DIR__ . '/ghn.local.php';
$ghnConfig = is_file($ghnLocal) ? require $ghnLocal : [];
define('GHN_BASE_URL', (string) ($ghnConfig['base_url'] ?? $env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api')));
define('GHN_TOKEN', (string) ($ghnConfig['token'] ?? $env('GHN_TOKEN', '')));
define('GHN_SHOP_ID', (int) ($ghnConfig['shop_id'] ?? $env('GHN_SHOP_ID', '0')));
define('GHN_FROM_DISTRICT_ID', (int) ($ghnConfig['from_district_id'] ?? $env('GHN_FROM_DISTRICT_ID', '0')));
define('GHN_DEFAULT_WEIGHT', (int) ($ghnConfig['default_weight'] ?? $env('GHN_DEFAULT_WEIGHT', '200')));
unset($ghnLocal, $ghnConfig);

// Local: http://localhost/SportShop. Render: địa chỉ HTTPS của Web Service.
define('BASE_URL', rtrim($env('APP_URL', 'http://localhost/SportShop'), '/'));

$momoLocal = __DIR__ . '/momo.local.php';
$momoConfig = is_file($momoLocal) ? require $momoLocal : [];
define('MOMO_ENDPOINT', (string) ($momoConfig['endpoint'] ?? $env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create')));
define('MOMO_PARTNER_CODE', (string) ($momoConfig['partner_code'] ?? $env('MOMO_PARTNER_CODE', '')));
define('MOMO_ACCESS_KEY', (string) ($momoConfig['access_key'] ?? $env('MOMO_ACCESS_KEY', '')));
define('MOMO_SECRET_KEY', (string) ($momoConfig['secret_key'] ?? $env('MOMO_SECRET_KEY', '')));
define('MOMO_REDIRECT_URL', $env('MOMO_REDIRECT_URL', BASE_URL . '/checkout/return'));
define('MOMO_IPN_URL', $env('MOMO_IPN_URL', BASE_URL . '/checkout/ipn'));
$momoVerify = $momoConfig['verify_ssl'] ?? $env('MOMO_VERIFY_SSL', 'false');
define('MOMO_VERIFY_SSL', filter_var($momoVerify, FILTER_VALIDATE_BOOLEAN));
unset($momoLocal, $momoConfig);

// ── VNPay Sandbox (ưu tiên biến môi trường) ─────────────────
$vnpayEnv = static function (string $key, string $fallback): string {
    $value = getenv($key);
    return $value !== false && $value !== '' ? $value : $fallback;
};
define('VNPAY_ENDPOINT', $vnpayEnv(
    'VNPAY_ENDPOINT',
    'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'
));
define('VNPAY_TMN_CODE', $vnpayEnv('VNPAY_TMN_CODE', 'VNPAY_TMN_CODE'));
define('VNPAY_HASH_SECRET', $vnpayEnv('VNPAY_HASH_SECRET', 'VNPAY_HASH_SECRET'));
define('VNPAY_RETURN_URL', $vnpayEnv('VNPAY_RETURN_URL', BASE_URL . '/checkout/vnpayReturn'));
unset($vnpayEnv);

$geminiLocal = __DIR__ . '/gemini.local.php';
$geminiConfig = is_file($geminiLocal) ? require $geminiLocal : [];
define('GEMINI_API_KEY', (string) ($geminiConfig['api_key'] ?? $env('GEMINI_API_KEY', '')));
unset($geminiLocal, $geminiConfig);

// Đường dẫn thư mục gốc tuyệt đối
define('ROOT_PATH',   dirname(__DIR__));
define('APP_PATH',    ROOT_PATH . '/app');
define('CORE_PATH',   ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// ── Cài đặt Session ─────────────────────────────────────────
define('SESSION_LIFETIME', 3600); // 1 giờ

// ── Cài đặt phân trang ──────────────────────────────────────
define('PRODUCTS_PER_PAGE', 12);

// ── Môi trường (development | production) ───────────────────
define('APP_ENV', $env('APP_ENV', 'development'));
unset($env);

// Hiện lỗi khi development
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

// ── Khởi động session ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => str_starts_with(BASE_URL, 'https://'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
