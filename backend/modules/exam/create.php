<?php
// معلم برای یکی از کلاس‌های خودش یه امتحان می‌سازه (بعدش سوالات جدا اضافه می‌شن)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['teacher']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$classId = $input['class_id'] ?? null;
$title = $input['title'] ?? null;
$description = $input['description'] ?? null;

if (!$classId || !$title) {
    Response::json(['success' => false, 'message' => 'class_id و title الزامی است'], 422);
}

$db = Database::getConnection();
FeatureGate::require($db, (int) $user['school_id'], FeatureGate::EXAMS);

$stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
$stmt->execute([$classId, $user['user_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'شما به این کلاس دسترسی ندارید'], 403);

$stmt = $db->prepare("INSERT INTO exams (school_id, class_id, teacher_id, title, description) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$user['school_id'], $classId, $user['user_id'], $title, $description]);

Response::json(['success' => true, 'exam_id' => (int) $db->lastInsertId()]);
