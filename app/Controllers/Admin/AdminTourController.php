<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Location;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\TourDetail;
use App\Models\TourSchedule;

class AdminTourController extends Controller
{
    private Tour $tours;
    private TourCategory $categories;
    private Location $locations;
    private TourSchedule $schedules;
    private TourDetail $tourDetails;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $pdo = PDO();
        $this->tours = new Tour($pdo);
        $this->categories = new TourCategory($pdo);
        $this->locations = new Location($pdo);
        $this->schedules = new TourSchedule($pdo);
        $this->tourDetails = new TourDetail($pdo);
    }

    public function index(): void
    {
        $this->sendPage('admin/tours/index', [
            'tours' => $this->tours->all(),
            'categories' => $this->categories->all(),
        ]);
    }

    public function create(): void
    {
        $this->sendPage('admin/tours/create', [
            'categories' => $this->categories->all(),
            'locations' => $this->locations->all(),
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
            'tourDetail' => [],
        ]);
    }

    public function store(): void
    {
        $this->saveFormValues($_POST);
        $errors = array_merge($this->tours->validate($_POST), $this->tourDetails->validate($_POST));
        if ($errors) {
            redirect('/admin/tours/create', ['errors' => $errors]);
        }
        $tour = (new Tour(PDO()))->fill($_POST + ['createdBy' => AUTHGUARD()->user()?->userId]);
        if (!$tour->save()) {
            redirect('/admin/tours/create', ['errors' => ['tour' => 'Không thể tạo tour.']]);
        }
        if (!$this->tourDetails->saveForTour($tour->tourId, $_POST)) {
            redirect('/admin/tours/edit/' . $tour->tourId, ['error' => 'Tour đã tạo nhưng chưa thể lưu phần mô tả chi tiết.']);
        }
        unset($_SESSION['form']);
        redirect('/admin/tours', ['success' => 'Đã tạo tour mới.']);
    }

    public function edit(int $tourId): void
    {
        $tour = $this->tours->findTourById($tourId);
        if (!$tour) {
            $this->sendNotFound();
        }
        $this->sendPage('admin/tours/edit', [
            'tour' => $tour,
            'categories' => $this->categories->all(),
            'locations' => $this->locations->all(),
            'schedules' => $this->schedules->getSchedulesByTourId($tourId, false),
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
            'tourDetail' => $this->tourDetails->findByTourId($tourId),
        ]);
    }

    public function update(int $tourId): void
    {
        $tour = $this->tours->findTourById($tourId);
        if (!$tour) {
            $this->sendNotFound();
        }
        $this->saveFormValues($_POST);
        $errors = array_merge($tour->validate($_POST), $this->tourDetails->validate($_POST));
        if ($errors) {
            redirect('/admin/tours/edit/' . $tourId, ['errors' => $errors]);
        }
        if (!$tour->fill($_POST)->save()) {
            redirect('/admin/tours/edit/' . $tourId, ['errors' => ['tour' => 'Không thể cập nhật tour.']]);
        }
        if (!$this->tourDetails->saveForTour($tourId, $_POST)) {
            redirect('/admin/tours/edit/' . $tourId, ['error' => 'Đã cập nhật thông tin chung nhưng chưa thể lưu phần mô tả chi tiết.']);
        }
        unset($_SESSION['form']);
        redirect('/admin/tours', ['success' => 'Đã cập nhật tour.']);
    }

    public function destroy(int $tourId): void
    {
        $tour = $this->tours->findTourById($tourId);
        if (!$tour) {
            $this->sendNotFound();
        }
        if (!$tour->delete()) {
            redirect('/admin/tours', ['error' => 'Không thể xóa tour đã có đơn đặt.']);
        }
        redirect('/admin/tours', ['success' => 'Đã xóa tour.']);
    }

    public function addSchedule(): void
    {
        $data = [
            'tourId' => (int)($_POST['tourId'] ?? 0),
            'startDate' => (string)($_POST['startDate'] ?? ''),
            'endDate' => (string)($_POST['endDate'] ?? ''),
            'maxParticipants' => (int)($_POST['maxParticipants'] ?? 0),
            'createdBy' => AUTHGUARD()->user()?->userId,
        ];
        $errors = $this->schedules->validate($data);
        if ($errors) {
            redirect('/admin/tours/edit/' . max(1, $data['tourId']), ['errors' => $errors]);
        }
        if (!(new TourSchedule(PDO()))->fill($data)->save()) {
            redirect('/admin/tours/edit/' . $data['tourId'], ['error' => 'Không thể thêm lịch khởi hành.']);
        }
        redirect('/admin/tours/edit/' . $data['tourId'], ['success' => 'Đã thêm lịch khởi hành.']);
    }
}
