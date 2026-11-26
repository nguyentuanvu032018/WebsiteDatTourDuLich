<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;

class AdminCustomerController extends Controller
{
    private Customer $customers;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->customers = new Customer(PDO());
    }

    public function index(): void
    {
        $this->sendPage('admin/customers/index', ['customers' => $this->customers->getAllCustomersForAdmin()]);
    }

    public function show(int $userId): void
    {
        $customer = $this->customers->findByUserId($userId);
        if (!$customer) {
            $this->sendNotFound();
        }
        $this->sendPage('admin/customers/show', [
            'customer' => $customer,
            'bookings' => (new Booking(PDO()))->getByUser($userId),
        ]);
    }

    public function edit(int $userId): void
    {
        $customer = $this->customers->findByUserId($userId);
        if (!$customer) {
            $this->sendNotFound();
        }
        $this->sendPage('admin/customers/edit', [
            'customer' => $customer,
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function update(int $userId): void
    {
        $customer = $this->customers->findByUserId($userId);
        if (!$customer) {
            $this->sendNotFound();
        }
        $this->saveFormValues($_POST);
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
            'phoneNumber' => trim((string)($_POST['phoneNumber'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
        ];
        $errors = $customer->validate($data);
        unset($errors['password'], $errors['password_confirmation']);
        if ($errors) {
            redirect('/admin/customers/edit/' . $userId, ['errors' => $errors]);
        }
        if (!$customer->fill($data)->save()) {
            redirect('/admin/customers/edit/' . $userId, ['errors' => ['customer' => 'Không thể cập nhật khách hàng.']]);
        }
        unset($_SESSION['form']);
        redirect('/admin/customers', ['success' => 'Đã cập nhật thông tin khách hàng.']);
    }

    public function destroy(int $userId): void
    {
        if (!$this->customers->findByUserId($userId)) {
            $this->sendNotFound();
        }
        if (!$this->customers->deleteAccount($userId)) {
            redirect('/admin/customers', ['error' => 'Không thể xóa tài khoản khách hàng.']);
        }
        redirect('/admin/customers', ['success' => 'Đã xóa tài khoản khách hàng.']);
    }
}
