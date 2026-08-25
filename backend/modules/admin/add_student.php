<?php
// ادمین کل می‌تونه مستقیم به هر مدرسه‌ای دانش‌آموز اضافه کنه (school_id رو خودش مشخص می‌کنه)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['school_id', 'full_name', 'username', 'password', 'class_id'] as $f) {
    if (empty($input[$f])) Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
$stmt->execute([$input['class_id'], $input['school_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'کلاس متعلق به این مدرسه نیست'], 422);

$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$input['username']]);
if ($stmt->fetch()) Response::json(['success' => false, 'message' => 'این نام کاربری قبلا استفاده شده'], 409);

$hash = password_hash($input['password'], PASSWORD_BCRYPT);
$stmt = $db->prepare(
    "INSERT INTO users (role, school_id, class_id, full_name, username, password_hash) VALUES ('student', ?, ?, ?, ?, ?)"
);
$stmt->execute([$input['school_id'], $input['class_id'], $input['full_name'], $input['username'], $hash]);

Response::json(['success' => true, 'student_id' => (int) $db->lastInsertId()]);
