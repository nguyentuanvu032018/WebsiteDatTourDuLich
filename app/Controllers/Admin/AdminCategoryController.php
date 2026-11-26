<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\TourCategory;

class AdminCategoryController extends Controller
{
    private TourCategory $categoryModel;

    public function __construct()
    {
        parent::__construct();

        $this->requireAdmin();

        $pdo = PDO();
        $this->categoryModel = new TourCategory($pdo);
    }

    // 1. Hiển thị danh sách tất cả các Danh mục Tour
    public function index(): void
    {
        $categories = $this->categoryModel->all();

        $this->sendPage('admin/categories/index', [
            'categories' => $categories,
            'errors'     => session_get_once('errors', []),
            'success'    => session_get_once('success', null)
        ]);
    }

    // 2. Hiển thị Form tạo mới Danh mục Tour
    public function create(): void
    {
        $this->sendPage('admin/categories/create', [
            'old'    => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', [])
        ]);
    }

    // 3. Xử lý Lưu Danh mục Tour mới vào CSDL (Bảng TourCategory)
    public function store(): void
    {
        $adminUser = AUTHGUARD()->user();
        $adminId = $adminUser?->userId ?? -1;

        $this->saveFormValues($_POST);

        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validate tên danh mục không được để trống
        if (empty($name)) {
            redirect('/admin/categories/create', ['errors' => ['name' => 'Vui lòng nhập tên danh mục.']]);
        }

        // Đổ dữ liệu vào Model TourCategory
        $this->categoryModel->fill([
            'name'        => $name,
            'description' => $description,
            'createdBy'   => $adminId
        ]);

        // Lưu bản ghi vào bảng TourCategory
        if ($this->categoryModel->save()) {
            $_SESSION['success'] = 'Thêm mới danh mục tour thành công!';
            redirect('/admin/categories');
        }

        redirect('/admin/categories/create', ['errors' => ['category' => 'Thêm danh mục thất bại. Vui lòng thử lại!']]);
    }

    // 4. Hiển thị Form cập nhật thông tin Danh mục Tour
    public function edit(int $categoryId): void
    {
        $category = $this->categoryModel->find($categoryId);

        if (!$category) {
            $this->sendNotFound();
            return;
        }

        $this->sendPage('admin/categories/edit', [
            'category' => $category,
            'old'      => $this->getSavedFormValues(),
            'errors'   => session_get_once('errors', [])
        ]);
    }

    // 5. Xử lý Cập nhật Danh mục Tour
    public function update(int $categoryId): void
    {
        $category = $this->categoryModel->find($categoryId);

        if (!$category) {
            $this->sendNotFound();
            return;
        }

        $this->saveFormValues($_POST);

        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            redirect('/admin/categories/edit/' . $categoryId, ['errors' => ['name' => 'Tên danh mục không được để trống.']]);
        }

        $category->fill([
            'name'        => $name,
            'description' => $description
        ]);

        if ($category->save()) {
            $_SESSION['success'] = 'Cập nhật danh mục tour thành công!';
            redirect('/admin/categories');
        }

        redirect('/admin/categories/edit/' . $categoryId, ['errors' => ['category' => 'Cập nhật danh mục thất bại!']]);
    }

    // 6. Xử lý Xóa Danh mục Tour
    public function destroy(int $categoryId): void
    {
        $category = $this->categoryModel->find($categoryId);

        if (!$category) {
            $this->sendNotFound();
            return;
        }

        // TourCategory::delete() trả về array ['success' => bool, 'message' => string]
        $result = $this->categoryModel->delete($categoryId);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        redirect('/admin/categories');
    }
}
