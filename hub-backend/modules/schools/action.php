<?php
// تعلیق/رفع‌تعلیق/حذف یک مدرسه؛ هاب دستور رو به همون هاست مقصد پاس می‌ده
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/HostClient.php';

Auth::requireAdmin();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$registryId = $input['registry_id'] ?? null;
$action = $input['action'] ?? null; // suspend | unsuspend | delete

if (!$registryId || !in_array($action, ['suspend', 'unsuspend', 'delete'], true)) {
    Response::json(['success' => false, 'message' => 'registry_id و action (suspend/unsuspend/delete) الزامی است'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare(
    "SELECT sr.*, h.api_base_url, h.shared_secret FROM schools_registry sr
     JOIN hosts h ON h.id = sr.host_id WHERE sr.id = ?"
);
$stmt->execute([$registryId]);
$school = $stmt->fetch();
if (!$school) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

$result = HostClient::call($school['api_base_url'], 'hub/school-action', [
    'school_id' => $school['remote_school_id'],
    'action' => $action,
], $school['shared_secret']);

if (!($result['success'] ?? false)) {
    Response::json(['success' => false, 'message' => $result['message'] ?? 'خطا در اجرای عملیات روی هاست'], 502);
}

$newStatus = ['suspend' => 'suspended', 'unsuspend' => 'active', 'delete' => 'deleted'][$action];
$stmt = $db->prepare("UPDATE schools_registry SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $registryId]);

Response::json(['success' => true, 'message' => 'انجام شد']);
