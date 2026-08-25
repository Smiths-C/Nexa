<?php
// وضعیت همه‌ی قابلیت‌های قابل‌تنظیم برای یک مدرسه‌ی خاص (چه صریحا ثبت شده باشن چه نه)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$schoolId = $_GET['school_id'] ?? null;
if (!$schoolId) Response::json(['success' => false, 'message' => 'school_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT feature_key, enabled FROM school_features WHERE school_id = ?");
$stmt->execute([$schoolId]);
$rows = $stmt->fetchAll();
$overrides = [];
foreach ($rows as $r) $overrides[$r['feature_key']] = (bool) $r['enabled'];

$allKeys = ['exams', 'late_penalty', 'resubmit', 'points', 'online_class'];
$result = [];
foreach ($allKeys as $key) {
    $result[] = ['feature_key' => $key, 'enabled' => $overrides[$key] ?? true];
}

Response::json(['success' => true, 'data' => $result]);
