<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$threadId = $input['thread_id'] ?? null;
if (!$threadId) Response::json(['success' => false, 'message' => 'thread_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM message_threads WHERE id = ? AND teacher_id = ?");
$stmt->execute([$threadId, $user['user_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);

$stmt = $db->prepare("UPDATE message_threads SET status = 'allowed' WHERE id = ?");
$stmt->execute([$threadId]);

Response::json(['success' => true, 'message' => 'گفتگو برای دانش‌آموز باز شد']);
