<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher']);
$classId = $_GET['class_id'] ?? null;
if (!$classId) Response::json(['success' => false, 'message' => 'class_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
$stmt->execute([$classId, $user['user_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);

$stmt = $db->prepare("SELECT * FROM assignments WHERE class_id = ? ORDER BY created_at DESC");
$stmt->execute([$classId]);
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
