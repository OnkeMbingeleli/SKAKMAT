<?php
namespace App\Models;

use PDO;

class UserModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDB();
    }

    /**
     * Get all users (no role filter).
     */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users");
        return $stmt->fetchAll();
    }

    /**
     * Get all staff members.
     */
    public function getAllStaff(): array
    {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users WHERE role = 'staff'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find a user by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new user. Returns the new user ID.
     */
    public function createUser(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (first_name, last_name, email, role, department, position, password)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['role'],
            $data['department'],
            $data['position'],
            password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get user profile (excludes password).
     */
    public function getUserProfile(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, role, department, position, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update user fields (except password).
     */
    public function updateUser(int $id, array $data): bool
    {
        $allowed = ['first_name', 'last_name', 'email', 'department', 'position', 'role'];
        $fields = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $data[$col];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }
<<<<<<< HEAD

=======
>>>>>>> origin/PortReferencingUpdate
    /**
     * Update password for a user.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }
}