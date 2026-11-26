<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    protected PDO $db;

    public int $userId = -1;
    public string $email = '';
    public string $sDT = '';
    public string $name = '';
    public string $password = '';
    public string $createdAt = '';

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function where(string $column, string $value): ?self
    {
        $columns = ['userId', 'email', 'phoneNumber', 'name'];
        if (!in_array($column, $columns, true)) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE {$column} = :value LIMIT 1");
        $stmt->execute(['value' => $value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $this->fillFromDbRow($row);
        return $this;
    }

    public function fill(array $data): self
    {
        $this->email = strtolower(trim((string)($data['email'] ?? $this->email)));
        $this->sDT = trim((string)($data['sDT'] ?? $data['sdt'] ?? $data['phoneNumber'] ?? $this->sDT));
        $this->name = trim((string)($data['name'] ?? $this->name));
        if (isset($data['password']) && (string)$data['password'] !== '') {
            $this->password = password_hash((string)$data['password'], PASSWORD_DEFAULT);
        }
        return $this;
    }

    protected function fillFromDbRow(array $row): void
    {
        $row = normalize_db_row($row);
        $this->userId = (int)($row['userId'] ?? -1);
        $this->email = (string)($row['email'] ?? '');
        $this->sDT = (string)($row['phoneNumber'] ?? '');
        $this->name = (string)($row['name'] ?? '');
        $this->password = (string)($row['password'] ?? '');
        $this->createdAt = (string)($row['createdAt'] ?? '');
    }

    public function validate(array $data): array
    {
        $errors = [];
        $name = trim((string)($data['name'] ?? $this->name));
        $email = strtolower(trim((string)($data['email'] ?? $this->email)));
        $phone = trim((string)($data['sDT'] ?? $data['phoneNumber'] ?? $this->sDT));
        $password = (string)($data['password'] ?? '');
        $confirm = (string)($data['password_confirmation'] ?? '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $errors['name'] = 'Họ tên phải có từ 2 đến 150 ký tự.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không đúng định dạng.';
        } elseif ($this->isValueInUse('email', $email)) {
            $errors['email'] = 'Email đã được sử dụng.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+() .-]{8,20}$/', $phone)) {
            $errors['sDT'] = 'Số điện thoại không hợp lệ.';
        } elseif ($phone !== '' && $this->isValueInUse('phoneNumber', $phone)) {
            $errors['sDT'] = 'Số điện thoại đã được sử dụng.';
        }
        if ($this->userId <= 0 || $password !== '') {
            if (strlen($password) < 6) {
                $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
            } elseif ($password !== $confirm) {
                $errors['password_confirmation'] = 'Xác nhận mật khẩu không khớp.';
            }
        }
        return $errors;
    }

    private function isValueInUse(string $column, string $value): bool
    {
        if (!in_array($column, ['email', 'phoneNumber'], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM Users WHERE {$column} = :value AND userId <> :userId"
        );
        $stmt->execute(['value' => $value, 'userId' => $this->userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function save(): bool
    {
        if ($this->name === '' || $this->email === '' || $this->password === '') {
            return false;
        }
        if ($this->userId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE Users SET name = :name, email = :email, phoneNumber = :phoneNumber,
                 password = :password WHERE userId = :userId'
            );
            return $stmt->execute([
                'userId' => $this->userId,
                'name' => $this->name,
                'email' => $this->email,
                'phoneNumber' => $this->sDT !== '' ? $this->sDT : null,
                'password' => $this->password,
            ]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO Users (name, password, email, phoneNumber)
             VALUES (:name, :password, :email, :phoneNumber) RETURNING userId, createdAt'
        );
        $ok = $stmt->execute([
            'name' => $this->name,
            'password' => $this->password,
            'email' => $this->email,
            'phoneNumber' => $this->sDT !== '' ? $this->sDT : null,
        ]);
        if ($ok) {
            $created = normalize_db_row((array)$stmt->fetch(PDO::FETCH_ASSOC));
            $this->userId = (int)($created['userId'] ?? -1);
            $this->createdAt = (string)($created['createdAt'] ?? '');
        }
        return $ok;
    }

    public function verifyPassword(string $password): bool
    {
        return $this->password !== '' && password_verify($password, $this->password);
    }

    public function updatePassword(string $newPassword): bool
    {
        if ($this->userId <= 0 || strlen($newPassword) < 6) {
            return false;
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE Users SET password = :password WHERE userId = :userId');
        $ok = $stmt->execute(['password' => $hash, 'userId' => $this->userId]);
        if ($ok) {
            $this->password = $hash;
        }
        return $ok;
    }
}
