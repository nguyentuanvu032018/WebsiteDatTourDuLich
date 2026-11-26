<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class UserPresence
{
    private const ONLINE_SECONDS = 60;

    public function __construct(private PDO $db)
    {
    }

    public function touch(string $sessionKey, ?int $userId, string $currentPath): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $sessionKey)) {
            return;
        }
        $path = parse_url($currentPath, PHP_URL_PATH);
        $path = is_string($path) && str_starts_with($path, '/') ? $path : '/';
        $path = mb_substr($path, 0, 255);

        $stmt = $this->db->prepare(
            'INSERT INTO UserPresence (sessionKey, userId, currentPath, lastSeenAt)
             VALUES (:sessionKey, :userId, :currentPath, CURRENT_TIMESTAMP)
             ON CONFLICT (sessionKey) DO UPDATE SET
                 userId = EXCLUDED.userId,
                 currentPath = EXCLUDED.currentPath,
                 lastSeenAt = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'sessionKey' => $sessionKey,
            'userId' => $userId && $userId > 0 ? $userId : null,
            'currentPath' => $path,
        ]);

        $this->db->exec(
            "DELETE FROM UserPresence
              WHERE lastSeenAt < CURRENT_TIMESTAMP - INTERVAL '1 day'"
        );
    }

    public function snapshot(int $recentLimit = 3): array
    {
        $interval = self::ONLINE_SECONDS . ' seconds';
        $counts = $this->db->query(
            "SELECT
                COUNT(DISTINCT COALESCE('user:' || userId::text, 'session:' || sessionKey)) AS totalOnline,
                COUNT(DISTINCT userId) FILTER (WHERE userId IS NOT NULL) AS loggedInOnline,
                COUNT(DISTINCT sessionKey) FILTER (WHERE userId IS NULL) AS guestOnline
             FROM UserPresence
             WHERE lastSeenAt >= CURRENT_TIMESTAMP - INTERVAL '{$interval}'"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->db->prepare(
            "SELECT up.userId,
                    COALESCE(u.name, 'Khách vãng lai') AS displayName,
                    up.currentPath,
                    GREATEST(0, FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - up.lastSeenAt))))::int AS secondsAgo
               FROM UserPresence up
               LEFT JOIN Users u ON u.userId = up.userId
              WHERE up.lastSeenAt >= CURRENT_TIMESTAMP - INTERVAL '{$interval}'
              ORDER BY up.lastSeenAt DESC
              LIMIT :limit"
        );
        $stmt->bindValue(':limit', max(1, min(10, $recentLimit)), PDO::PARAM_INT);
        $stmt->execute();

        return [
            'totalOnline' => (int)($counts['totalonline'] ?? $counts['totalOnline'] ?? 0),
            'loggedInOnline' => (int)($counts['loggedinonline'] ?? $counts['loggedInOnline'] ?? 0),
            'guestOnline' => (int)($counts['guestonline'] ?? $counts['guestOnline'] ?? 0),
            'recent' => normalize_db_rows($stmt->fetchAll(PDO::FETCH_ASSOC)),
            'onlineWindowSeconds' => self::ONLINE_SECONDS,
            'generatedAt' => time(),
        ];
    }
}
