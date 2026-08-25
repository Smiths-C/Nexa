<?php
// خارج کردن مدرسه از حالت تعلیق
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$schoolId = $input['school_id'] ?? null;
if (!$schoolId) Response::json(['success' => false, 'message' => 'school_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

$stmt = $db->prepare("UPDATE schools SET status = 'approved' WHERE id = ?");
$stmt->execute([$schoolId]);

Response::json(['success' => true, 'message' => 'مدرسه از حالت تعلیق خارج شد']);
