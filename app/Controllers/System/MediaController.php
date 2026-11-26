<?php

declare(strict_types=1);

namespace App\Controllers\System;

class MediaController
{
    public function locationImage(string $filename): void
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            $this->notFound();
        }

        $path = ROOTDIR . 'public/uploads/locations/' . $filename;
        if (!is_file($path) || !is_readable($path)) {
            $this->notFound();
        }

        $lastModified = (int)filemtime($path);
        $size = (int)filesize($path);
        $etag = '"' . hash('sha256', $filename . '|' . $lastModified . '|' . $size) . '"';

        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('Cache-Control: public, max-age=86400, immutable');
        header('X-Content-Type-Options: nosniff');

        $ifNoneMatch = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        $ifModifiedSince = strtotime((string)($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '')) ?: 0;
        if ($ifNoneMatch === $etag || ($ifNoneMatch === '' && $ifModifiedSince >= $lastModified)) {
            http_response_code(304);
            exit;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($path);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            $this->notFound();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        readfile($path);
        exit;
    }

    private function notFound(): never
    {
        http_response_code(404);
        exit('Không tìm thấy hình ảnh.');
    }
}
