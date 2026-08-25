<?php
/**
 * هاب مرکزی این endpoint رو صدا می‌زنه تا رو همین هاست یه مدرسه‌ی جدید +
 * حساب مدیرش ساخته بشه. فقط با رمز مشترک هاب قابل دسترسیه (نه یه ادمین لاگین‌کرده).
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/HubAuth.php';
require_once __DIR__ . '/../../modules/ai/SchoolVerifier.php';

HubAuth::require();
$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['name', 'national_code', 'city', 'manager_full_name', 'manager_username', 'manager_password'] as $f) {
    if (empty($input[$f])) Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$input['manager_username']]);
if ($stmt->fetch()) Response::json(['success' => false, 'message' => 'این نام کاربری قبلا استفاده شده'], 409);

$verifier = new SchoolVerifier($db);
$check = $verifier->verify($input['name'], $input['national_code'], $input['city']);
if (!$check['valid']) Response::json(['success' => false, 'message' => $check['message']], 422);

try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO schools (name, national_code, city, status) VALUES (?, ?, ?, 'approved')");
    $stmt->execute([$input['name'], $input['national_code'], $input['city']]);
    $schoolId = (int) $db->lastInsertId();

    $hash = password_hash($input['manager_password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        "INSERT INTO users (role, school_id, full_name, username, password_hash) VALUES ('manager', ?, ?, ?, ?)"
    );
    $stmt->execute([$schoolId, $input['manager_full_name'], $input['manager_username'], $hash]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    Response::json(['success' => false, 'message' => 'خطا در ساخت مدرسه روی این هاست'], 500);
}

Response::json(['success' => true, 'school_id' => $schoolId]);
