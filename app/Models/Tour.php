<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class Tour
{
    public int $tourId = -1;
    public string $title = '';
    public string $description = '';
    public float $basePrice = 0.0;
    public int $numberDay = 1;
    /** @var int[] */
    public array $locationIds = [];
    public int $categoryId = -1;
    public string $createdAt = '';
    public ?int $createdBy = null;

    public function __construct(private PDO $db)
    {
    }

    public function fill(array $data): Tour
    {
        $this->title = trim((string)($data['title'] ?? $this->title));
        $this->description = trim((string)($data['description'] ?? $this->description));
        $this->basePrice = (float)($data['basePrice'] ?? $this->basePrice);
        $this->numberDay = (int)($data['numberDay'] ?? $data['numberDays'] ?? $this->numberDay);
        $this->categoryId = (int)($data['categoryId'] ?? $this->categoryId);
        $this->createdBy = isset($data['createdBy']) ? (int)$data['createdBy'] : $this->createdBy;
        if (isset($data['locationIds']) && is_array($data['locationIds'])) {
            $this->locationIds = array_values(array_unique(array_filter(array_map('intval', $data['locationIds']), static fn (int $id): bool => $id > 0)));
        }
        return $this;
    }

    private function fillFromDbRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->tourId = (int)($row['tourId'] ?? -1);
        $this->title = (string)($row['title'] ?? '');
        $this->description = (string)($row['description'] ?? '');
        $this->basePrice = (float)($row['basePrice'] ?? 0);
        $this->numberDay = (int)($row['numberDays'] ?? 1);
        $this->categoryId = (int)($row['categoryId'] ?? -1);
        $this->createdAt = (string)($row['createdAt'] ?? '');
        $this->createdBy = isset($row['createdBy']) ? (int)$row['createdBy'] : null;

        $stmt = $this->db->prepare('SELECT locationId FROM Tour_Location WHERE tourId = :tourId ORDER BY locationId');
        $stmt->execute(['tourId' => $this->tourId]);
        $this->locationIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function findTourById(int $tourId): ?Tour
    {
        if ($tourId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM Tours WHERE tourId = :tourId LIMIT 1');
        $stmt->execute(['tourId' => $tourId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromDbRow($row);
        return $this;
    }

    /** @return array<string, mixed>|null */
    public function findPublicDetail(int $tourId): ?array
    {
        $sql = $this->catalogSelect() . ' WHERE t.tourId = :tourId LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tourId' => $tourId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? normalize_db_row($row) : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->search();
    }

    /** @return array<int, array<string, mixed>> */
    public function search(int $categoryId = -1, int $locationId = -1, string $keyword = '', int $limit = 0, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if ($categoryId > 0) {
            $where[] = 't.categoryId = :categoryId';
            $params['categoryId'] = $categoryId;
        }
        if ($locationId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM Tour_Location ftl WHERE ftl.tourId = t.tourId AND ftl.locationId = :locationId)';
            $params['locationId'] = $locationId;
        }
        if ($keyword !== '') {
            $where[] = '(t.title ILIKE :keyword OR t.description ILIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $sql = $this->catalogSelect();
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.createdAt DESC, t.tourId DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }


    public function countSearch(int $categoryId = -1, int $locationId = -1, string $keyword = ''): int
    {
        $where = [];
        $params = [];
        if ($categoryId > 0) {
            $where[] = 't.categoryId = :categoryId';
            $params['categoryId'] = $categoryId;
        }
        if ($locationId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM Tour_Location ftl WHERE ftl.tourId = t.tourId AND ftl.locationId = :locationId)';
            $params['locationId'] = $locationId;
        }
        if ($keyword !== '') {
            $where[] = '(t.title ILIKE :keyword OR t.description ILIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }
        $sql = 'SELECT COUNT(*) FROM Tours t';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function save(): bool
    {
        if ($this->validate([
            'title' => $this->title,
            'basePrice' => $this->basePrice,
            'numberDays' => $this->numberDay,
            'categoryId' => $this->categoryId,
            'locationIds' => $this->locationIds,
        ])) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            if ($this->tourId > 0) {
                $stmt = $this->db->prepare(
                    'UPDATE Tours SET title = :title, description = :description, basePrice = :basePrice,
                                      numberDays = :numberDays, categoryId = :categoryId
                     WHERE tourId = :tourId'
                );
                $ok = $stmt->execute([
                    'tourId' => $this->tourId,
                    'title' => $this->title,
                    'description' => $this->description,
                    'basePrice' => $this->basePrice,
                    'numberDays' => $this->numberDay,
                    'categoryId' => $this->categoryId,
                ]);
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO Tours (title, description, basePrice, numberDays, categoryId, createdBy)
                     VALUES (:title, :description, :basePrice, :numberDays, :categoryId, :createdBy)
                     RETURNING tourId, createdAt'
                );
                $ok = $stmt->execute([
                    'title' => $this->title,
                    'description' => $this->description,
                    'basePrice' => $this->basePrice,
                    'numberDays' => $this->numberDay,
                    'categoryId' => $this->categoryId,
                    'createdBy' => $this->createdBy,
                ]);
                if ($ok) {
                    $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
                    $this->tourId = (int)($created['tourId'] ?? -1);
                    $this->createdAt = (string)($created['createdAt'] ?? '');
                }
            }

            if (!$ok || $this->tourId <= 0) {
                $this->db->rollBack();
                return false;
            }

            $this->syncLocations();
            $this->db->commit();
            return true;
        } catch (Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    private function syncLocations(): void
    {
        $delete = $this->db->prepare('DELETE FROM Tour_Location WHERE tourId = :tourId');
        $delete->execute(['tourId' => $this->tourId]);
        $insert = $this->db->prepare('INSERT INTO Tour_Location (tourId, locationId) VALUES (:tourId, :locationId)');
        foreach ($this->locationIds as $locationId) {
            $insert->execute(['tourId' => $this->tourId, 'locationId' => $locationId]);
        }
    }

    public function delete(): bool
    {
        if ($this->tourId <= 0) {
            return false;
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM Tours WHERE tourId = :tourId');
            return $stmt->execute(['tourId' => $this->tourId]) && $stmt->rowCount() === 1;
        } catch (Throwable) {
            return false;
        }
    }

    public function validate(array $data): array
    {
        $errors = [];
        if (trim((string)($data['title'] ?? '')) === '') {
            $errors['title'] = 'Tiêu đề tour không được để trống.';
        }
        $basePrice = $data['basePrice'] ?? null;
        if (!is_numeric($basePrice) || !is_finite((float)$basePrice) || (float)$basePrice <= 0) {
            $errors['basePrice'] = 'Giá tour phải là một số lớn hơn 0.';
        }
        if ((int)($data['numberDay'] ?? $data['numberDays'] ?? 0) <= 0) {
            $errors['numberDay'] = 'Số ngày phải từ 1 trở lên.';
        }
        if ((int)($data['categoryId'] ?? 0) <= 0) {
            $errors['categoryId'] = 'Vui lòng chọn danh mục tour.';
        }
        if (empty($data['locationIds']) || !is_array($data['locationIds'])) {
            $errors['locationIds'] = 'Vui lòng chọn ít nhất một địa điểm.';
        }
        return $errors;
    }

    /** @return array<int, array<string, mixed>> */
    public function getRelatedTours(int $tourId, int $categoryId, int $limit = 3): array
    {
        if ($tourId <= 0 || $categoryId <= 0 || $limit <= 0) {
            return [];
        }

        $sql = $this->catalogSelect()
            . ' WHERE t.categoryId = :categoryId AND t.tourId <> :tourId'
            . ' ORDER BY t.createdAt DESC, t.tourId DESC LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':tourId', $tourId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM Tours')->fetchColumn();
    }

    public function getTopBookedTours(int $limit = 5): array
    {
        $sql = 'SELECT t.tourId, t.title, t.basePrice, COUNT(b.bookingId) AS totalBooked
                FROM Tours t LEFT JOIN Bookings b ON t.tourId = b.tourId
                GROUP BY t.tourId, t.title, t.basePrice
                ORDER BY totalBooked DESC, t.title ASC LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function catalogSelect(): string
    {
        return <<<'SQL'
SELECT t.tourId, t.title, t.description, t.basePrice, t.numberDays, t.categoryId,
       t.createdAt, t.createdBy, tc.name AS categoryName,
       (SELECT li.imageUrl
          FROM Tour_Location tl_img
          JOIN Location_Images li ON li.locationId = tl_img.locationId
         WHERE tl_img.tourId = t.tourId
         ORDER BY li.imageId ASC LIMIT 1) AS imageUrl,
       (SELECT STRING_AGG(l.locationName, ', ' ORDER BY l.locationName)
          FROM Tour_Location tl_name
          JOIN Locations l ON l.locationId = tl_name.locationId
         WHERE tl_name.tourId = t.tourId) AS locationNames,
       COALESCE((SELECT AVG(r.rating) FROM Review r WHERE r.tourId = t.tourId), 0) AS averageRating,
       (SELECT COUNT(*) FROM Review r2 WHERE r2.tourId = t.tourId) AS reviewCount
  FROM Tours t
  JOIN TourCategory tc ON tc.categoryId = t.categoryId
SQL;
    }
}
