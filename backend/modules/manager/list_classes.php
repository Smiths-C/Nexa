<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager']);
$db = Database::getConnection();
// همیشه فقط کلاس‌های مدرسه‌ی خود مدیر، مرتب از پایه پایین به بالا
$stmt = $db->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY grade_level ASC, name ASC");
$stmt->execute([$user['school_id']]);
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
