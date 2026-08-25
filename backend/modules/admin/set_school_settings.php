<?php
// ادمین کل می‌تونه برای هر مدرسه، تعداد "پیام آزاد" دانش‌آموز (قبل از نیاز به اجازه‌ی معلم) رو تغییر بده
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$schoolId = $input['school_id'] ?? null;
$limit    = $input['free_message_limit'] ?? null; // مثلا 1 یا 2 یا 3 ...

if (!$schoolId) Response::json(['success' => false, 'message' => 'school_id الزامی است'], 422);
if ($limit === null || !is_numeric($limit) || (int) $limit < 0) {
    Response::json(['success' => false, 'message' => 'free_message_limit باید یک عدد صفر یا بزرگ‌تر باشد'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

$stmt = $db->prepare("UPDATE schools SET free_message_limit = ? WHERE id = ?");
$stmt->execute([(int) $limit, $schoolId]);

Response::json(['success' => true, 'message' => 'محدودیت پیام آزاد این مدرسه به‌روزرسانی شد']);
