<?php
/**
 * ============================================================
 *  SPORT SHOP – User Model
 *  File: app/models/UserModel.php
 *  Bảng: user
 * ============================================================
 */

class UserModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tìm user theo username
     */
    public function findByUsername(string $username): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM `user` WHERE username = ? LIMIT 1",
            [$username]
        );
    }

    /**
     * Tìm user theo email
     */
    public function findByEmail(string $email): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM `user` WHERE email = ? LIMIT 1",
            [$email]
        );
    }

    /**
     * Tìm user theo ID
     */
    public function findById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT id, username, full_name, email, phone, gender, avatar, roles, status_user
             FROM `user` WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Kiểm tra đăng nhập
     * @return array|false  Trả về thông tin user nếu hợp lệ, false nếu sai
     */
    public function login(string $username, string $password): array|false
    {
        $user = $this->findByUsername($username);

        if (!$user) {
            return false;
        }

        // Kiểm tra tài khoản có bị khóa không
        if ($user['status_user'] === 'Closed') {
            return false;
        }

        // Xác minh mật khẩu (password_verify cho bcrypt)
        if (!password_verify($password, $user['password'])) {
            return false;
        }

        return $user;
    }

    /**
     * Đăng ký tài khoản mới
     * @return int  ID của user vừa tạo
     */
    public function register(array $data): int
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $this->db->execute(
            "INSERT INTO `user`
                (username, password, email, full_name, phone, gender, roles, status_user, create_date, update_date)
             VALUES
                (?, ?, ?, ?, ?, ?, 'USER', 'Active', NOW(6), NOW(6))",
            [
                $data['username'],
                $hashedPassword,
                $data['email']     ?? null,
                $data['full_name'] ?? null,
                $data['phone']     ?? null,
                $data['gender']    ?? null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function verificationState(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT id, username, email, full_name, avatar, roles, status_user,
                    email_verified_at, email_otp_hash, email_otp_expires
             FROM `user` WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    public function activeEmailOtpHash(int $id): ?string
    {
        $row = $this->db->fetchOne(
            "SELECT email_otp_hash FROM `user`
             WHERE id = ? AND email_otp_hash IS NOT NULL AND email_otp_expires > NOW(6)
             LIMIT 1",
            [$id]
        );

        if (!$row || empty($row['email_otp_hash'])) {
            return null;
        }

        return (string) $row['email_otp_hash'];
    }

    public function saveEmailOtp(int $id, string $code, int $ttlSeconds = 600): void
    {
        $this->db->execute(
            "UPDATE `user`
             SET email_otp_hash = ?, email_otp_expires = DATE_ADD(NOW(6), INTERVAL ? SECOND), update_date = NOW(6)
             WHERE id = ?",
            [password_hash($code, PASSWORD_BCRYPT), $ttlSeconds, $id]
        );
    }

    public function markEmailVerified(int $id): void
    {
        $this->db->execute(
            "UPDATE `user`
             SET email_verified_at = NOW(6), email_otp_hash = NULL, email_otp_expires = NULL, update_date = NOW(6)
             WHERE id = ?",
            [$id]
        );
    }

    public function clearEmailOtp(int $id): void
    {
        $this->db->execute(
            "UPDATE `user` SET email_otp_hash = NULL, email_otp_expires = NULL, update_date = NOW(6) WHERE id = ?",
            [$id]
        );
    }

    /**
     * Kiểm tra username đã tồn tại chưa
     */
    public function usernameExists(string $username): bool
    {
        $result = $this->db->fetchOne(
            "SELECT id FROM `user` WHERE username = ? LIMIT 1",
            [$username]
        );
        return $result !== false;
    }

    /**
     * Kiểm tra email đã tồn tại chưa
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->fetchOne(
            "SELECT id FROM `user` WHERE email = ? LIMIT 1",
            [$email]
        );
        return $result !== false;
    }

    /**
     * Kiểm tra email có thuộc về tài khoản khác hay không.
     */
    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        return (bool) $this->db->fetchOne(
            "SELECT id FROM `user` WHERE email = ? AND id <> ? LIMIT 1",
            [$email, $userId]
        );
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile(int $id, array $data): bool
    {
        $this->db->execute(
            "UPDATE `user` SET full_name=?, email=?, phone=?, gender=?, update_date=NOW(6)
             WHERE id = ?",
            [
                $data['full_name'],
                $data['email'],
                $data['phone'],
                $data['gender'],
                $id,
            ]
        );
        return true;
    }

    /**
     * Xác minh mật khẩu hiện tại trước khi cho phép đổi mật khẩu.
     */
    public function verifyPassword(int $id, string $password): bool
    {
        $user = $this->db->fetchOne(
            "SELECT password FROM `user` WHERE id = ? LIMIT 1",
            [$id]
        );

        return $user !== false
            && is_string($user['password'] ?? null)
            && password_verify($password, $user['password']);
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        $hashed  = password_hash($newPassword, PASSWORD_BCRYPT);
        $affected = $this->db->execute(
            "UPDATE `user` SET password=?, update_date=NOW(6) WHERE id=?",
            [$hashed, $id]
        );
        return $affected > 0;
    }

    /**
     * Lấy tất cả users (dùng cho admin)
     */
    public function getAllUsers(): array
    {
        return $this->db->fetchAll(
            "SELECT id, username, full_name, email, phone, roles, status_user, create_date
             FROM `user` ORDER BY create_date DESC"
        );
    }

    /**
     * Cập nhật trạng thái user (Admin)
     */
    public function setStatus(int $id, string $status): bool
    {
        $affected = $this->db->execute(
            "UPDATE `user` SET status_user=? WHERE id=?",
            [$status, $id]
        );
        return $affected > 0;
    }
}
