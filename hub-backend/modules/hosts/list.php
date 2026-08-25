<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireAdmin();
$db = Database::getConnection();
$stmt = $db->query("SELECT id, name, api_base_url, status, created_at FROM hosts ORDER BY created_at DESC");
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
