<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class Booking
{
    public int $bookingId = -1;
    public int $userId = -1;
    public float $totalPrice = 0.0;
    public int $tourId = -1;
    public ?int $scheduleId = null;
    public ?string $couponCode = null;
    public string $status = 'PENDING';
    public string $createdAt = '';
    public ?int $createdBy = null;

    public function __construct(private PDO $db) {}

    public function fill(array $data): self
    {
        $this->bookingId = (int)($data['bookingId'] ?? $this->bookingId);
        $this->userId = (int)($data['userId'] ?? $this->userId);
        $this->totalPrice = (float)($data['totalPrice'] ?? $this->totalPrice);
        $this->tourId = (int)($data['tourId'] ?? $this->tourId);
        $scheduleId = $data['scheduleId'] ?? $this->scheduleId;
        $this->scheduleId = $scheduleId !== null && $scheduleId !== '' ? (int)$scheduleId : null;
        $coupon = strtoupper(trim((string)($data['couponCode'] ?? $this->couponCode ?? '')));
        $this->couponCode = $coupon !== '' ? $coupon : null;
        $status = strtoupper(trim((string)($data['status'] ?? $this->status)));
        $this->status = in_array($status, ['PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED'], true) ? $status : 'PENDING';
        $this->createdBy = isset($data['createdBy']) ? (int)$data['createdBy'] : $this->createdBy;
        return $this;
    }

    private function fillFromDbRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->bookingId = (int)($row['bookingId'] ?? -1);
        $this->userId = (int)($row['userId'] ?? -1);
        $this->totalPrice = (float)($row['totalPrice'] ?? 0);
        $this->tourId = (int)($row['tourId'] ?? -1);
        $this->scheduleId = isset($row['scheduleId']) ? (int)$row['scheduleId'] : null;
        $this->couponCode = !empty($row['couponCode']) ? (string)$row['couponCode'] : null;
        $this->status = (string)($row['status'] ?? 'PENDING');
        $this->createdAt = (string)($row['createdAt'] ?? '');
        $this->createdBy = isset($row['createdBy']) ? (int)$row['createdBy'] : null;
    }

    public function find(int $bookingId): ?self
    {
        if ($bookingId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM Bookings WHERE bookingId = :bookingId LIMIT 1');
        $stmt->execute(['bookingId' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromDbRow($row);
        return $this;
    }

    public function findForUser(int $bookingId, int $userId): ?self
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Bookings WHERE bookingId = :bookingId AND userId = :userId LIMIT 1'
        );
        $stmt->execute(['bookingId' => $bookingId, 'userId' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromDbRow($row);
        return $this;
    }

    public function save(): bool
    {
        if ($this->userId <= 0 || $this->tourId <= 0 || $this->totalPrice < 0) {
            return false;
        }

        if ($this->bookingId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE Bookings
                    SET totalPrice = :totalPrice, scheduleId = :scheduleId, couponCode = :couponCode, status = :status
                  WHERE bookingId = :bookingId'
            );
            return $stmt->execute([
                'bookingId' => $this->bookingId,
                'totalPrice' => $this->totalPrice,
                'scheduleId' => $this->scheduleId,
                'couponCode' => $this->couponCode,
                'status' => $this->status,
            ]);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO Bookings (userId, totalPrice, tourId, scheduleId, couponCode, status, createdBy)
             VALUES (:userId, :totalPrice, :tourId, :scheduleId, :couponCode, :status, :createdBy)
             RETURNING bookingId, createdAt'
        );
        $ok = $stmt->execute([
            'userId' => $this->userId,
            'totalPrice' => $this->totalPrice,
            'tourId' => $this->tourId,
            'scheduleId' => $this->scheduleId,
            'couponCode' => $this->couponCode,
            'status' => $this->status,
            'createdBy' => $this->createdBy ?? $this->userId,
        ]);
        if ($ok) {
            $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->bookingId = (int)($created['bookingId'] ?? -1);
            $this->createdAt = (string)($created['createdAt'] ?? '');
        }
        return $ok && $this->bookingId > 0;
    }

    public function setStatus(string $status): bool
    {
        $status = strtoupper($status);
        if ($this->bookingId <= 0 || !in_array($status, ['PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED'], true)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE Bookings SET status = :status WHERE bookingId = :bookingId');
        $ok = $stmt->execute(['status' => $status, 'bookingId' => $this->bookingId]);
        if ($ok) {
            $this->status = $status;
        }
        return $ok;
    }

    public function cancelForUser(int $bookingId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE Bookings SET status = 'CANCELLED'
              WHERE bookingId = :bookingId AND userId = :userId AND status IN ('PENDING', 'CONFIRMED')"
        );
        return $stmt->execute(['bookingId' => $bookingId, 'userId' => $userId]) && $stmt->rowCount() === 1;
    }

    public function cancelBooking(int $bookingId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE Bookings SET status = 'CANCELLED' WHERE bookingId = :bookingId AND status <> 'CANCELLED'"
        );
        return $stmt->execute(['bookingId' => $bookingId]) && $stmt->rowCount() === 1;
    }


    public function confirmBooking(int $bookingId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE Bookings
         SET status = 'CONFIRMED',
             updatedAt = CURRENT_TIMESTAMP
         WHERE bookingId = :bookingId
           AND status = 'PENDING'"
        );

        $stmt->execute([
            'bookingId' => $bookingId
        ]);

        return $stmt->rowCount() > 0;
    }

    // 

    public function getBookingHistory(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
            b.bookingId AS "bookingId",
            b.status AS "status",
            b.totalPrice AS "totalPrice",
            b.createdAt AS "createdAt",
            t.title AS "tourTitle",
            ts.startDate AS "startDate",
            p.paymentId AS "paymentId",
            p.status AS "paymentStatus"
        FROM Bookings b
        JOIN Tours t
            ON t.tourId = b.tourId
        LEFT JOIN TourSchedules ts
            ON ts.scheduleId = b.scheduleId
        LEFT JOIN Payment p
            ON p.bookingId = b.bookingId
        WHERE b.userId = :userId
        ORDER BY b.createdAt DESC'
        );

        $stmt->execute([
            'userId' => $userId
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare(
            "SELECT b.*, t.title AS tourTitle, t.basePrice, ts.startDate, ts.endDate,
                    COALESCE(p.status, 'UNPAID') AS paymentStatus,
                    (SELECT COUNT(*) FROM BookingDetail bd WHERE bd.bookingId = b.bookingId) AS passengerCount
               FROM Bookings b
               JOIN Tours t ON t.tourId = b.tourId
               LEFT JOIN TourSchedules ts ON ts.scheduleId = b.scheduleId
               LEFT JOIN Payment p ON p.bookingId = b.bookingId
              WHERE b.userId = :userId
              ORDER BY b.createdAt DESC"
        );
        $stmt->execute(['userId' => $userId]);
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getPassengers(int $bookingId = 0): array
    {
        $id = $bookingId > 0 ? $bookingId : $this->bookingId;
        if ($id <= 0) {
            return [];
        }
        $stmt = $this->db->prepare('SELECT * FROM BookingDetail WHERE bookingId = :bookingId ORDER BY detailId');
        $stmt->execute(['bookingId' => $id]);
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM Bookings')->fetchColumn();
    }

    public function getRecentBookings(int $limit = 5): array
    {
        $sql = "SELECT b.bookingId, b.totalPrice, b.status, b.createdAt,
                       u.name AS customerName, t.title AS tourTitle,
                       COALESCE(p.status, 'UNPAID') AS paymentStatus
                  FROM Bookings b
                  JOIN Users u ON u.userId = b.userId
                  JOIN Tours t ON t.tourId = b.tourId
                  LEFT JOIN Payment p ON p.bookingId = b.bookingId
                 ORDER BY b.createdAt DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAllBookingsForAdmin(): array
    {
        $sql = "SELECT b.*, u.name AS customerName, u.email AS customerEmail,
                       u.phoneNumber AS customerPhone, t.title AS tourTitle,
                       ts.startDate, ts.endDate, COALESCE(p.status, 'UNPAID') AS paymentStatus
                  FROM Bookings b
                  JOIN Users u ON u.userId = b.userId
                  JOIN Tours t ON t.tourId = b.tourId
                  LEFT JOIN TourSchedules ts ON ts.scheduleId = b.scheduleId
                  LEFT JOIN Payment p ON p.bookingId = b.bookingId
                 ORDER BY b.createdAt DESC";
        return normalize_db_rows($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getBookingDetailForAdmin(int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, u.name AS customerName, u.email AS customerEmail, u.phoneNumber AS customerPhone,
                    c.address, t.title AS tourTitle, ts.startDate, ts.endDate,
                    p.paymentId, p.paymentMethod, p.transactionId, p.status AS paymentStatus, p.amount
               FROM Bookings b
               JOIN Users u ON u.userId = b.userId
               JOIN Customer c ON c.userId = b.userId
               JOIN Tours t ON t.tourId = b.tourId
               LEFT JOIN TourSchedules ts ON ts.scheduleId = b.scheduleId
               LEFT JOIN Payment p ON p.bookingId = b.bookingId
              WHERE b.bookingId = :bookingId LIMIT 1"
        );
        $stmt->execute(['bookingId' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $detail = normalize_db_row($row);
        $detail['passengers'] = $this->getPassengers($bookingId);
        return $detail;
    }
}
