<?php
// JWT-style auth: issue/validate tokens, gate routes by login/admin role.
namespace App\Middleware;

class AuthMiddleware
{
    private string $secret;

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret ?? JWT_SECRET;
    }

    /**
     * Generate a JWT-like token.
     */
    public function generateToken(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + 3600; // 1 hour
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", $this->secret, true)
        );
        return "$header.$payloadEncoded.$signature";
    }

    /**
     * Validate a token and return the decoded payload or null.
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;
        $validSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secret, true)
        );
        if (!hash_equals($validSignature, $signature)) return null;

        $data = json_decode($this->base64UrlDecode($payload), true);
        if (!$data || !isset($data['exp']) || $data['exp'] < time()) return null;

        return $data;
    }

    /**
     * Get Bearer token from the Authorization header.
     */
    private function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Require any authenticated user. Exits with 401 if invalid.
     * Returns decoded token payload (includes user_id, email, role).
     */
    public function requireLogin(): array
    {
        $token = $this->getBearerToken();
        if (!$token) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }
        $payload = $this->validateToken($token);
        if (!$payload) {
            jsonResponse(['error' => 'Invalid or expired token'], 401);
        }
        return $payload;
    }

    /**
     * Require admin role. Exits with 403 if not admin.
     */
    public function requireAdmin(): array
    {
        $payload = $this->requireLogin();
        if (($payload['role'] ?? '') !== 'admin') {
            jsonResponse(['error' => 'Admin privileges required'], 403);
        }
        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}