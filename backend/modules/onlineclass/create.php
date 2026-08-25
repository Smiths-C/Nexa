<?php
/**
 * مدیرِ مدرسه یا ادمین کل، برای یک کلاس، یک جلسه‌ی کلاس آنلاین می‌سازه و
 * مشخص می‌کنه معلم اصلیش کیه. خودِ ویدیو/صدا روی سرور Node (VPS) پخش می‌شه؛
 * اینجا فقط رکورد جلسه ساخته می‌شه.
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['manager', 'admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$classId = $input['class_id'] ?? null;
$mainTeacherId = $input['main_teacher_id'] ?? null;
$title = $input['title'] ?? null;

if (!$classId || !$mainTeacherId) {
    Response::json(['success' => false, 'message' => 'class_id و main_teacher_id الزامی است'], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch();
if (!$class) Response::json(['success' => false, 'message' => 'کلاس یافت نشد'], 404);

// مدیر فقط برای مدرسه‌ی خودش، ادمین برای هر مدرسه‌ای
if ($user['role'] === 'manager' && (int) $class['school_id'] !== (int) $user['school_id']) {
    Response::json(['success' => false, 'message' => 'این کلاس متعلق به مدرسه‌ی شما نیست'], 403);
}
$schoolId = (int) $class['school_id'];
FeatureGate::require($db, $schoolId, FeatureGate::ONLINE_CLASS);

// معلم اصلی باید واقعا به این کلاس تخصیص داشته باشه
$stmt = $db->prepare(
    "SELECT u.id FROM users u JOIN class_teacher ct ON ct.teacher_id = u.id
     WHERE u.id = ? AND ct.class_id = ? AND u.role = 'teacher'"
);
$stmt->execute([$mainTeacherId, $classId]);
if (!$stmt->fetch()) {
    Response::json(['success' => false, 'message' => 'معلم انتخاب‌شده به این کلاس تخصیص ندارد'], 422);
}

$stmt = $db->prepare(
    "INSERT INTO online_classes (school_id, class_id, main_teacher_id, created_by, title, status)
     VALUES (?, ?, ?, ?, ?, 'scheduled')"
);
$stmt->execute([$schoolId, $classId, $mainTeacherId, $user['user_id'], $title]);

Response::json(['success' => true, 'session_id' => (int) $db->lastInsertId()]);
