<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class ContactMessage
{
    public int $contactId = -1;
    public string $fullName = '';
    public string $contactMethod = 'email';
    public string $email = '';
    public string $phoneNumber = '';
    public string $message = '';
    public string $status = 'NEW';
    public string $createdAt = '';
    public ?int $customerUserId = null;

    public function __construct(private PDO $db)
    {
    }

    public function fill(array $data): self
    {
        $this->fullName = trim((string)($data['fullName'] ?? $data['fullname'] ?? $this->fullName));
        $this->contactMethod = (string)($data['contactMethod'] ?? $this->contactMethod);
        $this->email = trim((string)($data['email'] ?? $this->email));
        $this->phoneNumber = trim((string)($data['phoneNumber'] ?? $data['phone'] ?? $this->phoneNumber));
        $this->message = trim((string)($data['message'] ?? $this->message));
        return $this;
    }

    public function validate(): array
    {
        $errors = [];
        if (mb_strlen($this->fullName) < 2 || mb_strlen($this->fullName) > 150) {
            $errors['fullName'] = 'Họ tên phải có từ 2 đến 150 ký tự.';
        }
        if (!in_array($this->contactMethod, ['email', 'phone'], true)) {
            $errors['contactMethod'] = 'Hình thức liên hệ không hợp lệ.';
        }
        if ($this->contactMethod === 'email' && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Địa chỉ email không hợp lệ.';
        }
        if ($this->contactMethod === 'phone' && !preg_match('/^[0-9+() .-]{8,20}$/', $this->phoneNumber)) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        }
        if (mb_strlen($this->message) < 10 || mb_strlen($this->message) > 3000) {
            $errors['message'] = 'Nội dung phải có từ 10 đến 3000 ký tự.';
        }
        return $errors;
    }

    public function save(): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ContactMessages
                (fullName, contactMethod, email, phoneNumber, message, status, customerUserId)
             VALUES
                (:fullName, :contactMethod, :email, :phoneNumber, :message, :status, :customerUserId)
             RETURNING contactId, createdAt'
        );
        $result = $stmt->execute([
            'fullName' => $this->fullName,
            'contactMethod' => $this->contactMethod,
            'email' => $this->email !== '' ? $this->email : null,
            'phoneNumber' => $this->phoneNumber !== '' ? $this->phoneNumber : null,
            'message' => $this->message,
            'status' => $this->status,
            'customerUserId' => $this->customerUserId,
        ]);
        if ($result) {
            $row = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->contactId = (int)($row['contactId'] ?? -1);
            $this->createdAt = (string)($row['createdAt'] ?? '');
        }
        return $result;
    }

    public function all(): array
    {
        return normalize_db_rows($this->db->query(
            'SELECT * FROM ContactMessages ORDER BY createdAt DESC'
        )->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $contactId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ContactMessages WHERE contactId = :contactId LIMIT 1');
        $stmt->execute(['contactId' => $contactId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? normalize_db_row($row) : null;
    }

    public function findForCustomer(int $contactId, int $customerUserId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ContactMessages
              WHERE contactId = :contactId AND customerUserId = :customerUserId
              LIMIT 1'
        );
        $stmt->execute(['contactId' => $contactId, 'customerUserId' => $customerUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? normalize_db_row($row) : null;
    }

    public function forCustomer(int $customerUserId): array
    {
        $stmt = $this->db->prepare(
            "SELECT cm.*,
                    (SELECT COUNT(*) FROM ContactReplies cr
                      WHERE cr.contactId = cm.contactId
                        AND cr.senderRole = 'admin' AND cr.isRead = FALSE) AS unreadCount,
                    (SELECT MAX(cr2.createdAt) FROM ContactReplies cr2
                      WHERE cr2.contactId = cm.contactId) AS lastReplyAt
               FROM ContactMessages cm
              WHERE cm.customerUserId = :customerUserId
              ORDER BY COALESCE(
                  (SELECT MAX(cr3.createdAt) FROM ContactReplies cr3
                    WHERE cr3.contactId = cm.contactId),
                  cm.createdAt
              ) DESC"
        );
        $stmt->execute(['customerUserId' => $customerUserId]);
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function reopen(int $contactId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE ContactMessages SET status = 'NEW', resolvedAt = NULL WHERE contactId = :contactId"
        );
        $stmt->execute(['contactId' => $contactId]);
    }

    public function markResolved(int $contactId): bool
    {
        $stmt = $this->db->prepare("UPDATE ContactMessages SET status = 'RESOLVED', resolvedAt = CURRENT_TIMESTAMP WHERE contactId = :contactId");
        return $stmt->execute(['contactId' => $contactId]) && $stmt->rowCount() === 1;
    }

    public function delete(int $contactId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ContactMessages WHERE contactId = :contactId');
        return $stmt->execute(['contactId' => $contactId]) && $stmt->rowCount() === 1;
    }
}
