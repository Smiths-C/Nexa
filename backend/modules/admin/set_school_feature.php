<?php
// روشن/خاموش کردن یک قابلیت مشخص (exams / late_penalty / resubmit / points) برای یک مدرسه‌ی خاص
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$schoolId = $input['school_id'] ?? null;
$featureKey = $input['feature_key'] ?? null;
$enabled = $input['enabled'] ?? null; // true / false

$validKeys = ['exams', 'late_penalty', 'resubmit', 'points', 'online_class'];

if (!$schoolId || !$featureKey || $enabled === null) {
    Response::json(['success' => false, 'message' => 'school_id، feature_key و enabled الزامی است'], 422);
}
if (!in_array($featureKey, $validKeys, true)) {
    Response::json(['success' => false, 'message' => 'feature_key نامعتبر است. مقادیر مجاز: ' . implode(', ', $validKeys)], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

$stmt = $db->prepare(
    "INSERT INTO school_features (school_id, feature_key, enabled) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)"
);
$stmt->execute([$schoolId, $featureKey, $enabled ? 1 : 0]);

Response::json(['success' => true, 'message' => 'قابلیت به‌روزرسانی شد']);
