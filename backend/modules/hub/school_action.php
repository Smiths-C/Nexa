<?php
// اجرای دستور تعلیق/رفع‌تعلیق/حذف که از طرف هاب مرکزی رسیده
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/HubAuth.php';

HubAuth::require();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$schoolId = $input['school_id'] ?? null;
$action = $input['action'] ?? null;

if (!$schoolId || !in_array($action, ['suspend', 'unsuspend', 'delete'], true)) {
    Response::json(['success' => false, 'message' => 'school_id و action معتبر الزامی است'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

if ($action === 'delete') {
    $stmt = $db->prepare("DELETE FROM schools WHERE id = ?");
    $stmt->execute([$schoolId]);
} else {
    $status = $action === 'suspend' ? 'suspended' : 'approved';
    $stmt = $db->prepare("UPDATE schools SET status = ? WHERE id = ?");
    $stmt->execute([$status, $schoolId]);
}

Response::json(['success' => true]);
