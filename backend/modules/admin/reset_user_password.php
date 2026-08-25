<?php
// ادمین کل می‌تونه رمز عبور هر کاربری (مدیر، معلم، دانش‌آموز) رو ریست کنه
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['user_id']) || empty($input['new_password'])) {
    Response::json(['success' => false, 'message' => 'user_id و new_password الزامی است'], 422);
}
if (mb_strlen($input['new_password']) < 6) {
    Response::json(['success' => false, 'message' => 'رمز جدید باید حداقل ۶ کاراکتر باشد'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$input['user_id']]);
if (!$stmt->fetch()) {
    Response::json(['success' => false, 'message' => 'کاربر یافت نشد'], 404);
}

$hash = password_hash($input['new_password'], PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$hash, $input['user_id']]);

Response::json(['success' => true, 'message' => 'رمز عبور با موفقیت ریست شد']);
