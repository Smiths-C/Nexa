<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['student']);
$db = Database::getConnection();

$stmt = $db->prepare(
    "SELECT a.*, u.full_name AS teacher_name,
        (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.student_id = ?) AS submitted
     FROM assignments a
     JOIN users u ON u.id = a.teacher_id
     WHERE a.class_id = ?
     ORDER BY a.created_at DESC"
);
$stmt->execute([$user['user_id'], $user['class_id']]);
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
