<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager']);
$db = Database::getConnection();

$classId = $_GET['class_id'] ?? null;
if ($classId) {
    $stmt = $db->prepare(
        "SELECT u.id, u.full_name, u.username, u.phone, u.class_id, c.name AS class_name FROM users u
         JOIN classes c ON c.id = u.class_id
         WHERE u.role='student' AND u.school_id = ? AND u.class_id = ?
         ORDER BY u.full_name ASC"
    );
    $stmt->execute([$user['school_id'], $classId]);
} else {
    $stmt = $db->prepare(
        "SELECT u.id, u.full_name, u.username, u.phone, u.class_id, c.name AS class_name FROM users u
         LEFT JOIN classes c ON c.id = u.class_id
         WHERE u.role='student' AND u.school_id = ?
         ORDER BY c.grade_level ASC, u.full_name ASC"
    );
    $stmt->execute([$user['school_id']]);
}

Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
