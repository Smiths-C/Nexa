<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

$user = Auth::requireRole(['manager']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['name']) || !isset($input['grade_level'])) {
    Response::json(['success' => false, 'message' => 'نام کلاس و مقطع (grade_level) الزامی است'], 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("INSERT INTO classes (school_id, name, grade_level) VALUES (?, ?, ?)");
$stmt->execute([$user['school_id'], $input['name'], (int) $input['grade_level']]);

Response::json(['success' => true, 'class_id' => (int) $db->lastInsertId()]);
