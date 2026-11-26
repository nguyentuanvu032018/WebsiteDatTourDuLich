<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class BookingDetail
{
    public int $detailId = -1;
    public string $fullName = '';
    public string $phoneNumber = '';
    public string $relationship = '';
    public int $bookingId = -1;

    public function __construct(private PDO $db)
    {
    }

    public function fill(array $data): self
    {
        $this->detailId = (int)($data['detailId'] ?? $this->detailId);
        $this->fullName = trim((string)($data['fullName'] ?? $this->fullName));
        $this->phoneNumber = trim((string)($data['phoneNumber'] ?? $this->phoneNumber));
        $this->relationship = trim((string)($data['relationship'] ?? $this->relationship));
        $this->bookingId = (int)($data['bookingId'] ?? $this->bookingId);
        return $this;
    }

    public function validate(): array
    {
        $errors = [];
        if (mb_strlen($this->fullName) < 2 || mb_strlen($this->fullName) > 100) {
            $errors['fullName'] = 'Họ tên hành khách phải có từ 2 đến 100 ký tự.';
        }
        if ($this->phoneNumber !== '' && !preg_match('/^[0-9+() .-]{8,20}$/', $this->phoneNumber)) {
            $errors['phoneNumber'] = 'Số điện thoại hành khách không hợp lệ.';
        }
        if ($this->bookingId <= 0) {
            $errors['bookingId'] = 'Đơn đặt tour không hợp lệ.';
        }
        return $errors;
    }

    public function save(): bool
    {
        if ($this->validate()) {
            return false;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO BookingDetail (fullName, phoneNumber, relationship, bookingId)
             VALUES (:fullName, :phoneNumber, :relationship, :bookingId)
             RETURNING detailId'
        );
        $ok = $stmt->execute([
            'fullName' => $this->fullName,
            'phoneNumber' => $this->phoneNumber !== '' ? $this->phoneNumber : null,
            'relationship' => $this->relationship !== '' ? $this->relationship : null,
            'bookingId' => $this->bookingId,
        ]);
        if ($ok) {
            $this->detailId = (int)$stmt->fetchColumn();
        }
        return $ok;
    }
}
