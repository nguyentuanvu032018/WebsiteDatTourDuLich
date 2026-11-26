<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class Customer extends User
{
    public string $address = '';
    public array $tourIds = [];

    public function fill(array $data): self
    {
        parent::fill($data);
        $this->address = trim((string)($data['address'] ?? $this->address));
        return $this;
    }

    protected function fillFromDbRow(array $row): void
    {
        parent::fillFromDbRow($row);
        $normalized = normalize_db_row($row);
        $this->address = (string)($normalized['address'] ?? '');
    }

    public function findByUserId(int $userId): ?self
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, c.address FROM Users u JOIN Customer c ON c.userId = u.userId
             WHERE u.userId = :userId LIMIT 1'
        );
        $stmt->execute(['userId' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromDbRow($row);
        return $this;
    }

    public function save(): bool
    {
        $ownsTransaction = !$this->db->inTransaction();
        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }
            if (!parent::save() || $this->userId <= 0) {
                if ($ownsTransaction && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }
            $stmt = $this->db->prepare(
                'INSERT INTO Customer (userId, address) VALUES (:userId, :address)
                 ON CONFLICT (userId) DO UPDATE SET address = EXCLUDED.address'
            );
            $ok = $stmt->execute(['userId' => $this->userId, 'address' => $this->address]);
            if ($ownsTransaction) {
                $ok ? $this->db->commit() : $this->db->rollBack();
            }
            return $ok;
        } catch (Throwable) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM Customer')->fetchColumn();
    }

    public function getAllCustomersForAdmin(): array
    {
        $sql = "SELECT u.userId, u.name, u.email, u.phoneNumber, u.createdAt, c.address,
                       (SELECT COUNT(*) FROM Bookings b WHERE b.userId = u.userId) AS bookingCount,
                       COALESCE((SELECT SUM(b.totalPrice) FROM Bookings b WHERE b.userId = u.userId AND b.status <> 'CANCELLED'), 0) AS totalSpent
                  FROM Customer c JOIN Users u ON u.userId = c.userId
                 ORDER BY u.createdAt DESC";
        return normalize_db_rows($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    }

    public function deleteAccount(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Users WHERE userId = :userId');
        return $stmt->execute(['userId' => $userId]) && $stmt->rowCount() === 1;
    }
}
