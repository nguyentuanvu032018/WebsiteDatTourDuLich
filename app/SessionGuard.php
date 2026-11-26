<?php

declare(strict_types=1);

namespace App;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\User;
use PDO;

class SessionGuard
{
    protected ?User $user = null;

    public function __construct(protected PDO $pdo)
    {
    }

    public function login(User $user, array $credentials): bool
    {
        if (!$user->verifyPassword((string)($credentials['password'] ?? ''))) {
            return false;
        }

        session_regenerate_id(true);
        $this->user = $user;
        $_SESSION['user_id'] = $user->userId;
        $_SESSION['user_role'] = $this->detectRoleByUserId($user->userId);
        unset($_SESSION['form']);
        csrf_token();

        return true;
    }

    protected function detectRoleByUserId(int $userId): string
    {
        $statement = $this->pdo->prepare('SELECT userId FROM Admin WHERE userId = :userId LIMIT 1');
        $statement->execute(['userId' => $userId]);
        return $statement->fetchColumn() !== false ? 'admin' : 'customer';
    }

    public function user(): ?User
    {
        if (!$this->isUserLoggedIn()) {
            $this->user = null;
            return null;
        }

        if ($this->user instanceof User) {
            return $this->user;
        }

        $userId = (int)$_SESSION['user_id'];
        $role = (string)($_SESSION['user_role'] ?? 'customer');
        $model = $role === 'admin' ? new Admin($this->pdo) : new Customer($this->pdo);
        $this->user = $model->where('userId', (string)$userId);

        // The account may have been deleted while an old session cookie remains.
        if (!$this->user) {
            $this->logout();
        }

        return $this->user;
    }

    public function isAdmin(): bool
    {
        return $this->isUserLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public function logout(): void
    {
        $this->user = null;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?: '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => (bool)($params['secure'] ?? false),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Do not let PHP reuse the session id still present in the current request cookie.
        unset($_COOKIE[session_name()]);
        session_id('');

        // Start a clean anonymous session for flash messages and a fresh CSRF token.
        session_start();
        session_regenerate_id(true);
        csrf_token();
    }

    public function isUserLoggedIn(): bool
    {
        return isset($_SESSION['user_id'])
            && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) !== false
            && (int)$_SESSION['user_id'] > 0
            && in_array($_SESSION['user_role'] ?? '', ['admin', 'customer'], true);
    }
}
