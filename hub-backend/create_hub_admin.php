<?php
// فقط یک‌بار اجرا کنید تا اولین ادمین هاب ساخته بشه؛ بعدش پاکش کنید.
// yourdomain.com/create_hub_admin.php?username=admin&password=YourStrongPass&name=ادمین هاب
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';

$username = $_GET['username'] ?? 'admin';
$password = $_GET['password'] ?? 'Admin@123';
$name     = $_GET['name']     ?? 'ادمین هاب';

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM hub_admins LIMIT 1");
$stmt->execute();
if ($stmt->fetch()) die("یک ادمین هاب از قبل وجود داره. برای امنیت این فایل رو پاک کنید.");

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare("INSERT INTO hub_admins (full_name, username, password_hash) VALUES (?, ?, ?)");
$stmt->execute([$name, $username, $hash]);

echo "ادمین هاب ساخته شد. username: $username | password: $password\n";
echo "⚠️ همین الان این فایل رو پاک کنید.\n";
