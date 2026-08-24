<?php

namespace Nithish\Medibook\Auth;

class AuthMiddleware
{
    private JwtHandler $jwtHandler;

    public function __construct()
    {
        $this->jwtHandler = new JwtHandler();
    }

    /**
     * Validates the auth_token cookie.
     * Returns the decoded payload (user id, email) if valid.
     * Sends a 401 JSON response and halts execution if invalid/missing.
     */
    public function requireAuth(): array
    {
        $token = $_COOKIE['auth_token'] ?? null;

        if (!$token) {
            $this->unauthorized('No token provided');
        }

        $payload = $this->jwtHandler->validateToken($token);

        if ($payload === null) {
            $this->unauthorized('Invalid or expired token');
        }

        return $payload;
    }

    /**
     * Like requireAuth(), but does NOT block the request if the token
     * is missing or invalid. Returns the payload if valid, null otherwise.
     * Use this for endpoints that work for both guests and logged-in users.
     */
    public function optionalAuth(): ?array
    {
        $token = $_COOKIE['auth_token'] ?? null;

        if (!$token) {
            return null;
        }

        return $this->jwtHandler->validateToken($token);
    }

    private function unauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}