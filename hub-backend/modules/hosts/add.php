<?php
// افزودن یک هاست جدید به لیست (که مدرسه‌ها بعدا می‌تونن روش ساخته بشن)
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireAdmin();
$input = json_decode(file_get_contents('php://input'), true) ?? [];

foreach (['name', 'api_base_url', 'shared_secret'] as $f) {
    if (empty($input[$f])) Response::json(['success' => false, 'message' => "فیلد $f الزامی است"], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("INSERT INTO hosts (name, api_base_url, shared_secret) VALUES (?, ?, ?)");
$stmt->execute([$input['name'], rtrim($input['api_base_url'], '/'), $input['shared_secret']]);

Response::json(['success' => true, 'host_id' => (int) $db->lastInsertId()]);
