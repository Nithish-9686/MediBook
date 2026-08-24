<?php

namespace Nithish\Medibook\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler
{
    private string $secret;
    private string $algo;
    private int $expiry;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->algo = $_ENV['JWT_ALGO'] ?? 'HS256';
        $this->expiry = (int)($_ENV['JWT_EXPIRY'] ?? 3600);
    }

    public function generateToken(int $userId, string $email): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiry;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $userId,
            'email' => $email
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}