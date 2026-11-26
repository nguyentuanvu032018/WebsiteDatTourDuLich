<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Promotion;

class AdminPromotionController extends Controller
{
    private Promotion $promotions;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->promotions = new Promotion(PDO());
    }

    public function index(): void
    {
        $this->sendPage('admin/promotions/index', ['promotions' => $this->promotions->all()]);
    }

    public function create(): void
    {
        $this->sendPage('admin/promotions/create', [
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function store(): void
    {
        $this->saveFormValues($_POST);
        $promotion = (new Promotion(PDO()))->fill($_POST);
        $errors = $promotion->validate($_POST, true);
        if ($errors) {
            redirect('/admin/promotions/create', ['errors' => $errors]);
        }
        $promotion->createdByAdmin = (int)(AUTHGUARD()->user()?->userId ?? 0) ?: null;
        if (!$promotion->save()) {
            redirect('/admin/promotions/create', ['errors' => ['promotion' => 'Không thể tạo mã khuyến mãi.']]);
        }
        unset($_SESSION['form']);
        redirect('/admin/promotions', ['success' => 'Đã tạo mã khuyến mãi.']);
    }

    public function edit(string $couponCode): void
    {
        $promotion = $this->promotions->findByCode($couponCode);
        if (!$promotion) {
            $this->sendNotFound();
        }
        $this->sendPage('admin/promotions/edit', [
            'promotion' => $promotion,
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function update(string $couponCode): void
    {
        $promotion = $this->promotions->findByCode($couponCode);
        if (!$promotion) {
            $this->sendNotFound();
        }
        $this->saveFormValues($_POST);
        $errors = $promotion->validate($_POST + ['couponCode' => $couponCode], false);
        if ($errors) {
            redirect('/admin/promotions/edit/' . rawurlencode($couponCode), ['errors' => $errors]);
        }
        $promotion->fill($_POST + ['couponCode' => $couponCode]);
        if (!$promotion->save()) {
            redirect('/admin/promotions/edit/' . rawurlencode($couponCode), ['errors' => ['promotion' => 'Không thể cập nhật mã.']]);
        }
        unset($_SESSION['form']);
        redirect('/admin/promotions', ['success' => 'Đã cập nhật mã khuyến mãi.']);
    }

    public function destroy(string $couponCode): void
    {
        $promotion = $this->promotions->findByCode($couponCode);
        if (!$promotion) {
            $this->sendNotFound();
        }
        if (!$promotion->delete()) {
            redirect('/admin/promotions', ['error' => 'Không thể xóa mã đang được sử dụng.']);
        }
        redirect('/admin/promotions', ['success' => 'Đã xóa mã khuyến mãi.']);
    }
}
