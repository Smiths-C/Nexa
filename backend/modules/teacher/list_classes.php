<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher']);
$db = Database::getConnection();

// فقط کلاس‌هایی که مدیر مدرسه به این معلم اختصاص داده، از پایه پایین به بالا
$stmt = $db->prepare(
    "SELECT c.* FROM classes c
     JOIN class_teacher ct ON ct.class_id = c.id
     WHERE ct.teacher_id = ?
     ORDER BY c.grade_level ASC, c.name ASC"
);
$stmt->execute([$user['user_id']]);
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
