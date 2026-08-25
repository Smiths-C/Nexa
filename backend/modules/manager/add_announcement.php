<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['title']) || empty($input['content'])) {
    Response::json(['success' => false, 'message' => 'عنوان و متن اطلاعیه الزامی است'], 422);
}

$db = Database::getConnection();
$classId = $input['class_id'] ?? null; // خالی = کل مدرسه

if ($classId) {
    $stmt = $db->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
    $stmt->execute([$classId, $user['school_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'کلاس نامعتبر است'], 422);
}

$stmt = $db->prepare(
    "INSERT INTO announcements (school_id, manager_id, class_id, title, content) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$user['school_id'], $user['user_id'], $classId, $input['title'], $input['content']]);

Response::json(['success' => true, 'announcement_id' => (int) $db->lastInsertId()]);
