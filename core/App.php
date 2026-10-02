<?php
/**
 * ============================================================
 *  SPORT SHOP – Router (App)
 *  File: core/App.php
 *
 *  Parse URL → load Controller → gọi Method
 *  URL format: /SportShop/{controller}/{method}/{param}
 * ============================================================
 */

class App
{
    private string $controller = 'HomeController';
    private string $method     = 'index';
    private array  $params     = [];

    public function __construct()
    {
        $this->parseUrl();
        $this->run();
    }

    /**
     * Parse URL thành controller / method / params
     */
    private function parseUrl(): void
    {
        // Lấy URL từ $_GET['url'] (được rewrite bởi .htaccess)
        $url = $_GET['url'] ?? '';

        // Loại bỏ trailing slash và sanitize
        $url = rtrim(filter_var(trim($url), FILTER_SANITIZE_URL), '/');

        if (empty($url)) {
            return; // dùng mặc định: HomeController::index
        }

        // Tách thành mảng segments
        $segments = explode('/', $url);

        // ── Segment 0: Controller ──────────────────────────
        $controllerName = $this->toPascalCase($segments[0]) . 'Controller';
        $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            $this->controller = $controllerName;
            array_shift($segments);
        }
        // Nếu không tìm thấy controller → giữ nguyên HomeController

        // ── Segment 1: Method ──────────────────────────────
        if (!empty($segments[0])) {
            $this->method = $this->toCamelCase($segments[0]);
            array_shift($segments);
        }

        // ── Phần còn lại: Params ───────────────────────────
        $this->params = $segments ? array_values($segments) : [];
    }

    /**
     * Load controller file và gọi method
     */
    private function run(): void
    {
        $controllerFile = APP_PATH . '/controllers/' . $this->controller . '.php';

        // Kiểm tra file controller tồn tại
        if (!file_exists($controllerFile)) {
            $this->show404("Controller không tồn tại: {$this->controller}");
            return;
        }

        // Load file
        require_once $controllerFile;

        // Kiểm tra class tồn tại
        if (!class_exists($this->controller)) {
            $this->show404("Class không tồn tại: {$this->controller}");
            return;
        }

        // Khởi tạo controller
        $controllerObj = new $this->controller();

        // Kiểm tra method tồn tại
        if (!method_exists($controllerObj, $this->method)) {
            $this->show404("Method không tồn tại: {$this->controller}::{$this->method}");
            return;
        }

        // Gọi method với params
        call_user_func_array([$controllerObj, $this->method], $this->params);
    }

    /**
     * Hiện trang 404 thân thiện
     */
    private function show404(string $debug = ''): void
    {
        http_response_code(404);
        $debugInfo = (APP_ENV === 'development' && $debug)
            ? "<p style='font-size:13px;color:#94a3b8;margin-top:12px'>Debug: {$debug}</p>"
            : '';

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="vi">
        <head>
          <meta charset="UTF-8">
          <title>404 – Không tìm thấy | Sport Shop</title>
          <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap">
          <style>
            *{box-sizing:border-box;margin:0;padding:0}
            body{font-family:'Inter',sans-serif;background:#f1f5f9;
                 display:flex;align-items:center;justify-content:center;min-height:100vh}
            .box{text-align:center;padding:40px}
            h1{font-size:90px;font-weight:800;color:#2196F3;line-height:1}
            h2{font-size:20px;color:#1e293b;margin:12px 0 8px}
            p{color:#64748b;font-size:15px;margin-bottom:28px}
            a{background:#2196F3;color:#fff;padding:12px 28px;
              border-radius:50px;text-decoration:none;font-weight:600;
              transition:background .3s}
            a:hover{background:#1565C0}
          </style>
        </head>
        <body>
          <div class="box">
            <h1>404</h1>
            <h2>Trang không tìm thấy!</h2>
            <p>Trang bạn tìm kiếm không tồn tại hoặc đã bị xóa.</p>
            {$debugInfo}
            <a href="javascript:history.back()">← Quay lại</a>
            &nbsp;
            <a href="/SportShop/">🏠 Trang chủ</a>
          </div>
        </body>
        </html>
        HTML;
        exit;
    }

    /**
     * "auth" → "Auth",  "home-page" → "HomePage"
     */
    private function toPascalCase(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($str))));
    }

    /**
     * "my-method" → "myMethod"
     */
    private function toCamelCase(string $str): string
    {
        return lcfirst($this->toPascalCase($str));
    }
}
