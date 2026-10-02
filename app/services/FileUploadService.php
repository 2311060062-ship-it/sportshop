<?php

/**
 * Upload ảnh sản phẩm với tên do hệ thống sinh.
 */
class FileUploadService
{
    private const MAX_SIZE = 5242880;
    private const PLACEHOLDERS = [
        'product-placeholder.svg',
        'placeholder.png',
        'placeholder.jpg',
        'placeholder.webp',
    ];

    private string $uploadDirectory;

    public function __construct(string $directory = 'products')
    {
        if (!in_array($directory, ['products', 'payment-qr', 'payment-proofs'], true)) {
            throw new InvalidArgumentException('Thư mục tải ảnh không hợp lệ.');
        }
        $this->uploadDirectory = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'images'
            . DIRECTORY_SEPARATOR . $directory;
    }

    /**
     * Trả về tên file mới hoặc null nếu người dùng không chọn ảnh.
     *
     * @throws RuntimeException Khi file upload không hợp lệ.
     */
    public function upload(string $field = 'image'): ?string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->uploadErrorMessage($error));
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('Tệp tải lên không hợp lệ.');
        }
        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new RuntimeException('Ảnh phải có dung lượng không quá 5MB.');
        }
        if (!class_exists('finfo')) {
            throw new RuntimeException('Máy chủ chưa bật extension Fileinfo.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($temporaryPath);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!is_string($mime) || !isset($extensions[$mime])) {
            throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
        }

        $this->ensureUploadDirectory();
        $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
        $destination = $this->uploadDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($temporaryPath, $destination)) {
            throw new RuntimeException('Không thể lưu ảnh đã tải lên.');
        }

        return $filename;
    }

    /**
     * Chỉ xóa file nằm trực tiếp trong thư mục ảnh sản phẩm.
     */
    public function remove(?string $filename): bool
    {
        if ($filename === null || $filename === '') {
            return false;
        }

        $basename = basename(str_replace('\\', '/', $filename));
        if ($basename !== $filename || in_array(strtolower($basename), self::PLACEHOLDERS, true)) {
            return false;
        }

        $directory = realpath($this->uploadDirectory);
        $target = realpath($this->uploadDirectory . DIRECTORY_SEPARATOR . $basename);
        if ($directory === false || $target === false || !is_file($target)) {
            return false;
        }

        $sameDirectory = strcasecmp(dirname($target), $directory) === 0;
        return $sameDirectory && unlink($target);
    }

    private function ensureUploadDirectory(): void
    {
        if (!is_dir($this->uploadDirectory)
            && !mkdir($this->uploadDirectory, 0755, true)
            && !is_dir($this->uploadDirectory)
        ) {
            throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
        }

        if (!is_writable($this->uploadDirectory)) {
            throw new RuntimeException('Thư mục ảnh không có quyền ghi.');
        }
    }

    private function uploadErrorMessage(int $error): string
    {
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'Ảnh vượt quá dung lượng cho phép.';
        }

        return 'Quá trình tải ảnh lên không hoàn tất.';
    }
}
