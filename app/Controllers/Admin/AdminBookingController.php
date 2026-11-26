<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Booking;

class AdminBookingController extends Controller
{
    private Booking $bookings;

    public function __construct()
    {
        parent::__construct();

        $this->requireAdmin();
        $this->bookings = new Booking(PDO());
    }

    public function index(): void
    {
        $this->sendPage('admin/bookings/index', [
            'bookings' => $this->bookings->getAllBookingsForAdmin()
        ]);
    }

    public function show(int $bookingId): void
    {
        $booking = $this->bookings->getBookingDetailForAdmin($bookingId);

        if (!$booking) {
            $this->sendNotFound();
            return;
        }

        $this->sendPage('admin/bookings/show', [
            'booking' => $booking
        ]);
    }

    public function cancel(int $bookingId): void
    {
        if (!$this->bookings->cancelBooking($bookingId)) {
            redirect('/admin/bookings', [
                'error' => 'Không thể hủy đơn đặt tour.'
            ]);
            return;
        }

        redirect('/admin/bookings', [
            'success' => 'Đã hủy đơn đặt tour.'
        ]);
    }

    public function confirm(int $bookingId): void
    {
        $booking = $this->bookings->find($bookingId);

        if (!$booking) {
            $this->sendNotFound();
            return;
        }

        if ($booking->status !== 'PENDING') {
            redirect('/admin/bookings', [
                'error' => 'Chỉ có thể xác nhận đơn đang chờ xử lý.'
            ]);
            return;
        }

        if (!$this->bookings->confirmBooking($bookingId)) {
            redirect('/admin/bookings', [
                'error' => 'Không thể xác nhận đơn đặt tour.'
            ]);
            return;
        }

        redirect('/admin/bookings', [
            'success' => 'Xác nhận đơn đặt tour thành công!'
        ]);
    }
}
