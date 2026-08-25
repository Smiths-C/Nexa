<?php
/**
 * اولین درخواستی که اپ می‌فرسته (قبل از لاگین): «این کد مدرسه مال کدوم هاسته؟»
 * این endpoint عمومیه (نیاز به توکن نداره) چون هنوز کسی لاگین نکرده.
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';

$schoolCode = $_GET['school_code'] ?? null;
if (!$schoolCode) Response::json(['success' => false, 'message' => 'school_code الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare(
    "SELECT sr.status, sr.name, h.api_base_url FROM schools_registry sr
     JOIN hosts h ON h.id = sr.host_id
     WHERE sr.school_code = ?"
);
$stmt->execute([$schoolCode]);
$row = $stmt->fetch();

if (!$row) Response::json(['success' => false, 'message' => 'کد مدرسه معتبر نیست'], 404);
if ($row['status'] === 'deleted') Response::json(['success' => false, 'message' => 'این مدرسه دیگر وجود ندارد'], 410);
if ($row['status'] === 'suspended') Response::json(['success' => false, 'message' => 'این مدرسه موقتا تعلیق شده است'], 403);

Response::json(['success' => true, 'data' => ['host_url' => $row['api_base_url'], 'school_name' => $row['name']]]);
