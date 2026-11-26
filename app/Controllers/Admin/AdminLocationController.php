<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Location;
use App\Services\ImageUploader;
use RuntimeException;

class AdminLocationController extends Controller
{
    private Location $locationModel;
    private ImageUploader $uploader;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->locationModel = new Location(PDO());
        $this->uploader = new ImageUploader(ROOTDIR . 'public/uploads/locations');
    }

    public function index(): void
    {
        $this->sendPage('admin/locations/index', [
            'locations' => $this->locationModel->all(),
        ]);
    }

    public function create(): void
    {
        $this->sendPage('admin/locations/create', [
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function store(): void
    {
        $this->saveFormValues($_POST);
        $errors = $this->locationModel->validate($_POST);
        if ($errors) {
            redirect('/admin/locations/create', ['errors' => $errors]);
        }

        $this->locationModel->fill([
            'locationName' => $_POST['locationName'] ?? '',
            'city' => $_POST['city'] ?? '',
            'createdBy' => AUTHGUARD()->user()?->userId,
        ]);

        if (!$this->locationModel->save()) {
            redirect('/admin/locations/create', ['errors' => ['location' => 'Không thể tạo địa điểm.']]);
        }

        try {
            $this->attachUploadedImages($this->locationModel, $_FILES['images'] ?? []);
        } catch (RuntimeException $exception) {
            redirect('/admin/locations/edit/' . $this->locationModel->locationId, [
                'error' => 'Địa điểm đã được tạo nhưng ảnh chưa tải lên: ' . $exception->getMessage(),
            ]);
        }

        unset($_SESSION['form']);
        redirect('/admin/locations', ['success' => 'Thêm địa điểm và hình ảnh thành công.']);
    }

    public function edit(int $locationId): void
    {
        $location = $this->locationModel->findLocationById($locationId);
        if (!$location) {
            $this->sendNotFound();
        }

        $this->sendPage('admin/locations/edit', [
            'location' => $location,
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function update(int $locationId): void
    {
        $location = $this->locationModel->findLocationById($locationId);
        if (!$location) {
            $this->sendNotFound();
        }

        $this->saveFormValues($_POST);
        $errors = $location->validate($_POST);
        if ($errors) {
            redirect('/admin/locations/edit/' . $locationId, ['errors' => $errors]);
        }

        $location->fill($_POST);
        if (!$location->save()) {
            redirect('/admin/locations/edit/' . $locationId, ['error' => 'Không thể cập nhật địa điểm.']);
        }

        try {
            $this->attachUploadedImages($location, $_FILES['images'] ?? []);
        } catch (RuntimeException $exception) {
            redirect('/admin/locations/edit/' . $locationId, [
                'error' => 'Thông tin đã được lưu nhưng ảnh chưa tải lên: ' . $exception->getMessage(),
            ]);
        }

        unset($_SESSION['form']);
        redirect('/admin/locations/edit/' . $locationId, ['success' => 'Cập nhật địa điểm thành công.']);
    }

    public function destroy(int $locationId): void
    {
        $location = $this->locationModel->findLocationById($locationId);
        if (!$location) {
            $this->sendNotFound();
        }

        $images = $location->images;
        if (!$location->delete()) {
            redirect('/admin/locations', [
                'error' => 'Không thể xóa địa điểm đang được một tour sử dụng.',
            ]);
        }

        foreach ($images as $image) {
            $this->uploader->deleteByPublicUrl((string)($image['imageUrl'] ?? ''));
        }
        redirect('/admin/locations', ['success' => 'Đã xóa địa điểm.']);
    }


    private function attachUploadedImages(Location $location, array $files): int
    {
        $urls = $this->uploader->uploadMany($files);
        if (!$urls) {
            return 0;
        }

        $pdo = PDO();
        try {
            $pdo->beginTransaction();
            foreach ($urls as $url) {
                if (!$location->addImageUrl($url)) {
                    throw new RuntimeException('Không thể ghi thông tin ảnh vào cơ sở dữ liệu.');
                }
            }
            $pdo->commit();
            return count($urls);
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($urls as $url) {
                $this->uploader->deleteByPublicUrl($url);
            }
            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException('Không thể hoàn tất quá trình lưu ảnh.');
        }
    }

    public function deleteImage(int $locationId, int $imageId): void
    {
        $location = $this->locationModel->findLocationById($locationId);
        if (!$location) {
            $this->sendNotFound();
        }

        $image = $location->findImage($imageId);
        if (!$image) {
            $this->sendNotFound();
        }

        if (!$location->deleteImage($imageId)) {
            redirect('/admin/locations/edit/' . $locationId, ['error' => 'Không thể xóa ảnh.']);
        }

        $this->uploader->deleteByPublicUrl((string)$image['imageUrl']);
        redirect('/admin/locations/edit/' . $locationId, ['success' => 'Đã xóa ảnh khỏi album.']);
    }
}
