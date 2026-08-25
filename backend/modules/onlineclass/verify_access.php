<?php
/**
 * سرور سیگنالینگ (Node.js روی VPS) وقتی کسی می‌خواد وارد یه جلسه‌ی کلاس آنلاین
 * بشه، توکن JWT همون کاربر رو (که از اپ گرفته) به همین اندپوینت می‌فرسته تا
 * بک‌اند تایید کنه این آدم واقعا اجازه‌ی ورود به این room رو داره یا نه، و
 * نقشش تو room چیه (میزبان/ناظر یا فقط دانش‌آموز).
 *
 * یعنی VPS هیچ دیتابیس یا منطق تشخیص هویتی نداره؛ همیشه از همین هاست می‌پرسه.
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['manager', 'teacher', 'student', 'admin']);
$sessionId = $_GET['session_id'] ?? null;
if (!$sessionId) Response::json(['success' => false, 'message' => 'session_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM online_classes WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();
if (!$session) Response::json(['success' => false, 'message' => 'جلسه یافت نشد'], 404);
if ($session['status'] === 'ended') Response::json(['success' => false, 'message' => 'این جلسه پایان یافته است'], 410);

FeatureGate::require($db, (int) $session['school_id'], FeatureGate::ONLINE_CLASS);

$roleInRoom = null; // host | moderator | participant
$isMainTeacher = false;

if ($user['role'] === 'admin') {
    $roleInRoom = 'moderator';
} elseif ($user['role'] === 'manager') {
    if ((int) $session['school_id'] !== (int) $user['school_id']) {
        Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
    }
    $roleInRoom = 'moderator';
} elseif ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$session['class_id'], $user['user_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'شما به این کلاس دسترسی ندارید'], 403);
    $isMainTeacher = (int) $session['main_teacher_id'] === (int) $user['user_id'];
    $roleInRoom = $isMainTeacher ? 'host' : 'moderator';
} elseif ($user['role'] === 'student') {
    if ((int) $session['class_id'] !== (int) $user['class_id']) {
        Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
    }
    $roleInRoom = 'participant';
}

// اولین باری که یه میزبان/ناظر وارد می‌شه، جلسه "زنده" علامت می‌خوره
if (in_array($roleInRoom, ['host', 'moderator'], true) && $session['status'] === 'scheduled') {
    $stmt = $db->prepare("UPDATE online_classes SET status = 'live', started_at = NOW() WHERE id = ?");
    $stmt->execute([$sessionId]);
}

Response::json([
    'success' => true,
    'data' => [
        'user_id'        => (int) $user['user_id'],
        'full_name'      => $user['full_name'],
        'role'           => $user['role'],
        'role_in_room'   => $roleInRoom, // host | moderator | participant
        'is_main_teacher' => $isMainTeacher,
        'session_id'     => (int) $sessionId,
        'class_id'       => (int) $session['class_id'],
    ],
]);
