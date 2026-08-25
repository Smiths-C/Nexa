<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['class_id', 'teacher_id'] as $f) {
    if (empty($input[$f])) Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
$stmt->execute([$input['class_id'], $user['school_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'کلاس نامعتبر است'], 422);

$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role='teacher' AND school_id = ?");
$stmt->execute([$input['teacher_id'], $user['school_id']]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'معلم نامعتبر است'], 422);

$stmt = $db->prepare("INSERT IGNORE INTO class_teacher (class_id, teacher_id) VALUES (?, ?)");
$stmt->execute([$input['class_id'], $input['teacher_id']]);

Response::json(['success' => true, 'message' => 'معلم به کلاس اضافه شد']);
