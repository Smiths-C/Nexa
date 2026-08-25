<?php
/**
 * افزودن یک سوال به امتحان. معلم مشخص می‌کنه سوال «چهارگزینه‌ای» (multiple_choice)
 * هست یا «تشریحی» (essay). برای چهارگزینه‌ای، دقیقا ۴ گزینه لازمه و باید مشخص بشه
 * کدوم گزینه درسته (is_correct).
 *
 * ورودی نمونه برای چهارگزینه‌ای:
 * {
 *   "exam_id": 1,
 *   "question_text": "پایتخت ایران کجاست؟",
 *   "type": "multiple_choice",
 *   "options": [
 *     {"text": "تهران", "is_correct": true},
 *     {"text": "اصفهان", "is_correct": false},
 *     {"text": "شیراز", "is_correct": false},
 *     {"text": "مشهد", "is_correct": false}
 *   ]
 * }
 *
 * ورودی نمونه برای تشریحی:
 * { "exam_id": 1, "question_text": "دلایل انقلاب مشروطه را توضیح دهید.", "type": "essay" }
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['teacher']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$examId = $input['exam_id'] ?? null;
$questionText = $input['question_text'] ?? null;
$type = $input['type'] ?? null; // multiple_choice | essay
$options = $input['options'] ?? [];
$orderIndex = $input['order_index'] ?? 0;

if (!$examId || !$questionText || !in_array($type, ['multiple_choice', 'essay'], true)) {
    Response::json(['success' => false, 'message' => 'exam_id، question_text و type (multiple_choice یا essay) الزامی است'], 422);
}

if ($type === 'multiple_choice') {
    if (count($options) !== 4) {
        Response::json(['success' => false, 'message' => 'سوال چهارگزینه‌ای باید دقیقا ۴ گزینه داشته باشد'], 422);
    }
    $correctCount = count(array_filter($options, fn($o) => !empty($o['is_correct'])));
    if ($correctCount !== 1) {
        Response::json(['success' => false, 'message' => 'دقیقا یکی از گزینه‌ها باید درست علامت‌گذاری شود'], 422);
    }
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT school_id, teacher_id FROM exams WHERE id = ?");
$stmt->execute([$examId]);
$exam = $stmt->fetch();
if (!$exam || (int) $exam['teacher_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید یا امتحان یافت نشد'], 403);
}
FeatureGate::require($db, (int) $exam['school_id'], FeatureGate::EXAMS);

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        "INSERT INTO exam_questions (exam_id, question_text, type, order_index) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$examId, $questionText, $type, $orderIndex]);
    $questionId = (int) $db->lastInsertId();

    if ($type === 'multiple_choice') {
        $stmt = $db->prepare("INSERT INTO exam_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
        foreach ($options as $opt) {
            $stmt->execute([$questionId, $opt['text'], !empty($opt['is_correct']) ? 1 : 0]);
        }
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    Response::json(['success' => false, 'message' => 'خطا در ثبت سوال'], 500);
}

Response::json(['success' => true, 'question_id' => $questionId]);
