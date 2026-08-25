<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FileHelper.php';

$user = Auth::requireRole(['student']);

// این اندپوینت با multipart/form-data صدا زده میشه (چون ممکنه عکس/ویس داشته باشه)
$assignmentId = $_POST['assignment_id'] ?? null;
$contentType  = $_POST['content_type'] ?? 'text'; // text | image | voice
$textContent  = $_POST['content'] ?? null;

if (!$assignmentId) {
    Response::json(['success' => false, 'message' => 'assignment_id الزامی است'], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT a.*, u.full_name AS teacher_name FROM assignments a JOIN users u ON u.id = a.teacher_id WHERE a.id = ?");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();

if (!$assignment || (int) $assignment['class_id'] !== (int) $user['class_id']) {
    Response::json(['success' => false, 'message' => 'این تکلیف متعلق به کلاس شما نیست'], 422);
}

$content = $textContent;
if ($contentType !== 'text' && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // فایل داخل uploads/submissions/school_<id>/class_<id>/ ذخیره میشه
    // نام فایل: <نام دانش‌آموز>_to_<نام معلم>_تاریخ.پسوند -> همیشه مشخصه کی برای کی فرستاده
    $content = FileHelper::save(
        $_FILES['file'], 'submissions', (int) $user['school_id'], (int) $user['class_id'],
        $user['full_name'], $assignment['teacher_name']
    );
}

if (!$content) {
    Response::json(['success' => false, 'message' => 'محتوای تکلیف (متن یا فایل) الزامی است'], 422);
}

// اگه ددلاین تکلیف گذشته باشه، به‌عنوان دیرکرد ثبت می‌شه (برای کسر نمره‌ی احتمالی)
$isLate = 0;
if (!empty($assignment['due_date']) && strtotime($assignment['due_date']) < time()) {
    $isLate = 1;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        "INSERT INTO assignment_submissions (assignment_id, student_id, content_type, content, is_late) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$assignmentId, $user['user_id'], $contentType, $content, $isLate]);
    $submissionId = (int) $db->lastInsertId();

    // اگه ترد گفتگو برای همین تحویل وجود نداشت، بازش می‌کنیم (وضعیت اولیه: در انتظار معلم)
    $stmt = $db->prepare("SELECT id FROM message_threads WHERE assignment_submission_id = ?");
    $stmt->execute([$submissionId]);
    if (!$stmt->fetch()) {
        $stmt = $db->prepare(
            "INSERT INTO message_threads (school_id, class_id, student_id, teacher_id, assignment_submission_id, status)
             VALUES (?, ?, ?, ?, ?, 'awaiting_teacher')"
        );
        $stmt->execute([$user['school_id'], $user['class_id'], $user['user_id'], $assignment['teacher_id'], $submissionId]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    Response::json(['success' => false, 'message' => 'خطا در ثبت تکلیف'], 500);
}

Response::json(['success' => true, 'submission_id' => $submissionId]);
