<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Models\Customer;

class RegisterController extends Controller
{
    public function create(): void
    {
        if (AUTHGUARD()->isUserLoggedIn()) {
            redirect('/');
        }
        $this->sendPage('frontend/auth/register', [
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function store(): void
    {
        if (AUTHGUARD()->isUserLoggedIn()) {
            redirect('/');
        }

        $this->saveFormValues($_POST, ['password', 'password_confirmation', 'csrf_token']);
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
            'sDT' => trim((string)($_POST['sDT'] ?? $_POST['phoneNumber'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'password_confirmation' => (string)($_POST['password_confirmation'] ?? ''),
        ];

        $customer = new Customer(PDO());
        $errors = $customer->validate($data);
        if (mb_strlen($data['name']) < 2) {
            $errors['name'] = 'Họ tên phải có ít nhất 2 ký tự.';
        }
        if ($errors) {
            redirect('/register', ['errors' => $errors]);
        }

        if (!$customer->fill($data)->save()) {
            redirect('/register', ['errors' => ['register' => 'Không thể tạo tài khoản. Vui lòng thử lại.']]);
        }

        unset($_SESSION['form']);
        redirect('/login', ['success' => 'Đăng ký thành công. Vui lòng đăng nhập.']);
    }
}
