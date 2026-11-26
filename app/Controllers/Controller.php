<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactReply;
use League\Plates\Engine;
use Throwable;

class Controller
{
    protected Engine $view;

    public function __construct()
    {
        $this->view = new Engine(ROOTDIR . 'app/views');
        $currentUser = AUTHGUARD()->user();
        $unreadContactReplies = 0;
        if ($currentUser && !AUTHGUARD()->isAdmin()) {
            try {
                $unreadContactReplies = (new ContactReply(PDO()))
                    ->countUnreadForCustomer((int)$currentUser->userId);
            } catch (Throwable) {
                // Giữ các trang cũ hoạt động nếu migration phản hồi chưa được chạy.
            }
        }
        $this->view->addData([
            'currentUser' => $currentUser,
            'unreadContactReplies' => $unreadContactReplies,
            'flashError' => session_get_once('error'),
            'flashSuccess' => session_get_once('success'),
        ]);
    }

    public function sendPage(string $page, array $data = []): never
    {
        header('Content-Type: text/html; charset=UTF-8');
        exit($this->view->render($page, $data));
    }

    protected function saveFormValues(array $data, array $except = ['password', 'password_confirmation', 'csrf_token']): void
    {
        $_SESSION['form'] = array_filter(
            $data,
            static fn (string $key): bool => !in_array($key, $except, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function getSavedFormValues(): array
    {
        $values = session_get_once('form', []);
        return is_array($values) ? $values : [];
    }

    protected function requireLogin(): void
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login', ['error' => 'Vui lòng đăng nhập để tiếp tục.']);
        }
    }

    protected function requireCustomer(): void
    {
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login', ['error' => 'Vui lòng đăng nhập để tiếp tục.']);
        }
        if (AUTHGUARD()->isAdmin()) {
            redirect('/admin', ['error' => 'Chức năng này chỉ dành cho tài khoản khách hàng.']);
        }
    }

    protected function requireAdmin(): void
    {
        if (!AUTHGUARD()->isAdmin()) {
            redirect('/login', ['error' => 'Bạn không có quyền truy cập khu vực quản trị.']);
        }
    }

    public function sendNotFound(): never
    {
        http_response_code(404);
        $this->sendPage('errors/404');
    }
}
