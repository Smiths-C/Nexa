<?php
// ادمین کل برای هر مدرسه یک تم اختصاصی (رنگ اصلی/ثانویه + لوگو) تعیین می‌کند
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';

Auth::requireRole(['admin']);

// multipart/form-data چون ممکنه لوگو هم ارسال بشه
$schoolId  = $_POST['school_id']       ?? null;
$primary   = $_POST['primary_color']   ?? null; // مثلا #1B3358
$secondary = $_POST['secondary_color'] ?? null; // مثلا #FF6B35

if (!$schoolId) Response::json(['success' => false, 'message' => 'school_id الزامی است'], 422);

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);
if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'مدرسه یافت نشد'], 404);

foreach (['primary_color' => $primary, 'secondary_color' => $secondary] as $label => $c) {
    if ($c !== null && !preg_match('/^#[0-9A-Fa-f]{6}$/', $c)) {
        Response::json(['success' => false, 'message' => "کد رنگ $label باید مثل #1B3358 باشد"], 422);
    }
}

$logoPath = null;
if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $dir = __DIR__ . '/../../uploads/school_logos/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $fileName = 'school_' . $schoolId . '_logo_' . time() . ($ext ? ".$ext" : '');
    move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $fileName);
    $logoPath = "uploads/school_logos/$fileName";
}

$fields = [];
$params = [];
if ($primary)   { $fields[] = 'theme_primary_color = ?';   $params[] = $primary; }
if ($secondary) { $fields[] = 'theme_secondary_color = ?'; $params[] = $secondary; }
if ($logoPath)  { $fields[] = 'logo_path = ?';              $params[] = $logoPath; }

if (empty($fields)) Response::json(['success' => false, 'message' => 'هیچ مقداری برای تغییر ارسال نشده'], 422);

$params[] = $schoolId;
$stmt = $db->prepare('UPDATE schools SET ' . implode(', ', $fields) . ' WHERE id = ?');
$stmt->execute($params);

Response::json(['success' => true, 'message' => 'تم مدرسه به‌روزرسانی شد']);
