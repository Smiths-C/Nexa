<?php
// معلم به یک تحویل‌شده نمره می‌ده و/یا درخواست ارسال مجدد می‌کنه.
// اگه ارسال دیر بوده و تکلیف late_penalty داشته باشه، سقف نمره خودکار کم می‌شه
// (مثلا از ۲۰ می‌شه ۱۹) و اگه معلم نمره‌ی بالاتر از سقف بده، برمی‌گرده خطا.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['teacher']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$submissionId    = $input['submission_id'] ?? null;
$score           = array_key_exists('score', $input) ? $input['score'] : null;
$requestResubmit = $input['request_resubmit'] ?? false;

if (!$submissionId) {
    Response::json(['success' => false, 'message' => 'submission_id الزامی است'], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare(
    "SELECT s.*, a.teacher_id, a.max_score, a.late_penalty, a.school_id
     FROM assignment_submissions s
     JOIN assignments a ON a.id = s.assignment_id
     WHERE s.id = ?"
);
$stmt->execute([$submissionId]);
$submission = $stmt->fetch();

if (!$submission || (int) $submission['teacher_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید یا تحویل یافت نشد'], 403);
}

if ($requestResubmit) {
    FeatureGate::require($db, (int) $submission['school_id'], FeatureGate::RESUBMIT);
}

// محاسبه‌ی سقف مجاز نمره با در نظر گرفتن کسر نمره‌ی تاخیر
$effectiveMax = $submission['max_score'];
if ($submission['is_late'] && $submission['late_penalty'] !== null
    && FeatureGate::isEnabled($db, (int) $submission['school_id'], FeatureGate::LATE_PENALTY)) {
    $effectiveMax = max(0, (float) $submission['max_score'] - (float) $submission['late_penalty']);
}

if ($score !== null && $effectiveMax !== null && (float) $score > (float) $effectiveMax) {
    Response::json(['success' => false, 'message' => "این تکلیف دیر ارسال شده؛ سقف نمره‌ی مجاز الان {$effectiveMax} است"], 422);
}

$fields = [];
$params = [];
if ($score !== null) {
    $fields[] = 'score = ?';
    $params[] = $score;
}
$fields[] = 'status = ?';
$params[] = $requestResubmit ? 'resubmit_requested' : 'pending';

$params[] = $submissionId;
$stmt = $db->prepare('UPDATE assignment_submissions SET ' . implode(', ', $fields) . ' WHERE id = ?');
$stmt->execute($params);

Response::json(['success' => true, 'message' => 'وضعیت تکلیف به‌روزرسانی شد', 'effective_max_score' => $effectiveMax]);
