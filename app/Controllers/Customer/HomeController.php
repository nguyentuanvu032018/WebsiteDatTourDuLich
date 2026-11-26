<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\Location;
use App\Models\Promotion;
use App\Models\Tour;
use App\Models\TourCategory;

class HomeController extends Controller
{
    public function index(): void
    {
        $pdo = PDO();
        $categoryId = max(-1, (int)($_GET['category'] ?? -1));
        $locationId = max(-1, (int)($_GET['location'] ?? -1));
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $locationModel = new Location($pdo);

        $this->sendPage('frontend/home/index', [
            'tours' => array_slice((new Tour($pdo))->search($categoryId, $locationId, $keyword), 0, 8),
            'categories' => (new TourCategory($pdo))->all(),
            'locations' => $locationModel->all(),
            'popularLocations' => $locationModel->popular(6),
            'promotions' => (new Promotion($pdo))->active(),
            'selectedCategory' => $categoryId,
            'selectedLocation' => $locationId,
            'searchKeyword' => $keyword,
        ]);
    }
}
