<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\Review;
use App\Models\Tour;

class ReviewController extends Controller
{
    private Review $reviews;
    private Tour $tours;

    public function __construct()
    {
        parent::__construct();
        $this->reviews = new Review(PDO());
        $this->tours = new Tour(PDO());
    }

    public function store(): void
    {
        $this->requireCustomer();
        $tourId = (int)($_POST['tourId'] ?? 0);
        if (!$this->tours->findTourById($tourId)) {
            $this->sendNotFound();
        }
        $review = (new Review(PDO()))->fill([
            'rating' => (int)($_POST['rating'] ?? 0),
            'comment' => (string)($_POST['comment'] ?? ''),
            'customerUserId' => AUTHGUARD()->user()?->userId ?? -1,
            'tourId' => $tourId,
        ]);
        $errors = $review->validate();
        if ($errors) {
            redirect('/tours/detail/' . $tourId, ['errors' => $errors]);
        }
        if (!$review->save()) {
            redirect('/tours/detail/' . $tourId, ['error' => 'Không thể lưu đánh giá.']);
        }
        redirect('/tours/detail/' . $tourId, ['success' => 'Cảm ơn bạn đã gửi đánh giá.']);
    }

    public function destroy(int $reviewId): void
    {
        $this->requireCustomer();
        $review = $this->reviews->find($reviewId);
        if (!$review) {
            $this->sendNotFound();
        }
        $userId = AUTHGUARD()->user()?->userId ?? -1;
        if (!$this->reviews->deleteForUser($reviewId, $userId)) {
            http_response_code(403);
            redirect('/tours/detail/' . $review->tourId, ['error' => 'Bạn không có quyền xóa đánh giá này.']);
        }
        redirect('/tours/detail/' . $review->tourId, ['success' => 'Đã xóa đánh giá.']);
    }

    public function getByTour(int $tourId): never
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!$this->tours->findTourById($tourId)) {
            http_response_code(404);
            exit(json_encode(['success' => false, 'message' => 'Tour không tồn tại.'], JSON_UNESCAPED_UNICODE));
        }
        $reviews = $this->reviews->getByTourId($tourId);
        exit(json_encode([
            'success' => true,
            'tourId' => $tourId,
            'averageRating' => $this->reviews->getAverageRating($tourId),
            'count' => count($reviews),
            'reviews' => $reviews,
        ], JSON_UNESCAPED_UNICODE));
    }
}
