<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class Location
{
    public int $locationId = -1;
    public string $locationName = '';
    public string $city = '';
    /** @var array<int, array<string, mixed>> */
    public array $images = [];
    public string $createdAt = '';
    public ?int $createdBy = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function fill(array $data): Location
    {
        $this->locationName = trim((string)($data['locationName'] ?? $this->locationName));
        $this->city = trim((string)($data['city'] ?? $this->city));
        $this->createdBy = isset($data['createdBy']) ? (int)$data['createdBy'] : $this->createdBy;
        return $this;
    }

    private function fillFromDbRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->locationId = (int)($row['locationId'] ?? -1);
        $this->locationName = (string)($row['locationName'] ?? '');
        $this->city = (string)($row['city'] ?? '');
        $this->createdAt = (string)($row['createdAt'] ?? '');
        $this->createdBy = isset($row['createdBy']) ? (int)$row['createdBy'] : null;
        $this->images = $this->getImages();
    }

    public function findLocationById(int $locationId): ?Location
    {
        if ($locationId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM Locations WHERE locationId = :locationId LIMIT 1');
        $stmt->execute(['locationId' => $locationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $this->fillFromDbRow($row);
        return $this;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = 'SELECT l.locationId, l.locationName, l.city, l.createdAt, l.createdBy,
                       MIN(li.imageUrl) AS firstImage,
                       COUNT(li.imageId) AS imageCount
                FROM Locations l
                LEFT JOIN Location_Images li ON li.locationId = l.locationId
                GROUP BY l.locationId, l.locationName, l.city, l.createdAt, l.createdBy
                ORDER BY l.locationId DESC';
        return normalize_db_rows($this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<int, array<string, mixed>> */
    public function popular(int $limit = 6): array
    {
        $sql = 'SELECT l.locationId, l.locationName, l.city, l.createdAt,
                       MIN(li.imageUrl) AS firstImage,
                       COUNT(DISTINCT li.imageId) AS imageCount,
                       COUNT(DISTINCT tl.tourId) AS tourCount
                FROM Locations l
                LEFT JOIN Location_Images li ON li.locationId = l.locationId
                LEFT JOIN Tour_Location tl ON tl.locationId = l.locationId
                GROUP BY l.locationId, l.locationName, l.city, l.createdAt
                ORDER BY COUNT(DISTINCT tl.tourId) DESC,
                         COUNT(DISTINCT li.imageId) DESC,
                         l.locationName ASC
                LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function save(): bool
    {
        if ($this->locationName === '' || $this->city === '') {
            return false;
        }

        if ($this->locationId > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE Locations SET locationName = :locationName, city = :city WHERE locationId = :locationId'
            );
            return $stmt->execute([
                'locationName' => $this->locationName,
                'city' => $this->city,
                'locationId' => $this->locationId,
            ]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO Locations (locationName, city, createdBy)
             VALUES (:locationName, :city, :createdBy)
             RETURNING locationId, createdAt'
        );
        $result = $stmt->execute([
            'locationName' => $this->locationName,
            'city' => $this->city,
            'createdBy' => $this->createdBy,
        ]);
        if ($result) {
            $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->locationId = (int)($created['locationId'] ?? -1);
            $this->createdAt = (string)($created['createdAt'] ?? '');
        }
        return $result;
    }

    public function addImageUrl(string $imageUrl): bool
    {
        $imageUrl = trim($imageUrl);
        if ($this->locationId <= 0 || $imageUrl === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO Location_Images (locationId, imageUrl)
             VALUES (:locationId, :imageUrl)
             RETURNING imageId, imageUrl, createdAt'
        );
        $result = $stmt->execute(['locationId' => $this->locationId, 'imageUrl' => $imageUrl]);
        if ($result) {
            $this->images[] = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
        }
        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function getImages(): array
    {
        if ($this->locationId <= 0) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT imageId, locationId, imageUrl, createdAt
             FROM Location_Images WHERE locationId = :locationId ORDER BY imageId DESC'
        );
        $stmt->execute(['locationId' => $this->locationId]);
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findImage(int $imageId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT imageId, locationId, imageUrl, createdAt
             FROM Location_Images WHERE imageId = :imageId AND locationId = :locationId LIMIT 1'
        );
        $stmt->execute(['imageId' => $imageId, 'locationId' => $this->locationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? normalize_db_row($row) : null;
    }

    public function deleteImage(int $imageId): bool
    {
        if ($this->locationId <= 0 || $imageId <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'DELETE FROM Location_Images WHERE imageId = :imageId AND locationId = :locationId'
        );
        return $stmt->execute(['imageId' => $imageId, 'locationId' => $this->locationId])
            && $stmt->rowCount() === 1;
    }

    public function delete(): bool
    {
        if ($this->locationId <= 0) {
            return false;
        }
        try {
            $stmt = $this->pdo->prepare('DELETE FROM Locations WHERE locationId = :locationId');
            return $stmt->execute(['locationId' => $this->locationId]) && $stmt->rowCount() === 1;
        } catch (Throwable) {
            return false;
        }
    }

    public function validate(array $data): array
    {
        $errors = [];
        if (trim((string)($data['locationName'] ?? '')) === '') {
            $errors['locationName'] = 'Tên địa điểm không được để trống.';
        }
        if (trim((string)($data['city'] ?? '')) === '') {
            $errors['city'] = 'Tên tỉnh hoặc thành phố không được để trống.';
        }
        return $errors;
    }
}
