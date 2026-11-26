<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Review
{
    public int $reviewId = -1;
    public int $rating = 5;
    public string $comment = '';
    public int $customerUserId = -1;
    public int $tourId = -1;
    public string $createdAt = '';

    public function __construct(private PDO $db)
    {
    }

    public function fill(array $data): self
    {
        $this->reviewId = (int)($data['reviewId'] ?? $this->reviewId);
        $this->rating = (int)($data['rating'] ?? $this->rating);
        $this->comment = trim((string)($data['comment'] ?? $this->comment));
        $this->customerUserId = (int)($data['customerUserId'] ?? $this->customerUserId);
        $this->tourId = (int)($data['tourId'] ?? $this->tourId);
        return $this;
    }

    private function fillFromRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->reviewId = (int)($row['reviewId'] ?? -1);
        $this->rating = (int)($row['rating'] ?? 5);
        $this->comment = (string)($row['comment'] ?? '');
        $this->customerUserId = (int)($row['customerUserId'] ?? -1);
        $this->tourId = (int)($row['tourId'] ?? -1);
        $this->createdAt = (string)($row['createdAt'] ?? '');
    }

    public function validate(): array
    {
        $errors = [];
        if ($this->rating < 1 || $this->rating > 5) {
            $errors['rating'] = 'Điểm đánh giá phải từ 1 đến 5.';
        }
        if (mb_strlen($this->comment) > 2000) {
            $errors['comment'] = 'Nội dung đánh giá tối đa 2000 ký tự.';
        }
        if ($this->customerUserId <= 0 || $this->tourId <= 0) {
            $errors['review'] = 'Thông tin đánh giá không hợp lệ.';
        }
        return $errors;
    }

    public function find(int $reviewId): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM Review WHERE reviewId = :reviewId LIMIT 1');
        $stmt->execute(['reviewId' => $reviewId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromRow($row);
        return $this;
    }

    public function save(): bool
    {
        if ($this->validate()) {
            return false;
        }
        if ($this->reviewId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE Review SET rating = :rating, comment = :comment WHERE reviewId = :reviewId'
            );
            return $stmt->execute([
                'reviewId' => $this->reviewId,
                'rating' => $this->rating,
                'comment' => $this->comment,
            ]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO Review (rating, comment, customerUserId, tourId)
             VALUES (:rating, :comment, :customerUserId, :tourId) RETURNING reviewId, createdAt'
        );
        $ok = $stmt->execute([
            'rating' => $this->rating,
            'comment' => $this->comment,
            'customerUserId' => $this->customerUserId,
            'tourId' => $this->tourId,
        ]);
        if ($ok) {
            $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->reviewId = (int)($created['reviewId'] ?? -1);
            $this->createdAt = (string)($created['createdAt'] ?? '');
        }
        return $ok;
    }

    public function getByTourId(int $tourId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.name AS customerName
             FROM Review r JOIN Users u ON u.userId = r.customerUserId
             WHERE r.tourId = :tourId ORDER BY r.createdAt DESC'
        );
        $stmt->execute(['tourId' => $tourId]);
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAverageRating(int $tourId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(AVG(rating), 0) FROM Review WHERE tourId = :tourId');
        $stmt->execute(['tourId' => $tourId]);
        return round((float)$stmt->fetchColumn(), 1);
    }

    public function delete(): bool
    {
        if ($this->reviewId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM Review WHERE reviewId = :reviewId');
        return $stmt->execute(['reviewId' => $this->reviewId]) && $stmt->rowCount() === 1;
    }

    public function deleteForUser(int $reviewId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM Review WHERE reviewId = :reviewId AND customerUserId = :userId'
        );
        return $stmt->execute(['reviewId' => $reviewId, 'userId' => $userId]) && $stmt->rowCount() === 1;
    }

    public function getAllReviewsForAdmin(): array
    {
        $sql = 'SELECT r.*, u.name AS customerName, u.email AS customerEmail, t.title AS tourTitle
                FROM Review r
                JOIN Users u ON u.userId = r.customerUserId
                JOIN Tours t ON t.tourId = r.tourId
                ORDER BY r.createdAt DESC';
        return normalize_db_rows($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getReviewDetailForAdmin(int $reviewId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.name AS customerName, u.email AS customerEmail, t.title AS tourTitle
             FROM Review r
             JOIN Users u ON u.userId = r.customerUserId
             JOIN Tours t ON t.tourId = r.tourId
             WHERE r.reviewId = :reviewId LIMIT 1'
        );
        $stmt->execute(['reviewId' => $reviewId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? normalize_db_row($row) : null;
    }
}
