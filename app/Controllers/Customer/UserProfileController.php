<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;

class UserProfileController extends Controller
{
    private Customer $customers;

    public function __construct()
    {
        parent::__construct();
        $this->requireCustomer();
        $this->customers = new Customer(PDO());
    }

    public function show(): void
    {
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        $customer = $this->customers->findByUserId($userId);
        if (!$customer) {
            $this->sendNotFound();
        }
        $this->sendPage('frontend/profile/index', [
            'customer' => $customer,
            'bookings' => (new Booking(PDO()))->getByUser($userId),
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function update(): void
    {
        $this->saveFormValues($_POST);
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        $customer = $this->customers->findByUserId($userId);
        if (!$customer) {
            $this->sendNotFound();
        }
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
            'phoneNumber' => trim((string)($_POST['phoneNumber'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
        ];
        $errors = $customer->validate($data);
        unset($errors['password'], $errors['password_confirmation']);
        if ($errors) {
            redirect('/profile', ['errors' => $errors]);
        }
        if (!$customer->fill($data)->save()) {
            redirect('/profile', ['errors' => ['profile' => 'Không thể cập nhật hồ sơ.']]);
        }
        unset($_SESSION['form']);
        redirect('/profile', ['success' => 'Đã cập nhật hồ sơ cá nhân.']);
    }

    public function changePassword(): void
    {
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        $customer = $this->customers->findByUserId($userId);
        if (!$customer) {
            $this->sendNotFound();
        }
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['password_confirmation'] ?? '');
        $errors = [];
        if (!$customer->verifyPassword($current)) {
            $errors['current_password'] = 'Mật khẩu hiện tại không chính xác.';
        }
        if (strlen($new) < 6) {
            $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        }
        if ($new !== $confirm) {
            $errors['password_confirmation'] = 'Xác nhận mật khẩu mới không khớp.';
        }
        if ($errors) {
            redirect('/profile', ['errors' => $errors]);
        }
        if (!$customer->updatePassword($new)) {
            redirect('/profile', ['error' => 'Không thể đổi mật khẩu.']);
        }
        redirect('/profile', ['success' => 'Đổi mật khẩu thành công.']);
    }
}
