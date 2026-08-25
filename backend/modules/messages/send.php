<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FileHelper.php';

$user = Auth::requireRole(['teacher', 'student']);

// multipart/form-data چون ممکنه عکس یا ویس باشه
$threadId    = $_POST['thread_id'] ?? null;
$contentType = $_POST['content_type'] ?? 'text'; // text | image | voice
$textContent = $_POST['content'] ?? null;

if (!$threadId) {
    Response::json(['success' => false, 'message' => 'thread_id الزامی است'], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare(
    "SELECT t.*, s.full_name AS student_name, tc.full_name AS teacher_name, sc.free_message_limit
     FROM message_threads t
     JOIN users s   ON s.id = t.student_id
     JOIN users tc  ON tc.id = t.teacher_id
     JOIN schools sc ON sc.id = t.school_id
     WHERE t.id = ?"
);
$stmt->execute([$threadId]);
$thread = $stmt->fetch();

if (!$thread) {
    Response::json(['success' => false, 'message' => 'گفتگو یافت نشد'], 404);
}
if ($user['role'] === 'student' && (int) $thread['student_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}
if ($user['role'] === 'teacher' && (int) $thread['teacher_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}

// --- قانون اصلی: دانش‌آموز فقط به تعداد free_message_limit این مدرسه (پیش‌فرض ۱) پیام آزاد داره ---
if ($user['role'] === 'student') {
    if ($thread['status'] === 'closed') {
        Response::json(['success' => false, 'message' => 'باید منتظر پاسخ یا اجازه‌ی معلم برای ادامه گفتگو باشید'], 403);
    }
    if ($thread['status'] === 'awaiting_teacher') {
        $limit = (int) $thread['free_message_limit'];
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM messages WHERE thread_id = ? AND sender_role = 'student'");
        $stmt->execute([$threadId]);
        if ((int) $stmt->fetch()['c'] >= $limit) {
            Response::json(['success' => false, 'message' => "شما فقط $limit پیام آزاد می‌توانید بفرستید، منتظر اجازه‌ی معلم بمانید"], 403);
        }
    }
}

$senderName   = $user['role'] === 'teacher' ? $thread['teacher_name'] : $thread['student_name'];
$receiverName = $user['role'] === 'teacher' ? $thread['student_name'] : $thread['teacher_name'];

$content = $textContent;
if ($contentType !== 'text' && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // فایل داخل uploads/messages/school_<id>/class_<id>/ ذخیره میشه، نام فایل: فرستنده_to_گیرنده_تاریخ
    $content = FileHelper::save(
        $_FILES['file'], 'messages', (int) $thread['school_id'], (int) $thread['class_id'], $senderName, $receiverName
    );
}
if (!$content) {
    Response::json(['success' => false, 'message' => 'محتوای پیام (متن، عکس یا ویس) الزامی است'], 422);
}

$stmt = $db->prepare(
    "INSERT INTO messages (thread_id, sender_id, sender_role, content_type, content) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$threadId, $user['user_id'], $user['role'], $contentType, $content]);

// بعد از اولین پیام دانش‌آموز، گفتگو بسته می‌شه تا معلم صریحا اجازه‌ی ادامه بده
if ($user['role'] === 'student' && $thread['status'] === 'awaiting_teacher') {
    $stmt = $db->prepare("UPDATE message_threads SET status = 'closed' WHERE id = ?");
    $stmt->execute([$threadId]);
}

Response::json(['success' => true, 'message_id' => (int) $db->lastInsertId()]);
