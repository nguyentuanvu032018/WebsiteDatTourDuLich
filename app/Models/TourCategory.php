<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class TourCategory
{
    public int $categoryId = -1;
    public string $name = '';
    public string $description = '';
    public string $createdAt = '';
    public ?int $createdBy = null;

    public function __construct(private PDO $db)
    {
    }

    public function fill(array $data): self
    {
        $this->name = trim((string)($data['name'] ?? $this->name));
        $this->description = trim((string)($data['description'] ?? $this->description));
        $this->createdBy = isset($data['createdBy']) ? (int)$data['createdBy'] : $this->createdBy;
        return $this;
    }

    private function fillFromRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->categoryId = (int)($row['categoryId'] ?? -1);
        $this->name = (string)($row['name'] ?? '');
        $this->description = (string)($row['description'] ?? '');
        $this->createdAt = (string)($row['createdAt'] ?? '');
        $this->createdBy = isset($row['createdBy']) ? (int)$row['createdBy'] : null;
    }

    public function all(): array
    {
        return normalize_db_rows($this->db->query(
            'SELECT tc.*, (SELECT COUNT(*) FROM Tours t WHERE t.categoryId = tc.categoryId) AS tourCount
             FROM TourCategory tc ORDER BY tc.name'
        )->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $categoryId): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM TourCategory WHERE categoryId = :categoryId LIMIT 1');
        $stmt->execute(['categoryId' => $categoryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromRow($row);
        return $this;
    }

    public function save(): bool
    {
        if ($this->name === '') {
            return false;
        }
        if ($this->categoryId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE TourCategory SET name = :name, description = :description WHERE categoryId = :categoryId'
            );
            return $stmt->execute([
                'categoryId' => $this->categoryId,
                'name' => $this->name,
                'description' => $this->description,
            ]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO TourCategory (name, description, createdBy)
             VALUES (:name, :description, :createdBy) RETURNING categoryId, createdAt'
        );
        $ok = $stmt->execute([
            'name' => $this->name,
            'description' => $this->description,
            'createdBy' => $this->createdBy,
        ]);
        if ($ok) {
            $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->categoryId = (int)($created['categoryId'] ?? -1);
            $this->createdAt = (string)($created['createdAt'] ?? '');
        }
        return $ok;
    }

    public function delete(int $categoryId = 0): array
    {
        $id = $categoryId > 0 ? $categoryId : $this->categoryId;
        try {
            $stmt = $this->db->prepare('DELETE FROM TourCategory WHERE categoryId = :categoryId');
            $stmt->execute(['categoryId' => $id]);
            return $stmt->rowCount() === 1
                ? ['success' => true, 'message' => 'Đã xóa danh mục.']
                : ['success' => false, 'message' => 'Danh mục không tồn tại.'];
        } catch (Throwable) {
            return ['success' => false, 'message' => 'Không thể xóa danh mục đang được sử dụng bởi tour.'];
        }
    }
}
