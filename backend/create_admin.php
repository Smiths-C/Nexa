<?php
/**
 * این فایل رو فقط یک‌بار، بعد از بالا آوردن دیتابیس، اجرا کنید تا اولین ادمین کل ساخته بشه.
 * بعد از استفاده، حتما این فایل رو از روی سرور پاک کنید (یا با رمز محافظتش کنید).
 *
 * روش اجرا:
 *  yourdomain.com/create_admin.php?username=admin&password=YourStrongPass&name=مدیر کل
 */
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';

$username = $_GET['username'] ?? 'admin';
$password = $_GET['password'] ?? 'Admin@123';
$name     = $_GET['name']     ?? 'مدیر کل سیستم';

$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
if ($stmt->fetch()) {
    die("یک ادمین از قبل وجود داره. برای امنیت، این فایل رو پاک کنید.");
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare("INSERT INTO users (role, full_name, username, password_hash) VALUES ('admin', ?, ?, ?)");
$stmt->execute([$name, $username, $hash]);

echo "ادمین کل با موفقیت ساخته شد.\n";
echo "نام کاربری: $username\n";
echo "رمز عبور: $password\n";
echo "\n⚠️  حالا همین الان این فایل (create_admin.php) رو از روی سرور پاک کنید.\n";
