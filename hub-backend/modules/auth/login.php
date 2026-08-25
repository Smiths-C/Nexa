<?php
// ورود ادمین هاب مرکزی (سطح بالاتر از ادمین هر هاست)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/JWT.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($input['username']) || empty($input['password'])) {
    Response::json(['success' => false, 'message' => 'نام کاربری و رمز عبور الزامی است'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM hub_admins WHERE username = ?");
$stmt->execute([$input['username']]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($input['password'], $admin['password_hash'])) {
    Response::json(['success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است'], 401);
}

$config = require __DIR__ . '/../../config/config.php';
$token = JWT::encode(
    ['admin_id' => (int) $admin['id'], 'full_name' => $admin['full_name']],
    $config['jwt_secret'],
    $config['jwt_expire_seconds']
);

Response::json(['success' => true, 'token' => $token, 'admin' => ['id' => $admin['id'], 'full_name' => $admin['full_name']]]);
