<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);
$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM schools ORDER BY created_at DESC");
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
