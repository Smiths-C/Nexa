<?php
// لیست تحویل‌های یک امتحان برای معلم (نمره‌ی هر دانش‌آموز + جزئیات پاسخ‌ها)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher']);
$examId = $_GET['exam_id'] ?? null;
if (!$examId) Response::json(['success' => false, 'message' => 'exam_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM exams WHERE id = ? AND teacher_id = ?");
$stmt->execute([$examId, $user['user_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);

$stmt = $db->prepare(
    "SELECT es.id AS submission_id, es.student_id, u.full_name AS student_name, es.submitted_at
     FROM exam_submissions es JOIN users u ON u.id = es.student_id
     WHERE es.exam_id = ? ORDER BY es.submitted_at ASC"
);
$stmt->execute([$examId]);
$submissions = $stmt->fetchAll();

$ansStmt = $db->prepare(
    "SELECT ea.*, q.question_text, q.type FROM exam_answers ea
     JOIN exam_questions q ON q.id = ea.question_id
     WHERE ea.exam_submission_id = ?"
);
foreach ($submissions as &$s) {
    $ansStmt->execute([$s['submission_id']]);
    $answers = $ansStmt->fetchAll();
    $s['answers'] = $answers;
    $total = 0;
    $hasUngraded = false;
    foreach ($answers as $a) {
        if ($a['score'] === null) $hasUngraded = true;
        else $total += (float) $a['score'];
    }
    $s['total_score'] = $total;
    $s['fully_graded'] = !$hasUngraded;
}

Response::json(['success' => true, 'data' => $submissions]);
