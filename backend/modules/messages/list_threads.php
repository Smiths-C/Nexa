<?php
// لیست گفتگوها: معلم/دانش‌آموز فقط گفتگوهای خودشون رو می‌بینن؛
// مدیر همه‌ی گفتگوهای بین معلم‌ها و دانش‌آموزهای مدرسه‌ی خودش رو می‌بینه (نظارتی)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['teacher', 'student', 'manager']);
$db = Database::getConnection();

if ($user['role'] === 'teacher') {
    $stmt = $db->prepare(
        "SELECT t.*, s.full_name AS student_name FROM message_threads t
         JOIN users s ON s.id = t.student_id
         WHERE t.teacher_id = ? ORDER BY t.created_at DESC"
    );
    $stmt->execute([$user['user_id']]);
} elseif ($user['role'] === 'student') {
    $stmt = $db->prepare(
        "SELECT t.*, tc.full_name AS teacher_name FROM message_threads t
         JOIN users tc ON tc.id = t.teacher_id
         WHERE t.student_id = ? ORDER BY t.created_at DESC"
    );
    $stmt->execute([$user['user_id']]);
} else { // manager: نظارت روی همه‌ی گفتگوهای مدرسه‌ی خودش
    $stmt = $db->prepare(
        "SELECT t.*, s.full_name AS student_name, tc.full_name AS teacher_name, c.name AS class_name
         FROM message_threads t
         JOIN users s ON s.id = t.student_id
         JOIN users tc ON tc.id = t.teacher_id
         JOIN classes c ON c.id = t.class_id
         WHERE t.school_id = ? ORDER BY t.created_at DESC"
    );
    $stmt->execute([$user['school_id']]);
}

Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
