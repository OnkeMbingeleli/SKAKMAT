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
     * POST /api/users  (admin only) – create a user (role from body).
     */
    public function store(array $input): void
    {
        $this->auth->requireAdmin();

        $required = ['name', 'email', 'password', 'role'];
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
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => $input['password'],
            'role'     => $role,
        ]);

        $user = $this->userModel->getUserProfile($id);

        // Generate token for the new user
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
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ]
        ]);
    }

    /**
     * GET /api/profile  (any authenticated user)
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
     * GET /api/users  (admin only) – all users (test purpose).
     */
    public function index(): void
    {
        $this->auth->requireAdmin();
        $users = $this->userModel->getAllUsers();
        jsonResponse(['success' => true, 'data' => $users]);
    }

    /**
     * GET /api/users/staff  (admin only) – all staff.
     */
    public function staff(): void
    {
        $this->auth->requireAdmin();
        $staff = $this->userModel->getAllStaff();
        jsonResponse(['success' => true, 'data' => $staff]);
    }
}