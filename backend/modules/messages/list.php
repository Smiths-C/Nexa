<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher', 'student', 'manager']);
$threadId = $_GET['thread_id'] ?? null;
if (!$threadId) Response::json(['success' => false, 'message' => 'thread_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM message_threads WHERE id = ?");
$stmt->execute([$threadId]);
$thread = $stmt->fetch();
if (!$thread) Response::json(['success' => false, 'message' => 'یافت نشد'], 404);

if ($user['role'] === 'student' && (int) $thread['student_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}
if ($user['role'] === 'teacher' && (int) $thread['teacher_id'] !== (int) $user['user_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}
// مدیر فقط می‌تونه گفتگوهای مدرسه‌ی خودش رو ببینه (صرفا مشاهده، نظارتی)
if ($user['role'] === 'manager' && (int) $thread['school_id'] !== (int) $user['school_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}

$stmt = $db->prepare("SELECT * FROM messages WHERE thread_id = ? ORDER BY created_at ASC");
$stmt->execute([$threadId]);

Response::json(['success' => true, 'thread_status' => $thread['status'], 'data' => $stmt->fetchAll()]);
