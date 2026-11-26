<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Tour;
use App\Models\TourSchedule;
use Throwable;

class BookingController extends Controller
{
    private Booking $bookings;
    private Tour $tours;
    private Promotion $promotions;
    private TourSchedule $schedules;

    public function __construct()
    {
        parent::__construct();
        $this->requireCustomer();
        $pdo = PDO();
        $this->bookings = new Booking($pdo);
        $this->tours = new Tour($pdo);
        $this->promotions = new Promotion($pdo);
        $this->schedules = new TourSchedule($pdo);
    }

    public function create(int $tourId): void
    {
        $tour = $this->tours->findPublicDetail($tourId);
        if (!$tour) {
            $this->sendNotFound();
        }
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        $this->sendPage('frontend/bookings/checkout', [
            'tour' => $tour,
            'customer' => (new Customer(PDO()))->findByUserId($userId),
            'schedules' => $this->schedules->getSchedulesByTourId($tourId),
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function store(): void
    {
        $this->saveFormValues($_POST);
        $pdo = PDO();
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        $tourId = (int)($_POST['tourId'] ?? 0);
        $scheduleId = (int)($_POST['scheduleId'] ?? 0);
        $couponCode = strtoupper(trim((string)($_POST['couponCode'] ?? '')));
        $rawPassengers = $_POST['passengers'] ?? [];
        $passengers = [];
        if (is_array($rawPassengers)) {
            foreach ($rawPassengers as $passenger) {
                if (!is_array($passenger)) {
                    continue;
                }
                $name = trim((string)($passenger['fullName'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $passengers[] = [
                    'fullName' => $name,
                    'phoneNumber' => trim((string)($passenger['phoneNumber'] ?? '')),
                    'relationship' => trim((string)($passenger['relationship'] ?? 'Khách')),
                ];
            }
        }

        $tour = $this->tours->findPublicDetail($tourId);
        $errors = [];
        if (!$tour) {
            $errors['tour'] = 'Tour không tồn tại.';
        }
        if ($scheduleId <= 0 || !$this->schedules->belongsToTour($scheduleId, $tourId)) {
            $errors['scheduleId'] = 'Vui lòng chọn lịch khởi hành hợp lệ.';
        }
        if (!$passengers) {
            $errors['passengers'] = 'Vui lòng nhập ít nhất một hành khách.';
        }
        foreach ($passengers as $index => $passengerData) {
            $test = (new BookingDetail($pdo))->fill($passengerData + ['bookingId' => 1]);
            foreach ($test->validate() as $key => $message) {
                if ($key !== 'bookingId') {
                    $errors['passengers'] = 'Thông tin hành khách ' . ($index + 1) . ': ' . $message;
                    break 2;
                }
            }
        }
        if ($errors) {
            redirect('/bookings/checkout/' . max(1, $tourId), ['errors' => $errors]);
        }

        $total = (float)$tour['basePrice'] * count($passengers);
        $appliedCoupon = null;
        if ($couponCode !== '') {
            $promotion = $this->promotions->findByCode($couponCode);
            if (!$promotion) {
                redirect('/bookings/checkout/' . $tourId, ['errors' => ['couponCode' => 'Mã giảm giá không tồn tại.']]);
            }
            if ($promotion->hasBeenUsedByUser((int)$userId)) {
                redirect('/bookings/checkout/' . $tourId, [
                    'errors' => ['couponCode' => 'Bạn đã sử dụng mã khuyến mãi này trước đây. Mỗi tài khoản chỉ được dùng một lần.'],
                ]);
            }
            $validation = $promotion->isValid($total);
            if (!$validation['valid']) {
                redirect('/bookings/checkout/' . $tourId, ['errors' => ['couponCode' => $validation['message']]]);
            }
            $total -= $promotion->calculateDiscount($total);
            $appliedCoupon = $couponCode;
        }

        try {
            $pdo->beginTransaction();
            $availableSlots = $this->schedules->lockAndGetAvailableSlots($scheduleId, $tourId);
            if ($availableSlots === null) {
                throw new \RuntimeException('Lịch khởi hành không còn hợp lệ.');
            }
            if ($availableSlots < count($passengers)) {
                throw new \RuntimeException('Lịch khởi hành chỉ còn ' . $availableSlots . ' chỗ trống.');
            }

            $booking = (new Booking($pdo))->fill([
                'userId' => $userId,
                'tourId' => $tourId,
                'scheduleId' => $scheduleId,
                'couponCode' => $appliedCoupon,
                'totalPrice' => max(0, $total),
                'status' => 'PENDING',
                'createdBy' => $userId,
            ]);
            if (!$booking->save()) {
                throw new \RuntimeException('Không thể tạo đơn đặt tour.');
            }
            if ($appliedCoupon !== null) {
                $promotion = $this->promotions->findByCode($appliedCoupon);
                if (!$promotion || !$promotion->recordUsage((int)$userId, $booking->bookingId)) {
                    throw new \RuntimeException('Không thể ghi nhận lượt sử dụng mã khuyến mãi.');
                }
            }
            foreach ($passengers as $passengerData) {
                if (!(new BookingDetail($pdo))->fill($passengerData + ['bookingId' => $booking->bookingId])->save()) {
                    throw new \RuntimeException('Không thể lưu thông tin hành khách.');
                }
            }
            $pdo->commit();
            unset($_SESSION['form']);
            redirect('/bookings/payment/' . $booking->bookingId, ['success' => 'Đơn đặt tour đã được tạo. Vui lòng chọn hình thức thanh toán.']);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($appliedCoupon !== null) {
                $promotion = $this->promotions->findByCode($appliedCoupon);
                if ($promotion?->hasBeenUsedByUser((int)$userId)) {
                    redirect('/bookings/checkout/' . $tourId, [
                        'errors' => ['couponCode' => 'Bạn đã sử dụng mã khuyến mãi này trước đây. Mỗi tài khoản chỉ được dùng một lần.'],
                    ]);
                }
            }
            redirect('/bookings/checkout/' . $tourId, ['errors' => ['booking' => $exception->getMessage()]]);
        }
    }

    public function payment(int $bookingId): void
    {
        $booking = $this->ownedBooking($bookingId);

        // Kiểm tra status TRƯỚC khi render trang
        if ($booking->status !== 'PENDING') {
            redirect('/bookings/history', [
                'error' => 'Đơn này đã được xử lý nên không thể tiếp tục thanh toán.'
            ]);
        }

        $tour = $this->tours->findPublicDetail($booking->tourId);
        $payment = (new Payment(PDO()))->findByBookingId($bookingId);
        $this->sendPage('frontend/bookings/payment', [
            'booking' => $booking,
            'tour' => $tour,
            'payment' => $payment,
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function processPayment(): void
    {
        $bookingId = (int)($_POST['bookingId'] ?? 0);
        $booking = $this->ownedBooking($bookingId);
        if ($booking->status !== 'PENDING') {
            redirect('/bookings/history', [
                'error' => 'Đơn này đã được xử lý nên không thể tiếp tục thanh toán.'
            ]);
            return;
        }
        //  kiểm tra giao dịch cũ 
        $existingPayment = (new Payment(PDO()))->findByBookingId($bookingId);
        if ($existingPayment && in_array($existingPayment->status, ['SUCCESS', 'REFUNDED'], true)) {
            redirect('/bookings/success/' . $bookingId, ['success' => 'Giao dịch này đã được xử lý trước đó.']);
        }

        $method = strtoupper(trim((string)($_POST['paymentMethod'] ?? 'BANK_TRANSFER')));
        $status = $method === 'CASH' || $method === 'BANK_TRANSFER' ? 'PENDING' : 'SUCCESS';
        $payment = ($existingPayment ?? new Payment(PDO()))->fill([
            'bookingId' => $bookingId,
            'paymentMethod' => $method,
            'amount' => $booking->totalPrice,
            'transactionId' => 'VVT-' . date('YmdHis') . '-' . random_int(1000, 9999),
            'status' => $status,
        ]);
        if (!$payment->save()) {
            redirect('/bookings/payment/' . $bookingId, ['errors' => ['payment' => 'Không thể ghi nhận thanh toán.']]);
        }
        if ($status === 'SUCCESS') {
            $booking->setStatus('CONFIRMED');
        }
        redirect('/bookings/success/' . $bookingId);
    }

    public function success(int $bookingId): void
    {
        $booking = $this->ownedBooking($bookingId);
        $this->sendPage('frontend/bookings/success', [
            'booking' => $booking,
            'tour' => $this->tours->findPublicDetail($booking->tourId),
            'payment' => (new Payment(PDO()))->findByBookingId($bookingId),
        ]);
    }

    public function history(): void
    {
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        $this->sendPage('frontend/bookings/history', [
            'bookings' => $this->bookings->getByUser($userId),
        ]);
    }

    public function cancel(int $bookingId): void
    {
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        if (!$this->bookings->cancelForUser($bookingId, $userId)) {
            redirect('/bookings/history', ['error' => 'Không thể hủy đơn. Đơn có thể không tồn tại hoặc đã hoàn tất.']);
        }
        $payment = (new Payment(PDO()))->findByBookingId($bookingId);
        if ($payment && in_array($payment->status, ['SUCCESS', 'COMPLETED'], true)) {
            $payment->status = 'REFUNDED';
            $payment->save();
        }
        redirect('/bookings/history', ['success' => 'Đã hủy đơn đặt tour.']);
    }

    private function ownedBooking(int $bookingId): Booking
    {
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        $booking = (new Booking(PDO()))->findForUser($bookingId, $userId);
        if (!$booking) {
            $this->sendNotFound();
        }
        return $booking;
    }
}
