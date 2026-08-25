<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireAdmin();
$db = Database::getConnection();
$stmt = $db->query(
    "SELECT sr.*, h.name AS host_name FROM schools_registry sr
     JOIN hosts h ON h.id = sr.host_id ORDER BY sr.created_at DESC"
);
Response::json(['success' => true, 'data' => $stmt->fetchAll()]);
