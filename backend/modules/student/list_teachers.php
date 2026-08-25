<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['student']);
$db = Database::getConnection();

// دانش‌آموز فقط معلم‌های همون کلاس خودش رو می‌بینه
$stmt = $db->prepare(
    "SELECT u.id, u.full_name FROM users u
     JOIN class_teacher ct ON ct.teacher_id = u.id
     WHERE ct.class_id = ?
     ORDER BY u.full_name ASC"
);
$stmt->execute([$user['class_id']]);
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
