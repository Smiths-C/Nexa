<?php
// حذف کامل و برگشت‌ناپذیر مدرسه و همه‌ی اطلاعاتش (کلاس، کاربر، تکلیف، پیام و ...)
// چون همه‌ی جدول‌ها با ON DELETE CASCADE به schools وصلن، همینجا کافیه.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$schoolId = $input['school_id'] ?? null;
if (!$schoolId) Response::json(['success' => false, 'message' => 'school_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

$stmt = $db->prepare("DELETE FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);

Response::json(['success' => true, 'message' => 'مدرسه و تمام اطلاعاتش برای همیشه حذف شد']);
