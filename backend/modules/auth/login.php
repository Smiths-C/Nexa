<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/JWT.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['username']) || empty($input['password'])) {
    Response::json(['success' => false, 'message' => 'نام کاربری و رمز عبور الزامی است'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$input['username']]);
$user = $stmt->fetch();

if (!$user || !password_verify($input['password'], $user['password_hash'])) {
    Response::json(['success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است'], 401);
}
if ($user['status'] !== 'active') {
    Response::json(['success' => false, 'message' => 'حساب شما غیرفعال شده است'], 403);
}

// اگه مدرسه‌ی این کاربر تعلیق شده باشه، حتی اجازه‌ی ورود هم نداره (مدیر/معلم/دانش‌آموز فرقی نمی‌کنه)
if ($user['school_id']) {
    $stmt = $db->prepare("SELECT status FROM schools WHERE id = ?");
    $stmt->execute([$user['school_id']]);
    $school = $stmt->fetch();
    if ($school && $school['status'] === 'suspended') {
        Response::json(['success' => false, 'message' => 'مدرسه‌ی شما موقتا توسط ادمین سیستم تعلیق شده و امکان ورود نیست'], 403);
    }
}

$config = require __DIR__ . '/../../config/config.php';
$token = JWT::encode([
    'user_id'   => (int) $user['id'],
    'role'      => $user['role'],
    'school_id' => $user['school_id'] !== null ? (int) $user['school_id'] : null,
    'class_id'  => $user['class_id']  !== null ? (int) $user['class_id']  : null,
    'full_name' => $user['full_name'],
], $config['jwt_secret'], $config['jwt_expire_seconds']);

unset($user['password_hash']);
Response::json(['success' => true, 'token' => $token, 'user' => $user]);
