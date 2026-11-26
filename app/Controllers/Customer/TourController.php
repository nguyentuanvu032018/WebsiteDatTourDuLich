<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\Location;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\TourDetail;
use App\Models\TourSchedule;

class TourController extends Controller
{
    private Tour $tours;
    private TourCategory $categories;
    private Location $locations;
    private Review $reviews;
    private TourSchedule $schedules;
    private TourDetail $tourDetails;
    private Promotion $promotions;

    public function __construct()
    {
        parent::__construct();
        $pdo = PDO();
        $this->tours = new Tour($pdo);
        $this->categories = new TourCategory($pdo);
        $this->locations = new Location($pdo);
        $this->reviews = new Review($pdo);
        $this->schedules = new TourSchedule($pdo);
        $this->tourDetails = new TourDetail($pdo);
        $this->promotions = new Promotion($pdo);
    }

    public function index(): void
    {
        $categoryId = max(-1, (int)($_GET['category'] ?? -1));
        $locationId = max(-1, (int)($_GET['location'] ?? -1));
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $perPage = 12;
        $totalTours = $this->tours->countSearch($categoryId, $locationId, $keyword);
        $totalPages = max(1, (int)ceil($totalTours / $perPage));
        $page = min($totalPages, max(1, (int)($_GET['page'] ?? 1)));
        $this->sendPage('frontend/tours/list', [
            'tours' => $this->tours->search($categoryId, $locationId, $keyword, $perPage, ($page - 1) * $perPage),
            'categories' => $this->categories->all(),
            'locations' => $this->locations->all(),
            'selectedCategory' => $categoryId,
            'selectedLocation' => $locationId,
            'keyword' => $keyword,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalTours' => $totalTours,
        ]);
    }

    public function show(int $tourId): void
    {
        $tour = $this->tours->findPublicDetail($tourId);
        if (!$tour) {
            $this->sendNotFound();
        }
        $tourLocations = [];
        $tourEntity = (new Tour(PDO()))->findTourById($tourId);
        foreach ($tourEntity?->locationIds ?? [] as $locationId) {
            $location = (new Location(PDO()))->findLocationById((int)$locationId);
            if ($location) {
                $tourLocations[] = [
                    'locationId' => $location->locationId,
                    'locationName' => $location->locationName,
                    'city' => $location->city,
                    'images' => $location->getImages(),
                ];
            }
        }
        $this->sendPage('frontend/tours/detail', [
            'tour' => $tour,
            'tourLocations' => $tourLocations,
            'tourDetail' => $this->tourDetails->findByTourId($tourId),
            'schedules' => $this->schedules->getSchedulesByTourId($tourId),
            'reviews' => $this->reviews->getByTourId($tourId),
            'avgRating' => $this->reviews->getAverageRating($tourId),
            'relatedTours' => $this->tours->getRelatedTours(
                $tourId,
                (int)($tour['categoryId'] ?? 0),
                3
            ),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function checkCoupon(): never
    {
        header('Content-Type: application/json; charset=UTF-8');
        $code = strtoupper(trim((string)($_POST['couponCode'] ?? '')));
        $orderTotal = max(0, (float)($_POST['orderTotal'] ?? 0));
        $promotion = $this->promotions->findByCode($code);
        if (!$promotion) {
            http_response_code(404);
            exit(json_encode(['valid' => false, 'message' => 'Mã giảm giá không tồn tại.'], JSON_UNESCAPED_UNICODE));
        }
        $userId = (int)(AUTHGUARD()->user()?->userId ?? 0);
        if ($userId > 0 && !AUTHGUARD()->isAdmin() && $promotion->hasBeenUsedByUser($userId)) {
            http_response_code(409);
            exit(json_encode([
                'valid' => false,
                'message' => 'Bạn đã sử dụng mã khuyến mãi này. Mỗi tài khoản chỉ được dùng một lần.',
            ], JSON_UNESCAPED_UNICODE));
        }
        $validation = $promotion->isValid($orderTotal);
        if (!$validation['valid']) {
            http_response_code(422);
            exit(json_encode($validation, JSON_UNESCAPED_UNICODE));
        }
        $discount = $promotion->calculateDiscount($orderTotal);
        exit(json_encode([
            'valid' => true,
            'message' => 'Áp dụng mã giảm giá thành công.',
            'discountAmount' => $discount,
            'finalTotal' => max(0, $orderTotal - $discount),
        ], JSON_UNESCAPED_UNICODE));
    }
}
