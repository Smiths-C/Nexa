<?php
// ادمین کل می‌تونه مستقیم به هر مدرسه‌ای معلم اضافه کنه (school_id رو خودش مشخص می‌کنه)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['school_id', 'full_name', 'username', 'password'] as $f) {
    if (empty($input[$f])) Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE id = ?");
$stmt->execute([$input['school_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$input['username']]);
if ($stmt->fetch()) Response::json(['success' => false, 'message' => 'این نام کاربری قبلا استفاده شده'], 409);

$hash = password_hash($input['password'], PASSWORD_BCRYPT);
$stmt = $db->prepare(
    "INSERT INTO users (role, school_id, full_name, username, password_hash) VALUES ('teacher', ?, ?, ?, ?)"
);
$stmt->execute([$input['school_id'], $input['full_name'], $input['username'], $hash]);

Response::json(['success' => true, 'teacher_id' => (int) $db->lastInsertId()]);
