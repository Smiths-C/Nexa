<?php
// هر کاربر لاگین‌کرده (مدیر/معلم/دانش‌آموز) تم مدرسه‌ی خودش رو می‌گیره تا اپ رو با همون رنگ‌بندی نشون بده
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['admin', 'manager', 'teacher', 'student']);
$schoolId = $user['school_id'] ?? ($_GET['school_id'] ?? null);
if (!$schoolId) Response::json(['success' => false, 'message' => 'school_id مشخص نیست'], 422);

$db = Database::getConnection();
$stmt = $db->prepare(
    "SELECT id, name, theme_primary_color, theme_secondary_color, logo_path, free_message_limit FROM schools WHERE id = ?"
);
$stmt->execute([$schoolId]);
$school = $stmt->fetch();
if (!$school) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

Response::json(['success' => true, 'data' => $school]);
