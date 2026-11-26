<?php

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\Customer;

class PaymentController extends Controller
{
    private Payment $paymentModel;
    private Booking $bookingModel;
    private Tour $tourModel;

    public function __construct()
    {
        parent::__construct();

        // 1. Yêu cầu đăng nhập trước khi thực hiện hoặc xem thanh toán
        if (!AUTHGUARD()->isUserLoggedIn()) {
            redirect('/login');
        }

        $pdo = PDO();
        $this->paymentModel = new Payment($pdo);
        $this->bookingModel = new Booking($pdo);
        $this->tourModel    = new Tour($pdo);
    }

    /**
     * 1. Hiển thị giao diện Thanh toán cho Đơn đặt tour
     */
    public function show(int $bookingId): void
    {
        $user = AUTHGUARD()->user();
        $userId = $user->userId ?? $user->id ?? -1;

        // Tìm đơn đặt tour
        $booking = $this->bookingModel->find($bookingId);

        // Kiểm tra đơn hàng có tồn tại và thuộc sở hữu của chính khách hàng này không
        if (!$booking || (int)$booking->userId !== (int)$userId) {
            $this->sendNotFound();
            return;
        }

        // Nếu đơn đặt đã có thông tin thanh toán hoàn tất -> Chuyển đến trang thành công
        $existingPayment = $this->getPaymentByBookingId($bookingId);
        if ($existingPayment && ($existingPayment['status'] ?? '') === 'COMPLETED') {
            redirect('/booking/success/' . $bookingId);
        }

        // Lấy thông tin Tour và thông tin Khách hàng
        $tour = $this->tourModel->findTourById($booking->tourId);
        $customerModel = new Customer(PDO());
        $customer = $customerModel->findByUserId($userId);

        $this->sendPage('customer/payment/index', [
            'booking'  => $booking,
            'tour'     => $tour,
            'customer' => $customer,
            'payment'  => $existingPayment,
            'errors'   => session_get_once('errors', [])
        ]);
    }

    /**
     * 2. Xử lý ghi nhận Giao dịch Thanh toán vào CSDL (bảng Payment)
     */
    public function process(): void
    {
        $user = AUTHGUARD()->user();
        $userId = $user->userId ?? $user->id ?? -1;

        $bookingId     = (int)($_POST['bookingId'] ?? -1);
        $paymentMethod = trim($_POST['paymentMethod'] ?? 'CHUYEN_KHOAN'); // CHUYEN_KHOAN, VNPAY, MOMO, TIEN_MAT
        $amount        = (float)($_POST['amount'] ?? 0);

        // Kiểm tra ràng buộc đơn đặt
        $booking = $this->bookingModel->find($bookingId);
        if (!$booking || (int)$booking->userId !== (int)$userId) {
            redirect('/home', ['errors' => ['payment' => 'Đơn đặt tour không hợp lệ.']]);
        }

        // Tạo mã giao dịch duy nhất (VD: TXN17212345678)
        $transactionId = 'TXN' . time() . rand(100, 999);

        // Gán dữ liệu cho bảng Payment
        $paymentData = [
            'paymentMethod' => $paymentMethod,
            'amount'        => $amount > 0 ? $amount : $booking->totalPrice,
            'transactionId' => $transactionId,
            'status'        => 'COMPLETED', // Có thể để 'PENDING' nếu tích hợp cổng thanh toán online
            'bookingId'     => $bookingId
        ];

        $this->paymentModel->fill($paymentData);

        // Lưu thông tin thanh toán
        if ($this->paymentModel->save()) {
            $_SESSION['flash_data']['success'] = 'Thanh toán đơn đặt tour thành công!';
            redirect('/booking/success/' . $bookingId);
        }

        redirect('/payment/' . $bookingId, ['errors' => ['payment' => 'Có lỗi xảy ra khi lưu thông tin thanh toán.']]);
    }

    /**
     * 3. Xem Chi tiết Hóa đơn / Biên nhận thanh toán
     */
    public function invoice(int $bookingId): void
    {
        $user = AUTHGUARD()->user();
        $userId = $user->userId ?? $user->id ?? -1;

        $booking = $this->bookingModel->find($bookingId);
        if (!$booking || (int)$booking->userId !== (int)$userId) {
            $this->sendNotFound();
            return;
        }

        $payment = $this->getPaymentByBookingId($bookingId);
        if (!$payment) {
            redirect('/payment/' . $bookingId);
        }

        $tour = $this->tourModel->findTourById($booking->tourId);
        $customerModel = new Customer(PDO());
        $customer = $customerModel->findByUserId($userId);

        $this->sendPage('customer/payment/invoice', [
            'booking'  => $booking,
            'payment'  => $payment,
            'tour'     => $tour,
            'customer' => $customer
        ]);
    }

    /**
     * Hàm phụ trợ: Truy vấn dữ liệu bản ghi Payment theo bookingId
     */
    private function getPaymentByBookingId(int $bookingId): ?array
    {
        $stmt = PDO()->prepare('SELECT * FROM Payment WHERE bookingId = :bookingId LIMIT 1');
        $stmt->execute(['bookingId' => $bookingId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }
}