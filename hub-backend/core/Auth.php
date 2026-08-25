<?php
require_once __DIR__ . '/JWT.php';

class Auth
{
    public static function admin(): ?array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) return null;

        $config = require __DIR__ . '/../config/config.php';
        return JWT::decode($m[1], $config['jwt_secret']);
    }

    public static function requireAdmin(): array
    {
        $admin = self::admin();
        if (!$admin) Response::json(['success' => false, 'message' => 'ابتدا وارد شوید'], 401);
        return $admin;
    }
}
