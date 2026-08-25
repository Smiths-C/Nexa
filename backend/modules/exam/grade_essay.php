<?php
// معلم به یه پاسخ تشریحی نمره می‌ده
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$answerId = $input['answer_id'] ?? null;
$score = $input['score'] ?? null;

if (!$answerId || $score === null) {
    Response::json(['success' => false, 'message' => 'answer_id و score الزامی است'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare(
    "SELECT ea.id, e.teacher_id FROM exam_answers ea
     JOIN exam_submissions es ON es.id = ea.exam_submission_id
     JOIN exams e ON e.id = es.exam_id
     WHERE ea.id = ?"
);
$stmt->execute([$answerId]);
$row = $stmt->fetch();
if (!$row || (int) $row['teacher_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}

$stmt = $db->prepare("UPDATE exam_answers SET score = ? WHERE id = ?");
$stmt->execute([$score, $answerId]);

Response::json(['success' => true, 'message' => 'نمره ثبت شد']);
