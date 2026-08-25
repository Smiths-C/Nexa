<?php
// لیست امتحانات یک کلاس - هم معلم (اگه به کلاس دسترسی داشته باشه) هم دانش‌آموز (کلاس خودش)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher', 'student']);
$classId = $_GET['class_id'] ?? ($user['role'] === 'student' ? $user['class_id'] : null);
if (!$classId) Response::json(['success' => false, 'message' => 'class_id الزامی است'], 422);

$db = Database::getConnection();

if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$classId, $user['user_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
} elseif ((int) $classId !== (int) $user['class_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}

$stmt = $db->prepare("SELECT id, title, description, created_at FROM exams WHERE class_id = ? ORDER BY created_at DESC");
$stmt->execute([$classId]);
$exams = $stmt->fetchAll();

if ($user['role'] === 'student') {
    foreach ($exams as &$e) {
        $stmt = $db->prepare("SELECT id FROM exam_submissions WHERE exam_id = ? AND student_id = ?");
        $stmt->execute([$e['id'], $user['user_id']]);
        $e['submitted'] = (bool) $stmt->fetch();
    }
}

Response::json(['success' => true, 'data' => $exams]);
