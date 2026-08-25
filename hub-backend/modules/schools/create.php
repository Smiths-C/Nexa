<?php
/**
 * ادمین هاب یه مدرسه‌ی جدید می‌سازه: مشخص می‌کنه رو کدوم هاست بره،
 * هاب یه school_code یکتا می‌سازه، بعد از طریق HostClient به همون هاست
 * می‌گه «این مدرسه + این مدیر رو بساز»، و در نهایت تو schools_registry ثبتش می‌کنه.
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/HostClient.php';

Auth::requireAdmin();
$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['host_id', 'name', 'national_code', 'city', 'manager_full_name', 'manager_username', 'manager_password'] as $f) {
    if (empty($input[$f])) Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM hosts WHERE id = ? AND status = 'active'");
$stmt->execute([$input['host_id']]);
$host = $stmt->fetch();
if (!$host) Response::json(['success' => false, 'message' => 'هاست یافت نشد یا غیرفعال است'], 404);

// ساخت یه کد یکتا برای این مدرسه (چیزی که کاربرهای مدرسه موقع ورود واردش می‌کنن)
do {
    $schoolCode = 'NEXA-' . random_int(1000, 9999);
    $stmt = $db->prepare("SELECT id FROM schools_registry WHERE school_code = ?");
    $stmt->execute([$schoolCode]);
} while ($stmt->fetch());

$result = HostClient::call($host['api_base_url'], 'hub/provision-school', [
    'name' => $input['name'],
    'national_code' => $input['national_code'],
    'city' => $input['city'],
    'manager_full_name' => $input['manager_full_name'],
    'manager_username' => $input['manager_username'],
    'manager_password' => $input['manager_password'],
], $host['shared_secret']);

if (!($result['success'] ?? false)) {
    Response::json(['success' => false, 'message' => $result['message'] ?? 'خطا در ساخت مدرسه روی هاست مقصد'], 502);
}

$remoteSchoolId = $result['school_id'];

$stmt = $db->prepare(
    "INSERT INTO schools_registry (school_code, name, national_code, city, host_id, remote_school_id, status)
     VALUES (?, ?, ?, ?, ?, ?, 'active')"
);
$stmt->execute([$schoolCode, $input['name'], $input['national_code'], $input['city'], $host['id'], $remoteSchoolId]);

Response::json([
    'success' => true,
    'school_code' => $schoolCode,
    'registry_id' => (int) $db->lastInsertId(),
    'message' => "مدرسه ساخته شد. کد مدرسه برای ورود کاربرها: $schoolCode",
]);
