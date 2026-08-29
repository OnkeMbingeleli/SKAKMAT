<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\UserModel;
use App\Services\Mailer;

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
     * GET /api/users (admin only) – filtered user list
     */
    public function index(): void
    {
        $this->auth->requireAdmin();

        $filters = [
            'search' => $_GET['search'] ?? null,
            'department' => $_GET['department'] ?? null,
            'position' => $_GET['position'] ?? null,
            'role' => $_GET['role'] ?? null,
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $withAttendance = (($_GET['attendance'] ?? 'false') === 'true');

        $users = $this->userModel->getUsers($filters, $withAttendance, $limit, $offset);
        $total = $this->userModel->countUsers($filters);

        jsonResponse([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'departments' => $this->userModel->getDepartments($filters['role'] ?: null),
                'positions' => $this->userModel->getPositions($filters['role'] ?: null),
                'roles' => ['admin', 'staff'],
            ],
        ]);
    }

    /**
     * GET /api/users/{id} (admin only) – single user detail
     */
    public function show(int $id): void
    {
        $this->auth->requireAdmin();

        $user = $this->userModel->getUserProfile($id);
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        $attendance = $this->userModel->getUserAttendanceSummary($id);

        jsonResponse([
            'success' => true,
            'data' => [
                'user' => $user,
                'attendance' => $attendance,
            ],
        ]);
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

        $generatedPassword = $this->generatePassword(12);

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
        $mailer = new Mailer();

        if (!$mailer->sendWelcomeCredentials($user ?? $input, $generatedPassword)) {
            try {
                $this->userModel->deleteUser($id);
            } catch (\Throwable $cleanupError) {
                error_log('Skakmat createStaff cleanup failed: ' . $cleanupError->getMessage());
            }
            jsonResponse([
                'success' => false,
                'error' => 'Employee was not created because the welcome email could not be sent. Configure the PHP mail service/SMTP settings and try again.'
            ], 502);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Employee created and login credentials sent by email',
            'user' => $user,
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
     * DELETE /api/users/{id} (admin only) - remove a staff member.
     */
    public function destroy(int $id): void
    {
        $this->auth->requireAdmin();

        $existing = $this->userModel->findById($id);
        if (!$existing) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        if (($existing['role'] ?? '') !== 'staff') {
            jsonResponse(['error' => 'Only staff employees can be removed'], 400);
        }

        try {
            $deleted = $this->userModel->deleteUser($id);
        } catch (\Throwable $e) {
            jsonResponse(['error' => 'Unable to remove employee because related records still exist'], 409);
        }

        if (!$deleted) {
            jsonResponse(['error' => 'Unable to remove employee'], 400);
        }

        jsonResponse(['success' => true, 'message' => 'Employee removed']);
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
        
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }
}
