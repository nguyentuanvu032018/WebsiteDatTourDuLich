<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class ContactReply
{
    public function __construct(private PDO $db)
    {
    }

    public function create(int $contactId, int $senderUserId, string $senderRole, string $message): bool
    {
        $message = trim($message);
        if ($contactId <= 0 || $senderUserId <= 0
            || !in_array($senderRole, ['admin', 'customer'], true)
            || mb_strlen($message) < 2 || mb_strlen($message) > 3000) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO ContactReplies (contactId, senderUserId, senderRole, message)
             VALUES (:contactId, :senderUserId, :senderRole, :message)'
        );
        return $stmt->execute([
            'contactId' => $contactId,
            'senderUserId' => $senderUserId,
            'senderRole' => $senderRole,
            'message' => $message,
        ]);
    }

    public function forContact(int $contactId): array
    {
        $stmt = $this->db->prepare(
            'SELECT cr.*, u.name AS senderName
               FROM ContactReplies cr
               LEFT JOIN Users u ON u.userId = cr.senderUserId
              WHERE cr.contactId = :contactId
              ORDER BY cr.createdAt ASC, cr.replyId ASC'
        );
        $stmt->execute(['contactId' => $contactId]);
        return normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function markAdminRepliesRead(int $contactId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE ContactReplies
                SET isRead = TRUE
              WHERE contactId = :contactId AND senderRole = 'admin' AND isRead = FALSE"
        );
        $stmt->execute(['contactId' => $contactId]);
    }

    public function markCustomerRepliesRead(int $contactId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE ContactReplies
                SET isRead = TRUE
              WHERE contactId = :contactId AND senderRole = 'customer' AND isRead = FALSE"
        );
        $stmt->execute(['contactId' => $contactId]);
    }

    public function countUnreadForCustomer(int $customerUserId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
               FROM ContactReplies cr
               JOIN ContactMessages cm ON cm.contactId = cr.contactId
              WHERE cm.customerUserId = :customerUserId
                AND cr.senderRole = 'admin'
                AND cr.isRead = FALSE"
        );
        $stmt->execute(['customerUserId' => $customerUserId]);
        return (int)$stmt->fetchColumn();
    }
}
