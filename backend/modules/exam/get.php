<?php
// جزئیات یه امتحان به همراه سوالاتش. معلم پاسخ درست رو هم می‌بینه، دانش‌آموز نه.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher', 'student']);
$examId = $_GET['exam_id'] ?? null;
if (!$examId) Response::json(['success' => false, 'message' => 'exam_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$examId]);
$exam = $stmt->fetch();
if (!$exam) Response::json(['success' => false, 'message' => 'امتحان یافت نشد'], 404);

if ($user['role'] === 'teacher' && (int) $exam['teacher_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}
if ($user['role'] === 'student' && (int) $exam['class_id'] !== (int) $user['class_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}

$stmt = $db->prepare("SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY order_index ASC, id ASC");
$stmt->execute([$examId]);
$questions = $stmt->fetchAll();

$optStmt = $db->prepare("SELECT * FROM exam_options WHERE question_id = ?");
foreach ($questions as &$q) {
    if ($q['type'] === 'multiple_choice') {
        $optStmt->execute([$q['id']]);
        $opts = $optStmt->fetchAll();
        if ($user['role'] === 'student') {
            foreach ($opts as &$o) unset($o['is_correct']); // پاسخ درست از دانش‌آموز مخفی می‌مونه
        }
        $q['options'] = $opts;
    }
}

$exam['questions'] = $questions;
Response::json(['success' => true, 'data' => $exam]);
