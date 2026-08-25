<?php
/**
 * دانش‌آموز پاسخ‌های امتحان رو می‌فرسته. سوالات چهارگزینه‌ای خودکار تصحیح می‌شن،
 * سوالات تشریحی بدون نمره می‌مونن تا معلم دستی نمره بده.
 *
 * ورودی: { "exam_id": 1, "answers": [
 *   {"question_id": 5, "selected_option_id": 12},
 *   {"question_id": 6, "essay_answer": "متن پاسخ..."}
 * ]}
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['student']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$examId = $input['exam_id'] ?? null;
$answers = $input['answers'] ?? [];

if (!$examId || !is_array($answers) || count($answers) === 0) {
    Response::json(['success' => false, 'message' => 'exam_id و answers الزامی است'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$examId]);
$exam = $stmt->fetch();
if (!$exam || (int) $exam['class_id'] !== (int) $user['class_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید یا امتحان یافت نشد'], 403);
}

$stmt = $db->prepare("SELECT id FROM exam_submissions WHERE exam_id = ? AND student_id = ?");
$stmt->execute([$examId, $user['user_id']]);
if ($stmt->fetch()) {
    Response::json(['success' => false, 'message' => 'شما قبلا این امتحان را ارسال کرده‌اید'], 409);
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO exam_submissions (exam_id, student_id) VALUES (?, ?)");
    $stmt->execute([$examId, $user['user_id']]);
    $submissionId = (int) $db->lastInsertId();

    $qStmt = $db->prepare("SELECT * FROM exam_questions WHERE id = ? AND exam_id = ?");
    $optStmt = $db->prepare("SELECT is_correct FROM exam_options WHERE id = ?");
    $insStmt = $db->prepare(
        "INSERT INTO exam_answers (exam_submission_id, question_id, selected_option_id, essay_answer, score) VALUES (?, ?, ?, ?, ?)"
    );

    foreach ($answers as $ans) {
        $qStmt->execute([$ans['question_id'], $examId]);
        $question = $qStmt->fetch();
        if (!$question) continue; // سوال متعلق به این امتحان نیست، رد می‌شه

        if ($question['type'] === 'multiple_choice') {
            $selectedId = $ans['selected_option_id'] ?? null;
            $score = 0;
            if ($selectedId) {
                $optStmt->execute([$selectedId]);
                $opt = $optStmt->fetch();
                $score = ($opt && (int) $opt['is_correct'] === 1) ? 1 : 0;
            }
            $insStmt->execute([$submissionId, $question['id'], $selectedId, null, $score]);
        } else {
            // تشریحی: نمره خالی می‌مونه تا معلم دستی نمره بده
            $insStmt->execute([$submissionId, $question['id'], null, $ans['essay_answer'] ?? null, null]);
        }
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    Response::json(['success' => false, 'message' => 'خطا در ثبت پاسخ‌ها'], 500);
}

Response::json(['success' => true, 'submission_id' => $submissionId]);
