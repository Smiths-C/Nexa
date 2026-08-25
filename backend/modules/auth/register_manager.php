<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/JWT.php';
require_once __DIR__ . '/../../modules/ai/SchoolVerifier.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['full_name', 'username', 'password', 'school_name', 'national_code', 'city'] as $f) {
    if (empty($input[$f])) {
        Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
    }
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$input['username']]);
if ($stmt->fetch()) {
    Response::json(['success' => false, 'message' => 'این نام کاربری قبلا استفاده شده'], 409);
}

// نقش کاربر همینجا مشخص و ذخیره میشه: manager
$verifier = new SchoolVerifier($db);
$result = $verifier->verify($input['school_name'], $input['national_code'], $input['city']);
if (!$result['valid']) {
    Response::json(['success' => false, 'message' => $result['message']], 422);
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO schools (name, national_code, city, status) VALUES (?, ?, ?, 'approved')");
    $stmt->execute([$input['school_name'], $input['national_code'], $input['city']]);
    $schoolId = (int) $db->lastInsertId();

    $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        "INSERT INTO users (role, school_id, full_name, username, phone, password_hash) VALUES ('manager', ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$schoolId, $input['full_name'], $input['username'], $input['phone'] ?? null, $passwordHash]);
    $userId = (int) $db->lastInsertId();

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    Response::json(['success' => false, 'message' => 'خطا در ثبت‌نام، دوباره تلاش کنید'], 500);
}

$config = require __DIR__ . '/../../config/config.php';
$token = JWT::encode(
    ['user_id' => $userId, 'role' => 'manager', 'school_id' => $schoolId, 'class_id' => null, 'full_name' => $input['full_name']],
    $config['jwt_secret'],
    $config['jwt_expire_seconds']
);

Response::json([
    'success' => true,
    'message' => 'ثبت‌نام با موفقیت انجام شد',
    'token'   => $token,
    'user'    => [
        'id' => $userId, 'role' => 'manager', 'school_id' => $schoolId,
        'full_name' => $input['full_name'], 'school_name' => $input['school_name'],
    ],
]);
