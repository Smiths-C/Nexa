<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager', 'teacher', 'student', 'admin']);
$classId = $_GET['class_id'] ?? ($user['role'] === 'student' ? $user['class_id'] : null);
if (!$classId) Response::json(['success' => false, 'message' => 'class_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch();
if (!$class) Response::json(['success' => false, 'message' => 'کلاس یافت نشد'], 404);

if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$classId, $user['user_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
} elseif ($user['role'] === 'student') {
    if ((int) $classId !== (int) $user['class_id']) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
} elseif ($user['role'] === 'manager') {
    if ((int) $class['school_id'] !== (int) $user['school_id']) Response::json(['success' => false, 'message' => 'دسترسی ندارید'], 403);
}
// admin دسترسی کامل داره

$stmt = $db->prepare(
    "SELECT oc.*, u.full_name AS main_teacher_name FROM online_classes oc
     JOIN users u ON u.id = oc.main_teacher_id
     WHERE oc.class_id = ? ORDER BY oc.created_at DESC"
);
$stmt->execute([$classId]);

Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
