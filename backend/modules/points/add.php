<?php
// معلم یا مدیر می‌تونه به یه دانش‌آموزِ یه کلاس، امتیاز مثبت یا منفی بده (نیازمند قابلیت points)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['teacher', 'manager']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$classId = $input['class_id'] ?? null;
$studentId = $input['student_id'] ?? null;
$delta = $input['delta'] ?? null; // مثبت یا منفی، مثلا 1- یا 2
$reason = $input['reason'] ?? null;

if (!$classId || !$studentId || $delta === null) {
    Response::json(['success' => false, 'message' => 'class_id، student_id و delta الزامی است'], 422);
}

$db = Database::getConnection();
FeatureGate::require($db, (int) $user['school_id'], FeatureGate::POINTS);

// چک دسترسی: معلم باید به این کلاس تخصیص داشته باشه؛ مدیر فقط باید کلاس متعلق به مدرسه‌ی خودش باشه
if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$classId, $user['user_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'شما به این کلاس دسترسی ندارید'], 403);
} else {
    $stmt = $db->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
    $stmt->execute([$classId, $user['school_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'این کلاس متعلق به مدرسه‌ی شما نیست'], 403);
}

$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND class_id = ? AND role = 'student'");
$stmt->execute([$studentId, $classId]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'این دانش‌آموز در این کلاس نیست'], 422);

$stmt = $db->prepare(
    "INSERT INTO student_points (school_id, class_id, student_id, given_by, delta, reason) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$user['school_id'], $classId, $studentId, $user['user_id'], $delta, $reason]);

Response::json(['success' => true, 'point_id' => (int) $db->lastInsertId()]);
