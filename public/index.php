<?php

declare(strict_types=1);

ob_start();

error_reporting(E_ALL);

define('APPNAME', 'VivuTour - Hệ thống đặt tour du lịch');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('VIVUTOURSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once dirname(__DIR__) . '/bootstrap.php';
ini_set('display_errors', ($_ENV['APP_ENV'] ?? 'development') === 'production' ? '0' : '1');
csrf_token();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

// Prevent an authenticated page from being restored from the browser cache after logout.
if (AUTHGUARD()->isUserLoggedIn()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
} else {
    header('Cache-Control: private, no-cache, must-revalidate');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_valid_csrf_token();
}

// Record the current visit on the server as a reliable first presence signal.
// JavaScript heartbeat below keeps an idle open tab online after this request.
if (($_SERVER['REQUEST_URI'] ?? '') !== '/events/presence') {
    try {
        $presencePath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        (new \App\Models\UserPresence(PDO()))->touch(
            hash('sha256', session_id()),
            AUTHGUARD()->isUserLoggedIn() ? AUTHGUARD()->user()?->userId : null,
            is_string($presencePath) ? $presencePath : '/'
        );
    } catch (\Throwable $exception) {
        // Presence is supplementary and must never make the main website unavailable.
        error_log('Presence tracking failed: ' . $exception->getMessage());
    }
}

$router = new \Bramus\Router\Router();

// Authentication
$router->get('/login', '\App\Controllers\Auth\LoginController@create');
$router->post('/login', '\App\Controllers\Auth\LoginController@store');
$router->get('/register', '\App\Controllers\Auth\RegisterController@create');
$router->post('/register', '\App\Controllers\Auth\RegisterController@store');
$router->post('/logout', '\App\Controllers\Auth\LoginController@destroy');

// Online presence heartbeat and Admin SSE stream
$router->post('/presence/heartbeat', '\App\Controllers\System\PresenceController@heartbeat');
$router->get('/events/presence', '\App\Controllers\System\PresenceController@stream');

// Public pages
$router->get('/', '\App\Controllers\Customer\HomeController@index');
$router->get('/home', '\App\Controllers\Customer\HomeController@index');
$router->get('/about', function (): void {
    (new \App\Controllers\Controller())->sendPage('frontend/pages/about');
});
$router->get('/contact', '\App\Controllers\Customer\ContactController@create');
$router->post('/contact/send', '\App\Controllers\Customer\ContactController@store');
$router->get('/contacts/my', '\App\Controllers\Customer\ContactController@myContacts');
$router->get('/contacts/my/(\d+)', '\App\Controllers\Customer\ContactController@showMine');
$router->post('/contacts/my/(\d+)/reply', '\App\Controllers\Customer\ContactController@reply');

// Tours and reviews
$router->get('/tours', '\App\Controllers\Customer\TourController@index');
$router->get('/tours/detail/(\d+)', '\App\Controllers\Customer\TourController@show');
$router->get('/destinations/(\d+)', '\App\Controllers\Customer\DestinationController@show');
$router->post('/tours/coupon', '\App\Controllers\Customer\TourController@checkCoupon');
$router->post('/tours/reviews', '\App\Controllers\Customer\ReviewController@store');
$router->post('/tours/reviews/delete/(\d+)', '\App\Controllers\Customer\ReviewController@destroy');
$router->get('/api/tours/(\d+)/reviews', '\App\Controllers\Customer\ReviewController@getByTour');

// Booking and payment flow
$router->get('/bookings/checkout/(\d+)', '\App\Controllers\Customer\BookingController@create');
$router->post('/bookings/store', '\App\Controllers\Customer\BookingController@store');
$router->get('/bookings/payment/(\d+)', '\App\Controllers\Customer\BookingController@payment');
$router->post('/bookings/payment', '\App\Controllers\Customer\BookingController@processPayment');
$router->get('/bookings/success/(\d+)', '\App\Controllers\Customer\BookingController@success');
$router->get('/bookings/history', '\App\Controllers\Customer\BookingController@history');
$router->post('/bookings/cancel/(\d+)', '\App\Controllers\Customer\BookingController@cancel');
$router->post('/admin/bookings/confirm/(\d+)',   '\App\Controllers\Admin\AdminBookingController@confirm');


// Customer profile
$router->get('/profile', '\App\Controllers\Customer\UserProfileController@show');
$router->post('/profile/update', '\App\Controllers\Customer\UserProfileController@update');
$router->post('/profile/password', '\App\Controllers\Customer\UserProfileController@changePassword');

// Cached uploaded images (ETag + Last-Modified + 304)
$router->get('/media/locations/([A-Za-z0-9._-]+)', '\App\Controllers\System\MediaController@locationImage');

// Admin dashboard
$router->get('/admin', '\App\Controllers\Admin\AdminDashboardController@index');
$router->get('/admin/dashboard', '\App\Controllers\Admin\AdminDashboardController@index');

// Admin tours
$router->get('/admin/tours', '\App\Controllers\Admin\AdminTourController@index');
$router->get('/admin/tours/create', '\App\Controllers\Admin\AdminTourController@create');
$router->post('/admin/tours/store', '\App\Controllers\Admin\AdminTourController@store');
$router->get('/admin/tours/edit/(\d+)', '\App\Controllers\Admin\AdminTourController@edit');
$router->post('/admin/tours/update/(\d+)', '\App\Controllers\Admin\AdminTourController@update');
$router->post('/admin/tours/delete/(\d+)', '\App\Controllers\Admin\AdminTourController@destroy');
$router->post('/admin/tours/schedule', '\App\Controllers\Admin\AdminTourController@addSchedule');

// Admin categories
$router->get('/admin/categories', '\App\Controllers\Admin\AdminCategoryController@index');
$router->get('/admin/categories/create', '\App\Controllers\Admin\AdminCategoryController@create');
$router->post('/admin/categories/store', '\App\Controllers\Admin\AdminCategoryController@store');
$router->get('/admin/categories/edit/(\d+)', '\App\Controllers\Admin\AdminCategoryController@edit');
$router->post('/admin/categories/update/(\d+)', '\App\Controllers\Admin\AdminCategoryController@update');
$router->post('/admin/categories/delete/(\d+)', '\App\Controllers\Admin\AdminCategoryController@destroy');

// Admin locations and image upload
$router->get('/admin/locations', '\App\Controllers\Admin\AdminLocationController@index');
$router->get('/admin/locations/create', '\App\Controllers\Admin\AdminLocationController@create');
$router->post('/admin/locations/store', '\App\Controllers\Admin\AdminLocationController@store');
$router->get('/admin/locations/edit/(\d+)', '\App\Controllers\Admin\AdminLocationController@edit');
$router->post('/admin/locations/update/(\d+)', '\App\Controllers\Admin\AdminLocationController@update');
$router->post('/admin/locations/delete/(\d+)', '\App\Controllers\Admin\AdminLocationController@destroy');
$router->post('/admin/locations/(\d+)/images/delete/(\d+)', '\App\Controllers\Admin\AdminLocationController@deleteImage');

// Admin customers
$router->get('/admin/customers', '\App\Controllers\Admin\AdminCustomerController@index');
$router->get('/admin/customers/show/(\d+)', '\App\Controllers\Admin\AdminCustomerController@show');
$router->get('/admin/customers/edit/(\d+)', '\App\Controllers\Admin\AdminCustomerController@edit');
$router->post('/admin/customers/update/(\d+)', '\App\Controllers\Admin\AdminCustomerController@update');
$router->post('/admin/customers/delete/(\d+)', '\App\Controllers\Admin\AdminCustomerController@destroy');

// Admin bookings and payments
$router->get('/admin/bookings', '\App\Controllers\Admin\AdminBookingController@index');
$router->get('/admin/bookings/show/(\d+)', '\App\Controllers\Admin\AdminBookingController@show');
$router->post('/admin/bookings/cancel/(\d+)', '\App\Controllers\Admin\AdminBookingController@cancel');
$router->get('/admin/payments', '\App\Controllers\Admin\AdminPaymentController@index');
$router->get('/admin/payments/show/(\d+)', '\App\Controllers\Admin\AdminPaymentController@show');
$router->post('/admin/payments/confirm/(\d+)', '\App\Controllers\Admin\AdminPaymentController@confirm');


// Admin promotions and reviews
$router->get('/admin/promotions', '\App\Controllers\Admin\AdminPromotionController@index');
$router->get('/admin/promotions/create', '\App\Controllers\Admin\AdminPromotionController@create');
$router->post('/admin/promotions/store', '\App\Controllers\Admin\AdminPromotionController@store');
$router->get('/admin/promotions/edit/([A-Za-z0-9_-]+)', '\App\Controllers\Admin\AdminPromotionController@edit');
$router->post('/admin/promotions/update/([A-Za-z0-9_-]+)', '\App\Controllers\Admin\AdminPromotionController@update');
$router->post('/admin/promotions/delete/([A-Za-z0-9_-]+)', '\App\Controllers\Admin\AdminPromotionController@destroy');
$router->get('/admin/reviews', '\App\Controllers\Admin\AdminReviewController@index');
$router->get('/admin/reviews/show/(\d+)', '\App\Controllers\Admin\AdminReviewController@show');
$router->post('/admin/reviews/delete/(\d+)', '\App\Controllers\Admin\AdminReviewController@destroy');

// Admin contact messages
$router->get('/admin/contacts', '\App\Controllers\Admin\AdminContactController@index');
$router->get('/admin/contacts/show/(\d+)', '\App\Controllers\Admin\AdminContactController@show');
$router->post('/admin/contacts/reply/(\d+)', '\App\Controllers\Admin\AdminContactController@reply');
$router->post('/admin/contacts/resolve/(\d+)', '\App\Controllers\Admin\AdminContactController@resolve');
$router->post('/admin/contacts/delete/(\d+)', '\App\Controllers\Admin\AdminContactController@destroy');

$router->set404(function (): void {
    (new \App\Controllers\Controller())->sendNotFound();
});

$router->run();
