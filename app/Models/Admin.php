<?php

namespace App\Models;

use Override;
use PDO;

class Admin extends User
{
    public string $staffCode = ''; // mã số nhân viện

    public function __construct(PDO $pdo, string $staffCode = '')
    {
        parent::__construct($pdo);
        $this->staffCode = $staffCode;
    }

    // xóa tài khoản khach hàng
    public function delete(int $userId): bool
    {
        // Kiểm tra ID hợp lệ (Phải lớn hơn 0)
        if ($userId <= 0) {
            return false;
        }

        // Nhờ CSDL PostgreSQL thiết lập ON DELETE CASCADE ở các bảng liên quan (Admin, Customer, Bookings,...), 
        // nên chỉ cần xóa trong bảng Users là dữ liệu ở các bảng con sẽ tự động xóa sạch.
        $statement = $this->db->prepare('DELETE FROM Users WHERE userId = :userId');
        return $statement->execute(['userId' => $userId]);
    }
    //hiển thi danh sách người dùng
    public function all(): array
    {
        $statement = $this->db->query('SELECT * FROM users');
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    //tim kiem khách hang bằng email
    public function findUserByEmail(string $email): array
    {
        $statement = $this->db->prepare('select * from Users where email = :email limit 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user ?: [];
    }

    //tim kiem khách hang bằng email
    public function findUserByPhoneNumber(string $phoneNumber): array
    {
        $statement = $this->db->prepare('SELECT * FROM Users WHERE phoneNumber = :phoneNumber');
        $statement->execute(['phoneNumber' => $phoneNumber]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        return $user ?: [];
    }
    // lấy mã nhan vien
    public function getStaffCode(): string
    {
        // Nếu $staffCode chưa có và có userId hợp lệ, tìm trong bảng Admin
        if (empty($this->staffCode) && $this->userId > 0) {
            $statement = $this->db->prepare('SELECT staffCode FROM Admin WHERE userId = :userId LIMIT 1');
            $statement->execute(['userId' => $this->userId]);
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['staffcode'])) {
                $this->staffCode = $result['staffcode'];
            }
        }

        return $this->staffCode;
    }
}
