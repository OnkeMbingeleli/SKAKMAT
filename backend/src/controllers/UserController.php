<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\UserModel;

class UserController
{
    private UserModel $userModel;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->auth = new AuthMiddleware();
    }

    /**
     * POST /api/users (admin only) – create a user with explicit role.
     */
    public function store(array $input): void
    {
        $this->auth->requireAdmin();

        $required = ['first_name', 'last_name', 'email', 'role', 'department', 'position', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        $role = $input['role'];
        if (!in_array($role, ['admin', 'staff'])) {
            jsonResponse(['error' => 'Role must be admin or staff'], 400);
        }

        $existing = $this->userModel->findByEmail($input['email']);
        if ($existing) {
            jsonResponse(['error' => 'Email already exists'], 409);
        }

        $id = $this->userModel->createUser([
            'first_name' => $input['first_name'],
            'last_name'  => $input['last_name'],
            'email'      => $input['email'],
            'role'       => $role,
            'department' => $input['department'],
            'position'   => $input['position'],
            'password'   => $input['password'],
        ]);

        $user = $this->userModel->getUserProfile($id);

        $token = $this->auth->generateToken([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'User created',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    /**
     * POST /api/login
     */
    public function login(array $input): void
    {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            jsonResponse(['error' => 'Email and password are required'], 400);
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['error' => 'Invalid credentials'], 401);
        }

        $token = $this->auth->generateToken([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        jsonResponse([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'         => $user['id'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'department' => $user['department'],
                'position'   => $user['position'],
            ]
        ]);
    }

    /**
     * GET /api/profile (any authenticated user)
     */
    public function getProfile(): void
    {
        $payload = $this->auth->requireLogin();
        $userId = $payload['user_id'] ?? 0;
        $user = $this->userModel->getUserProfile($userId);
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }
        jsonResponse(['success' => true, 'data' => $user]);
    }

    /**
     * GET /api/users (admin only) – filtered, paginated list.
     * Query params: search, department, position, role, page, limit, attendance=true
     */
    public function index(): void
    {
        $this->auth->requireAdmin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $filters = array_filter([
            'search'     => $_GET['search'] ?? null,
            'department' => $_GET['department'] ?? null,
            'position'   => $_GET['position'] ?? null,
            'role'       => $_GET['role'] ?? null,
        ]);

        $withAttendance = ($_GET['attendance'] ?? '') === 'true';

        $users = $this->userModel->getUsers($filters, $withAttendance, $limit, $offset);
        $total = $this->userModel->countUsers($filters);

        jsonResponse([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'departments' => $this->userModel->getDepartments(),
                'positions' => $this->userModel->getPositions(),
            ],
        ]);
    }

    /**
     * GET /api/users/{id} (admin only) – single user + their attendance summary.
     */
    public function show(int $id): void
    {
        $this->auth->requireAdmin();

        $user = $this->userModel->getUserProfile($id);
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        $attendance = $this->userModel->getUserAttendanceSummary($id);

        jsonResponse(['success' => true, 'data' => ['user' => $user, 'attendance' => $attendance]]);
    }

    /**
     * GET /api/users/staff (admin only) – all staff
     */
    public function staff(): void
    {
        $this->auth->requireAdmin();
        $staff = $this->userModel->getAllStaff();
        jsonResponse(['success' => true, 'data' => $staff]);
    }

    /**
     * POST /api/users/staff (admin only) – create a staff member
     * Password is auto‑generated and returned.
     */
    public function createStaff(array $input): void
    {
        $this->auth->requireAdmin();

        $required = ['first_name', 'last_name', 'email', 'department', 'position'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        $existing = $this->userModel->findByEmail($input['email']);
        if ($existing) {
            jsonResponse(['error' => 'Email already exists'], 409);
        }

        $generatedPassword = $this->generatePassword(8);

        $id = $this->userModel->createUser([
            'first_name' => $input['first_name'],
            'last_name'  => $input['last_name'],
            'email'      => $input['email'],
            'role'       => 'staff',
            'department' => $input['department'],
            'position'   => $input['position'],
            'password'   => $generatedPassword,
        ]);

        $user = $this->userModel->getUserProfile($id);

        jsonResponse([
            'success'  => true,
            'message'  => 'Staff member created',
            'user'     => $user,
            'password' => $generatedPassword
        ], 201);
    }

    /**
     * PATCH /api/users/{id} (admin only) – update a user
     */
    public function updateUser(int $id, array $input): void
    {
        $this->auth->requireAdmin();

        $existing = $this->userModel->findById($id);
        if (!$existing) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        $data = [];
        foreach (['first_name', 'last_name', 'email', 'department', 'position', 'role'] as $field) {
            if (isset($input[$field])) {
                $data[$field] = $input[$field];
            }
        }

        if (empty($data)) {
            jsonResponse(['error' => 'No valid fields to update'], 400);
        }

        $this->userModel->updateUser($id, $data);
        $user = $this->userModel->getUserProfile($id);
        jsonResponse(['success' => true, 'user' => $user]);
    }

    /**
     * PATCH /api/profile/password – update own password
     */
    public function updatePassword(array $input): void
    {
        $payload = $this->auth->requireLogin();
        $userId = $payload['user_id'];

        $oldPassword = $input['old_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword)) {
            jsonResponse(['error' => 'Old and new passwords are required'], 400);
        }

        if (strlen($newPassword) < 8) {
            jsonResponse(['error' => 'New password must be at least 8 characters'], 400);
        }

        $user = $this->userModel->findById($userId);
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            jsonResponse(['error' => 'Current password is incorrect'], 401);
        }

        $this->userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_BCRYPT));
        jsonResponse(['success' => true, 'message' => 'Password updated successfully']);
    }

    /**
     * Generate a random password.
     */
    private function generatePassword(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        return substr(str_shuffle($chars), 0, $length);
    }
}