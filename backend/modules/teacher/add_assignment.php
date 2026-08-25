<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FileHelper.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['teacher']);

// این اندپوینت با multipart/form-data صدا زده میشه (چون ممکنه فایل ویدیو/ضمیمه داشته باشه)
$classId      = $_POST['class_id'] ?? null;
$title        = $_POST['title'] ?? null;
$description  = $_POST['description'] ?? '';
$type         = $_POST['type'] ?? 'text'; // text | video | file
$dueDate      = $_POST['due_date'] ?? null;       // مثلا 2026-09-01 14:00:00
$maxScore     = $_POST['max_score'] ?? null;      // مثلا 20
$latePenalty  = $_POST['late_penalty'] ?? null;   // مثلا 1 (اگه دیر بفرسته، از max_score کم میشه)

if (!$classId || !$title) {
    Response::json(['success' => false, 'message' => 'class_id و title الزامی است'], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
$stmt->execute([$classId, $user['user_id']]);
if (!$stmt->fetch()) {
    Response::json(['success' => false, 'message' => 'شما به این کلاس دسترسی ندارید'], 403);
}

// کسر نمره‌ی تاخیر فقط وقتی معتبره که قابلیت late_penalty برای این مدرسه روشن باشه
if ($latePenalty !== null && !FeatureGate::isEnabled($db, (int) $user['school_id'], FeatureGate::LATE_PENALTY)) {
    $latePenalty = null;
}

$stmt = $db->prepare("SELECT name FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$className = $stmt->fetchColumn() ?: 'کلاس';

$filePath = null;
if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // فایل داخل uploads/assignments/school_<id>/class_<id>/ ذخیره میشه
    // نام فایل: <نام معلم>_to_<نام کلاس>_تاریخ.پسوند
    $filePath = FileHelper::save($_FILES['file'], 'assignments', (int) $user['school_id'], (int) $classId, $user['full_name'], $className);
}

$stmt = $db->prepare(
    "INSERT INTO assignments (school_id, class_id, teacher_id, title, description, type, file_path, due_date, max_score, late_penalty)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$user['school_id'], $classId, $user['user_id'], $title, $description, $type, $filePath, $dueDate, $maxScore, $latePenalty]);

Response::json(['success' => true, 'assignment_id' => (int) $db->lastInsertId()]);
