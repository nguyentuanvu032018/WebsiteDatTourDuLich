<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class Promotion
{
    public string $couponCode = '';
    public float $discountValue = 0.0;
    public string $expiryDate = '';
    public ?float $minOrderValue = null;
    public ?int $createdByAdmin = null;
    public string $createdAt = '';

    public function __construct(private PDO $db)
    {
    }

    public function fill(array $data): self
    {
        $this->couponCode = strtoupper(trim((string)($data['couponCode'] ?? $this->couponCode)));
        $this->discountValue = (float)($data['discountValue'] ?? $this->discountValue);
        $this->expiryDate = trim((string)($data['expiryDate'] ?? $this->expiryDate));
        $min = $data['minOrderValue'] ?? $this->minOrderValue;
        $this->minOrderValue = ($min === '' || $min === null) ? null : (float)$min;
        $creator = $data['createdByAdmin'] ?? $data['createdBy'] ?? $this->createdByAdmin;
        $this->createdByAdmin = is_numeric($creator) && (int)$creator > 0 ? (int)$creator : null;
        return $this;
    }

    private function fillFromRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->couponCode = (string)($row['couponCode'] ?? '');
        $this->discountValue = (float)($row['discountValue'] ?? 0);
        $this->expiryDate = (string)($row['expiryDate'] ?? '');
        $this->minOrderValue = isset($row['minOrderValue']) ? (float)$row['minOrderValue'] : null;
        $this->createdByAdmin = !empty($row['createdBy']) ? (int)$row['createdBy'] : null;
        $this->createdAt = (string)($row['createdAt'] ?? '');
    }

    public function validate(array $data, bool $creating = true): array
    {
        $errors = [];
        $code = strtoupper(trim((string)($data['couponCode'] ?? $this->couponCode)));
        $discount = (float)($data['discountValue'] ?? 0);
        $expiry = (string)($data['expiryDate'] ?? '');
        if ($creating && !preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) {
            $errors['couponCode'] = 'Mã giảm giá gồm 3-50 ký tự: chữ in hoa, số, gạch ngang hoặc gạch dưới.';
        }
        if ($discount <= 0) {
            $errors['discountValue'] = 'Giá trị giảm phải lớn hơn 0.';
        }
        if ($expiry === '' || strtotime($expiry) === false) {
            $errors['expiryDate'] = 'Ngày hết hạn không hợp lệ.';
        }
        if (($data['minOrderValue'] ?? '') !== '' && (float)$data['minOrderValue'] < 0) {
            $errors['minOrderValue'] = 'Giá trị đơn tối thiểu không được âm.';
        }
        return $errors;
    }

    public function findByCode(string $couponCode): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM Promotion WHERE couponCode = :couponCode LIMIT 1');
        $stmt->execute(['couponCode' => strtoupper(trim($couponCode))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromRow($row);
        return $this;
    }

    public function all(): array
    {
        return normalize_db_rows($this->db->query(
            'SELECT * FROM Promotion ORDER BY expiryDate DESC, couponCode ASC'
        )->fetchAll(PDO::FETCH_ASSOC));
    }

    public function active(): array
    {
        return normalize_db_rows($this->db->query(
            'SELECT * FROM Promotion WHERE expiryDate >= CURRENT_DATE ORDER BY expiryDate ASC'
        )->fetchAll(PDO::FETCH_ASSOC));
    }

    public function save(): bool
    {
        if ($this->couponCode === '') {
            return false;
        }
        $exists = $this->db->prepare('SELECT 1 FROM Promotion WHERE couponCode = :couponCode');
        $exists->execute(['couponCode' => $this->couponCode]);
        if ($exists->fetchColumn() !== false) {
            $stmt = $this->db->prepare(
                'UPDATE Promotion SET discountValue = :discountValue, expiryDate = :expiryDate,
                 minOrderValue = :minOrderValue WHERE couponCode = :couponCode'
            );
            return $stmt->execute([
                'couponCode' => $this->couponCode,
                'discountValue' => $this->discountValue,
                'expiryDate' => $this->expiryDate,
                'minOrderValue' => $this->minOrderValue,
            ]);
        }
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO Promotion (couponCode, discountValue, expiryDate, minOrderValue, createdBy)
                 VALUES (:couponCode, :discountValue, :expiryDate, :minOrderValue, :createdBy)'
            );
            return $stmt->execute([
                'couponCode' => $this->couponCode,
                'discountValue' => $this->discountValue,
                'expiryDate' => $this->expiryDate,
                'minOrderValue' => $this->minOrderValue,
                'createdBy' => $this->createdByAdmin,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    public function isValid(float $orderTotal): array
    {
        if ($this->couponCode === '') {
            return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại.'];
        }
        if (strtotime($this->expiryDate . ' 23:59:59') < time()) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết hạn.'];
        }
        if ($this->minOrderValue !== null && $orderTotal < $this->minOrderValue) {
            return [
                'valid' => false,
                'message' => 'Đơn hàng cần tối thiểu ' . number_format($this->minOrderValue, 0, ',', '.') . '₫.',
            ];
        }
        return ['valid' => true, 'message' => 'Mã giảm giá hợp lệ.'];
    }

    public function calculateDiscount(float $orderTotal): float
    {
        return min(max(0, $this->discountValue), max(0, $orderTotal));
    }

    public function hasBeenUsedByUser(int $userId): bool
    {
        if ($userId <= 0 || $this->couponCode === '') {
            return false;
        }
        $stmt = $this->db->prepare(
            'SELECT 1
               FROM PromotionUsage
              WHERE userId = :userId
                AND couponCode = :couponCode
              LIMIT 1'
        );
        $stmt->execute([
            'userId' => $userId,
            'couponCode' => $this->couponCode,
        ]);
        return $stmt->fetchColumn() !== false;
    }

    public function recordUsage(int $userId, int $bookingId): bool
    {
        if ($userId <= 0 || $bookingId <= 0 || $this->couponCode === '') {
            return false;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO PromotionUsage (userId, couponCode, bookingId)
             VALUES (:userId, :couponCode, :bookingId)'
        );
        return $stmt->execute([
            'userId' => $userId,
            'couponCode' => $this->couponCode,
            'bookingId' => $bookingId,
        ]);
    }

    public function delete(): bool
    {
        if ($this->couponCode === '') {
            return false;
        }
        try {
            $stmt = $this->db->prepare('DELETE FROM Promotion WHERE couponCode = :couponCode');
            return $stmt->execute(['couponCode' => $this->couponCode]) && $stmt->rowCount() === 1;
        } catch (Throwable) {
            return false;
        }
    }
}
