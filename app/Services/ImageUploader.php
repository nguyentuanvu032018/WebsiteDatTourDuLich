<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class ImageUploader
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    private const MAX_FILES = 12;
    private const MAX_PIXELS = 40_000_000;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private readonly string $uploadDirectory,
        private readonly string $mediaUrlPrefix = '/media/locations/'
    ) {
    }

    /**
     * @return string[] 
     */
    public function uploadMany(array $files): array
    {
        $normalized = array_values(array_filter(
            $this->normalizeFilesArray($files),
            static fn (array $file): bool => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ));
        if (count($normalized) > self::MAX_FILES) {
            throw new RuntimeException('Mỗi lần chỉ được tải tối đa 12 ảnh.');
        }

        $urls = [];
        try {
            foreach ($normalized as $file) {
                $urls[] = $this->uploadOne($file);
            }
            return $urls;
        } catch (RuntimeException $exception) {
            foreach ($urls as $url) {
                $this->deleteByPublicUrl($url);
            }
            throw $exception;
        }
    }

    public function deleteByPublicUrl(string $url): void
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, $this->mediaUrlPrefix)) {
            return;
        }

        $filename = basename($path);
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            return;
        }

        $filePath = rtrim($this->uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    private function uploadOne(array $file): string
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->uploadErrorMessage($error));
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Tập tin tải lên không hợp lệ.');
        }
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Mỗi ảnh phải có dung lượng từ 1 byte đến 5 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmpName);
        $extension = self::ALLOWED_MIME_TYPES[$mime] ?? null;
        if (!$extension) {
            throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.');
        }

        $dimensions = @getimagesize($tmpName);
        if (!is_array($dimensions) || empty($dimensions[0]) || empty($dimensions[1])) {
            throw new RuntimeException('Nội dung tập tin không phải là ảnh hợp lệ.');
        }
        if ((int)$dimensions[0] * (int)$dimensions[1] > self::MAX_PIXELS) {
            throw new RuntimeException('Ảnh có độ phân giải quá lớn; tối đa 40 triệu điểm ảnh.');
        }

        if (!is_dir($this->uploadDirectory)
            && !mkdir($this->uploadDirectory, 0755, true)
            && !is_dir($this->uploadDirectory)) {
            throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = rtrim($this->uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpName, $destination)) {
            throw new RuntimeException('Không thể lưu ảnh lên máy chủ.');
        }

        @chmod($destination, 0644);
        return $this->mediaUrlPrefix . rawurlencode($filename);
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizeFilesArray(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            return [$files];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }
        return $normalized;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ảnh vượt quá dung lượng cho phép.',
            UPLOAD_ERR_PARTIAL => 'Ảnh chỉ được tải lên một phần. Vui lòng thử lại.',
            UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ thiếu thư mục tạm để nhận ảnh.',
            UPLOAD_ERR_CANT_WRITE => 'Máy chủ không thể ghi ảnh vào ổ đĩa.',
            UPLOAD_ERR_EXTENSION => 'Phần mở rộng PHP đã chặn quá trình tải ảnh.',
            default => 'Không thể tải ảnh lên máy chủ.',
        };
    }
}
