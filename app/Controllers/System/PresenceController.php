<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Models\UserPresence;

class PresenceController extends Controller
{
    public function heartbeat(): void
    {
        $sessionKey = hash('sha256', session_id());
        $userId = AUTHGUARD()->isUserLoggedIn() ? AUTHGUARD()->user()?->userId : null;
        $path = (string)($_POST['path'] ?? '/');
        (new UserPresence(PDO()))->touch($sessionKey, $userId, $path);
        http_response_code(204);
    }

    public function stream(): never
    {
        $this->requireAdmin();

        header('Content-Type: text/event-stream; charset=UTF-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        session_write_close();
        set_time_limit(10);
        ignore_user_abort(true);
        ini_set('zlib.output_compression', '0');
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        // EventSource sẽ tự động kết nối lại và nhận được bản chụp dữ liệu (snapshot) mới nhất.
        $presence = new UserPresence(PDO());
        $snapshot = $presence->snapshot();
        $payload = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo "retry: 10000\n";
        echo 'id: ' . (int)$snapshot['generatedAt'] . "\n";
        echo "event: presence.updated\n";
        echo 'data: ' . $payload . "\n\n";
        @flush();
        exit;
    }
}
