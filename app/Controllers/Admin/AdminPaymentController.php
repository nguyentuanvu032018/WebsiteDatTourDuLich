<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;

class AdminPaymentController extends Controller
{
    private Payment $payments;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->payments = new Payment(PDO());
    }

    public function index(): void
    {
        $this->sendPage('admin/payments/index', ['payments' => $this->payments->allForAdmin()]);
    }

    public function show(int $paymentId): void
    {
        $payment = $this->payments->detailForAdmin($paymentId);
        if (!$payment) {
            $this->sendNotFound();
        }
        $this->sendPage('admin/payments/show', ['payment' => $payment]);
    }

    public function confirm(int $paymentId): void
    {
        $payment = $this->payments->find($paymentId);
        if (!$payment) {
            $this->sendNotFound();
        }
        // thanh toán đã được xác nhận 
        $payment->status = 'SUCCESS';
        if ($payment->save()) {
            $_SESSION['flash_data']['success']
                = 'Xác nhận thanh toán thành công!';
        } else {
            $_SESSION['flash_data']['error']
                = 'Xác nhận thanh toán thất bại!';
        }
        redirect('/admin/payments', ['success' => 'Đã xác nhận thanh toán.']);
    }


    public function updateStatus(int $paymentId): void
    {
        $payment = $this->payments->find($paymentId);

        if (!$payment) {
            $this->sendNotFound();
            return;
        }

        $status = strtoupper(trim($_POST['status'] ?? ''));

        $allowedStatuses = [
            'PENDING',
            'SUCCESS',
            'FAILED',
            'REFUNDED'
        ];

        if (!in_array($status, $allowedStatuses)) {
            redirect('/admin/payments/show/' . $paymentId, [
                'errors' => [
                    'status' => 'Trạng thái thanh toán không hợp lệ.'
                ]
            ]);
        }

        $payment->status = $status;

        if ($payment->save()) {
            $_SESSION['flash_data']['success']
                = 'Cập nhật trạng thái thanh toán thành công!';
        } else {
            $_SESSION['flash_data']['error']
                = 'Cập nhật trạng thái thất bại!';
        }

        redirect('/admin/payments/show/' . $paymentId);
    }
}
