<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class TourSchedule
{
    public int $scheduleId = -1;
    public string $startDate = '';
    public string $endDate = '';
    public int $maxParticipants = 0;
    public int $tourId = -1;
    public string $createdAt = '';
    public ?int $createdBy = null;

    public function __construct(private PDO $db)
    {
    }

    public function fill(array $data): self
    {
        $this->scheduleId = (int)($data['scheduleId'] ?? $this->scheduleId);
        $this->startDate = (string)($data['startDate'] ?? $this->startDate);
        $this->endDate = (string)($data['endDate'] ?? $this->endDate);
        $this->maxParticipants = (int)($data['maxParticipants'] ?? $this->maxParticipants);
        $this->tourId = (int)($data['tourId'] ?? $this->tourId);
        $this->createdBy = isset($data['createdBy']) ? (int)$data['createdBy'] : $this->createdBy;
        return $this;
    }

    private function fillFromRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->scheduleId = (int)($row['scheduleId'] ?? -1);
        $this->startDate = (string)($row['startDate'] ?? '');
        $this->endDate = (string)($row['endDate'] ?? '');
        $this->maxParticipants = (int)($row['maxParticipants'] ?? 0);
        $this->tourId = (int)($row['tourId'] ?? -1);
        $this->createdAt = (string)($row['createdAt'] ?? '');
        $this->createdBy = isset($row['createdBy']) ? (int)$row['createdBy'] : null;
    }

    public function findTourSchedule(int $scheduleId): ?self
    {
        $stmt = $this->db->prepare('SELECT * FROM TourSchedules WHERE scheduleId = :scheduleId LIMIT 1');
        $stmt->execute(['scheduleId' => $scheduleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromRow($row);
        return $this;
    }

    public function belongsToTour(int $scheduleId, int $tourId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM TourSchedules WHERE scheduleId = :scheduleId AND tourId = :tourId AND startDate >= CURRENT_DATE'
        );
        $stmt->execute(['scheduleId' => $scheduleId, 'tourId' => $tourId]);
        return $stmt->fetchColumn() !== false;
    }

    public function lockAndGetAvailableSlots(int $scheduleId, int $tourId): ?int
    {
        $schedule = $this->db->prepare(
            'SELECT maxParticipants FROM TourSchedules
             WHERE scheduleId = :scheduleId AND tourId = :tourId AND startDate >= CURRENT_DATE
             FOR UPDATE'
        );
        $schedule->execute(['scheduleId' => $scheduleId, 'tourId' => $tourId]);
        $maximum = $schedule->fetchColumn();
        if ($maximum === false) {
            return null;
        }

        $booked = $this->db->prepare(
            "SELECT COUNT(bd.detailId)
               FROM Bookings b
               JOIN BookingDetail bd ON bd.bookingId = b.bookingId
              WHERE b.scheduleId = :scheduleId AND b.status <> 'CANCELLED'"
        );
        $booked->execute(['scheduleId' => $scheduleId]);
        return max(0, (int)$maximum - (int)$booked->fetchColumn());
    }

    public function getSchedulesByTourId(int $tourId, bool $futureOnly = true): array
    {
        $sql = "SELECT ts.*,
                       GREATEST(ts.maxParticipants - COALESCE((
                          SELECT SUM((SELECT COUNT(*) FROM BookingDetail bd WHERE bd.bookingId = b.bookingId))
                          FROM Bookings b
                          WHERE b.scheduleId = ts.scheduleId AND b.status <> 'CANCELLED'
                       ), 0), 0) AS availableSlots
                  FROM TourSchedules ts
                 WHERE ts.tourId = :tourId";
        if ($futureOnly) {
            $sql .= ' AND ts.startDate >= CURRENT_DATE';
        }
        $sql .= ' ORDER BY ts.startDate ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tourId' => $tourId]);
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function validate(array $data): array
    {
        $errors = [];
        $start = (string)($data['startDate'] ?? '');
        $end = (string)($data['endDate'] ?? '');
        if ($start === '' || strtotime($start) === false) {
            $errors['startDate'] = 'Ngày bắt đầu không hợp lệ.';
        }
        if ($end === '' || strtotime($end) === false || ($start !== '' && strtotime($end) < strtotime($start))) {
            $errors['endDate'] = 'Ngày kết thúc phải từ ngày bắt đầu trở đi.';
        }
        if ((int)($data['maxParticipants'] ?? 0) <= 0) {
            $errors['maxParticipants'] = 'Số khách tối đa phải lớn hơn 0.';
        }
        if ((int)($data['tourId'] ?? 0) <= 0) {
            $errors['tourId'] = 'Tour không hợp lệ.';
        }
        return $errors;
    }

    public function save(): bool
    {
        if ($this->validate([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'maxParticipants' => $this->maxParticipants,
            'tourId' => $this->tourId,
        ])) {
            return false;
        }
        if ($this->scheduleId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE TourSchedules SET startDate = :startDate, endDate = :endDate,
                 maxParticipants = :maxParticipants, tourId = :tourId WHERE scheduleId = :scheduleId'
            );
            return $stmt->execute([
                'scheduleId' => $this->scheduleId,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'maxParticipants' => $this->maxParticipants,
                'tourId' => $this->tourId,
            ]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO TourSchedules (startDate, endDate, maxParticipants, tourId, createdBy)
             VALUES (:startDate, :endDate, :maxParticipants, :tourId, :createdBy)
             RETURNING scheduleId, createdAt'
        );
        $ok = $stmt->execute([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'maxParticipants' => $this->maxParticipants,
            'tourId' => $this->tourId,
            'createdBy' => $this->createdBy,
        ]);
        if ($ok) {
            $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->scheduleId = (int)($created['scheduleId'] ?? -1);
            $this->createdAt = (string)($created['createdAt'] ?? '');
        }
        return $ok;
    }

    public function delete(): bool
    {
        if ($this->scheduleId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM TourSchedules WHERE scheduleId = :scheduleId');
        return $stmt->execute(['scheduleId' => $this->scheduleId]) && $stmt->rowCount() === 1;
    }
}
