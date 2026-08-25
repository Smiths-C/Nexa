<?php
// تغییر رمز عبور برای هر نقشی (ادمین، مدیر، معلم، دانش‌آموز) - خودشون رمزشون رو عوض می‌کنن
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['admin', 'manager', 'teacher', 'student']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['current_password']) || empty($input['new_password'])) {
    Response::json(['success' => false, 'message' => 'رمز فعلی و رمز جدید الزامی است'], 422);
}
if (mb_strlen($input['new_password']) < 6) {
    Response::json(['success' => false, 'message' => 'رمز جدید باید حداقل ۶ کاراکتر باشد'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$user['user_id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($input['current_password'], $row['password_hash'])) {
    Response::json(['success' => false, 'message' => 'رمز فعلی اشتباه است'], 401);
}

$newHash = password_hash($input['new_password'], PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$newHash, $user['user_id']]);

Response::json(['success' => true, 'message' => 'رمز عبور با موفقیت تغییر کرد']);
