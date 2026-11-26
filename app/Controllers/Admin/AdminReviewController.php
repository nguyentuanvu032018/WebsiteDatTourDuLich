<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Review;

class AdminReviewController extends Controller
{
    private Review $reviews;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->reviews = new Review(PDO());
    }

    public function index(): void
    {
        $this->sendPage('admin/reviews/index', ['reviews' => $this->reviews->getAllReviewsForAdmin()]);
    }

    public function show(int $reviewId): void
    {
        $review = $this->reviews->getReviewDetailForAdmin($reviewId);
        if (!$review) {
            $this->sendNotFound();
        }
        $this->sendPage('admin/reviews/show', ['review' => $review]);
    }

    public function destroy(int $reviewId): void
    {
        $review = $this->reviews->find($reviewId);
        if (!$review) {
            $this->sendNotFound();
        }
        if (!$review->delete()) {
            redirect('/admin/reviews', ['error' => 'Không thể xóa đánh giá.']);
        }
        redirect('/admin/reviews', ['success' => 'Đã xóa đánh giá.']);
    }
}
