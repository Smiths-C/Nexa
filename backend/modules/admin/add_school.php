<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['name', 'national_code', 'city'] as $f) {
    if (empty($input[$f])) Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE national_code = ?");
$stmt->execute([$input['national_code']]);
if ($stmt->fetch()) {
    Response::json(['success' => false, 'message' => 'این مدرسه قبلا ثبت شده'], 409);
}

$stmt = $db->prepare("INSERT INTO schools (name, national_code, city, status) VALUES (?, ?, ?, 'approved')");
$stmt->execute([$input['name'], $input['national_code'], $input['city']]);

Response::json(['success' => true, 'school_id' => (int) $db->lastInsertId()]);
