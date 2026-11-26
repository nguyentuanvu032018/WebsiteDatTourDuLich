<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\UserPresence;

class AdminDashboardController extends Controller
{
    private Booking $bookingModel;
    private Tour $tourModel;
    private Customer $customerModel;
    private Payment $paymentModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();

        $pdo = PDO();
        $this->bookingModel = new Booking($pdo);
        $this->tourModel = new Tour($pdo);
        $this->customerModel = new Customer($pdo);
        $this->paymentModel = new Payment($pdo);
    }

    public function index(): void
    {
        $this->sendPage('admin/dashboard', [
            'totalRevenue' => $this->paymentModel->getTotalRevenue(),
            'totalBookings' => $this->bookingModel->countAll(),
            'totalCustomers' => $this->customerModel->countAll(),
            'totalTours' => $this->tourModel->countAll(),
            'recentBookings' => $this->bookingModel->getRecentBookings(5),
            'topTours' => $this->tourModel->getTopBookedTours(5),
            'monthlyRevenue' => $this->paymentModel->getMonthlyRevenueLast6Months(),
            'presence' => (new UserPresence(PDO()))->snapshot(),
        ]);
    }
}
