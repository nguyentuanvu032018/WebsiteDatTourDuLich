<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Payment
{
    public int $paymentId = -1;
    public string $paymentMethod = 'BANK_TRANSFER';
    public float $amount = 0.0;
    public string $transactionId = '';
    public string $status = 'PENDING';
    public int $bookingId = -1;
    public string $createdAt = '';

    public function __construct(private PDO $db) {}

    public function fill(array $data): self
    {
        $this->paymentId = (int)($data['paymentId'] ?? $this->paymentId);
        $method = strtoupper(trim((string)($data['paymentMethod'] ?? $this->paymentMethod)));
        $this->paymentMethod = in_array($method, ['BANK_TRANSFER', 'CASH', 'MOMO', 'VNPAY'], true) ? $method : 'BANK_TRANSFER';
        $this->amount = (float)($data['amount'] ?? $this->amount);
        $this->transactionId = trim((string)($data['transactionId'] ?? $this->transactionId));
        $status = strtoupper(trim((string)($data['status'] ?? $this->status)));
        $this->status = in_array($status, ['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED', 'REFUNDED'], true) ? $status : 'PENDING';
        $this->bookingId = (int)($data['bookingId'] ?? $this->bookingId);
        return $this;
    }

    private function fillFromRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->paymentId = (int)($row['paymentId'] ?? -1);
        $this->paymentMethod = (string)($row['paymentMethod'] ?? 'BANK_TRANSFER');
        $this->amount = (float)($row['amount'] ?? 0);
        $this->transactionId = (string)($row['transactionId'] ?? '');
        $this->status = (string)($row['status'] ?? 'PENDING');
        $this->bookingId = (int)($row['bookingId'] ?? -1);
        $this->createdAt = (string)($row['createdAt'] ?? '');
    }

    public function find(int $paymentId): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM Payment WHERE paymentId = :paymentId LIMIT 1');
        $stmt->execute(['paymentId' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromRow($row);
        return $this;
    }

    public function findByBookingId(int $bookingId): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM Payment WHERE bookingId = :bookingId LIMIT 1');
        $stmt->execute(['bookingId' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromRow($row);
        return $this;
    }

    public function save(): bool
    {
        if ($this->bookingId <= 0 || $this->amount < 0) {
            return false;
        }
        if ($this->paymentId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE Payment SET paymentMethod = :paymentMethod, amount = :amount,
                 transactionId = :transactionId, status = :status WHERE paymentId = :paymentId'
            );
            return $stmt->execute([
                'paymentId' => $this->paymentId,
                'paymentMethod' => $this->paymentMethod,
                'amount' => $this->amount,
                'transactionId' => $this->transactionId !== '' ? $this->transactionId : null,
                'status' => $this->status,
            ]);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO Payment (paymentMethod, amount, transactionId, status, bookingId)
             VALUES (:paymentMethod, :amount, :transactionId, :status, :bookingId)
             ON CONFLICT (bookingId) DO UPDATE SET
               paymentMethod = EXCLUDED.paymentMethod,
               amount = EXCLUDED.amount,
               transactionId = EXCLUDED.transactionId,
               status = EXCLUDED.status
             RETURNING paymentId, createdAt'
        );
        $ok = $stmt->execute([
            'paymentMethod' => $this->paymentMethod,
            'amount' => $this->amount,
            'transactionId' => $this->transactionId !== '' ? $this->transactionId : null,
            'status' => $this->status,
            'bookingId' => $this->bookingId,
        ]);
        if ($ok) {
            $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->paymentId = (int)($created['paymentId'] ?? -1);
            $this->createdAt = (string)($created['createdAt'] ?? '');
        }
        return $ok;
    }

    public function allForAdmin(): array
    {
        $sql = "SELECT p.*, b.status AS bookingStatus, u.name AS customerName, u.email AS customerEmail,
                       t.title AS tourTitle
                  FROM Payment p
                  JOIN Bookings b ON b.bookingId = p.bookingId
                  JOIN Users u ON u.userId = b.userId
                  JOIN Tours t ON t.tourId = b.tourId
                 ORDER BY p.createdAt DESC";
        return normalize_db_rows($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    public function detailForAdmin(int $paymentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, b.totalPrice, b.status AS bookingStatus, b.createdAt AS bookingCreatedAt,
                    u.name AS customerName, u.email AS customerEmail, u.phoneNumber AS customerPhone,
                    t.title AS tourTitle
               FROM Payment p
               JOIN Bookings b ON b.bookingId = p.bookingId
               JOIN Users u ON u.userId = b.userId
               JOIN Tours t ON t.tourId = b.tourId
              WHERE p.paymentId = :paymentId LIMIT 1"
        );
        $stmt->execute(['paymentId' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? normalize_db_row($row) : null;
    }

    public function getTotalRevenue(): float
    {
        return (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM Payment WHERE status = 'SUCCESS'")->fetchColumn();
    }

    public function getMonthlyRevenueLast6Months(): array
    {
        $sql = "SELECT TO_CHAR(months.month, 'MM/YYYY') AS month,
                       COALESCE(SUM(p.amount), 0) AS monthly_total
                  FROM generate_series(
                        date_trunc('month', CURRENT_DATE) - interval '5 months',
                        date_trunc('month', CURRENT_DATE), interval '1 month'
                       ) AS months(month)
                  LEFT JOIN Payment p ON date_trunc('month', p.createdAt) = months.month AND p.status = 'SUCCESS'
                 GROUP BY months.month ORDER BY months.month";
        return normalize_db_rows($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }
}
