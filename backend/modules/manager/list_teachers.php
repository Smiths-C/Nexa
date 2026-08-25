<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager']);
$db = Database::getConnection();

$stmt = $db->prepare("SELECT id, full_name, username, phone FROM users WHERE role='teacher' AND school_id = ? ORDER BY full_name ASC");
$stmt->execute([$user['school_id']]);
$teachers = $stmt->fetchAll();

// برای هر معلم، کلاس‌هایی که بهش تخصیص داده شده رو هم برمی‌گردونیم
$stmt2 = $db->prepare(
    "SELECT c.id, c.name, c.grade_level FROM classes c
     JOIN class_teacher ct ON ct.class_id = c.id
     WHERE ct.teacher_id = ? ORDER BY c.grade_level ASC"
);
foreach ($teachers as &$t) {
    $stmt2->execute([$t['id']]);
    $t['classes'] = $stmt2->fetchAll();
}

Response::json(['success' => true, 'data' => $teachers]);
