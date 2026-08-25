<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager', 'teacher', 'admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = $input['session_id'] ?? null;
if (!$sessionId) Response::json(['success' => false, 'message' => 'session_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM online_classes WHERE id = ?");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();
if (!$session) Response::json(['success' => false, 'message' => 'جلسه یافت نشد'], 404);

if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$session['class_id'], $user['user_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
} elseif ($user['role'] === 'manager' && (int) $session['school_id'] !== (int) $user['school_id']) {
    Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}

$stmt = $db->prepare("UPDATE online_classes SET status = 'ended', ended_at = NOW() WHERE id = ?");
$stmt->execute([$sessionId]);

Response::json(['success' => true, 'message' => 'جلسه پایان یافت']);
