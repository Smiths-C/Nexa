<?php
// مدیر می‌تونه رمز عبور معلم یا دانش‌آموزِ مدرسه‌ی خودش رو ریست کنه (مثلا اگه فراموش کردن)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['user_id']) || empty($input['new_password'])) {
    Response::json(['success' => false, 'message' => 'user_id و new_password الزامی است'], 422);
}
if (mb_strlen($input['new_password']) < 6) {
    Response::json(['success' => false, 'message' => 'رمز جدید باید حداقل ۶ کاراکتر باشد'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND school_id = ? AND role IN ('teacher','student')");
$stmt->execute([$input['user_id'], $user['school_id']]);
if (!$stmt->fetch()) {
    Response::json(['success' => false, 'message' => 'این کاربر متعلق به مدرسه‌ی شما نیست'], 403);
}

$hash = password_hash($input['new_password'], PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$hash, $input['user_id']]);

Response::json(['success' => true, 'message' => 'رمز عبور با موفقیت ریست شد']);
