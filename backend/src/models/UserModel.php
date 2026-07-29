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
     * Get all users (for testing – no role filter).
     */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query("SELECT id, name, email, role, created_at FROM users");
        return $stmt->fetchAll();
    }

    /**
     * Get all staff members (role = 'staff').
     */
    public function getAllStaff(): array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users WHERE role = 'staff'");
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
            "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get user profile (excludes password).
     */
    public function getUserProfile(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}