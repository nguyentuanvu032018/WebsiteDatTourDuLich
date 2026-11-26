<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Models\User;

class LoginController extends Controller
{
    public function create(): void
    {
        if (AUTHGUARD()->isUserLoggedIn()) {
            redirect(AUTHGUARD()->isAdmin() ? '/admin' : '/');
        }

        $this->sendPage('frontend/auth/login', [
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function store(): void
    {
        if (AUTHGUARD()->isUserLoggedIn()) {
            redirect(AUTHGUARD()->isAdmin() ? '/admin' : '/');
        }

        $this->saveFormValues($_POST, ['password', 'csrf_token']);
        $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string)($_POST['password'] ?? '');
        $errors = [];

        if (!$email) {
            $errors['email'] = 'Email không đúng định dạng.';
        }
        if ($password === '') {
            $errors['password'] = 'Vui lòng nhập mật khẩu.';
        }
        if ($errors) {
            redirect('/login', ['errors' => $errors]);
        }

        $user = (new User(PDO()))->where('email', (string)$email);
        if ($user && AUTHGUARD()->login($user, ['password' => $password])) {
            unset($_SESSION['form']);
            redirect(AUTHGUARD()->isAdmin() ? '/admin' : '/', [
                'success' => 'Đăng nhập thành công. Chào mừng ' . $user->name . '!',
            ]);
        }

        redirect('/login', ['errors' => ['login' => 'Email hoặc mật khẩu không chính xác.']]);
    }

    public function destroy(): void
    {
        header('Clear-Site-Data: "cache"');
        AUTHGUARD()->logout();
        $_SESSION['success'] = 'Bạn đã đăng xuất hoàn toàn khỏi tài khoản.';
        redirect('/');
    }
}
