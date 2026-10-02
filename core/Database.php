<?php
/**
 * ============================================================
 *  SPORT SHOP – Kết nối Database (Singleton PDO)
 *  File: core/Database.php
 * ============================================================
 */

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Constructor: khởi tạo kết nối PDO tới MySQL/XAMPP
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ];
        if (DB_SSL_CA !== '' && is_file(DB_SSL_CA)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
        }

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Hiện thông báo lỗi thân thiện
            die($this->renderError($e->getMessage()));
        }
    }

    /**
     * Lấy instance duy nhất (Singleton)
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Lấy đối tượng PDO trực tiếp
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * Thực thi query có tham số (prepared statement)
     * @param  string $sql    Câu lệnh SQL với placeholder ?
     * @param  array  $params Mảng tham số
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Lấy 1 bản ghi (SELECT ... LIMIT 1)
     */
    public function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Lấy nhiều bản ghi
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Thực thi INSERT/UPDATE/DELETE, trả về số hàng bị ảnh hưởng
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Lấy ID của bản ghi vừa INSERT
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Bắt đầu transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Render trang lỗi kết nối DB
     */
    private function renderError(string $msg): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="vi">
        <head>
          <meta charset="UTF-8">
          <title>Lỗi Kết Nối Database</title>
          <style>
            body { font-family: Arial, sans-serif; background: #f1f5f9;
                   display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .box { background: #fff; border-left: 5px solid #F44336;
                   padding: 32px 40px; border-radius: 12px;
                   box-shadow: 0 8px 30px rgba(0,0,0,.12); max-width: 560px; }
            h2   { color: #F44336; margin-bottom: 12px; }
            pre  { background: #f8fafc; padding: 14px; border-radius: 8px;
                   font-size: 13px; overflow-x: auto; }
            p    { color: #475569; font-size: 14px; margin-top: 16px; }
          </style>
        </head>
        <body>
          <div class="box">
            <h2>⚠️ Không thể kết nối Database</h2>
            <pre>{$msg}</pre>
            <p>📌 Vui lòng kiểm tra:</p>
            <ul style="color:#475569;font-size:14px;line-height:2">
              <li>XAMPP đã bật chưa? (Apache + MySQL)</li>
              <li>Tên database trong <code>config/config.php</code> có đúng không?</li>
              <li>User/password MySQL có chính xác không?</li>
            </ul>
          </div>
        </body>
        </html>
        HTML;
    }

    // Ngăn clone và unserialize
    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize singleton"); }
}
