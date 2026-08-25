<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher']);
$classId = $_GET['class_id'] ?? null;
if (!$classId) Response::json(['success' => false, 'message' => 'class_id الزامی است'], 422);

$db = Database::getConnection();

// چک دسترسی معلم به این کلاس خاص
$stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
$stmt->execute([$classId, $user['user_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'شما به این کلاس دسترسی ندارید'], 403);

$stmt = $db->prepare("SELECT id, full_name, username, phone FROM users WHERE role='student' AND class_id = ? ORDER BY full_name ASC");
$stmt->execute([$classId]);
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
