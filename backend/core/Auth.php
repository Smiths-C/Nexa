<?php
require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/Database.php';

class Auth
{
    public static function user(): ?array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
            return null;
        }

        $config = require __DIR__ . '/../config/config.php';
        return JWT::decode($m[1], $config['jwt_secret']);
    }

    /** فقط اجازه میده اگه نقش کاربر توی لیست مجاز باشه، وگرنه 401/403 برمیگردونه و متوقف میشه */
    public static function requireRole(array $roles): array
    {
        $user = self::user();
        if (!$user) {
            Response::json(['success' => false, 'message' => 'ابتدا وارد شوید'], 401);
        }
        if (!in_array($user['role'], $roles, true)) {
            Response::json(['success' => false, 'message' => 'دسترسی غیرمجاز'], 403);
        }
        self::blockIfSchoolSuspended($user);
        return $user;
    }

    /**
     * اگه مدرسه‌ی این کاربر تعلیق شده باشه، هیچ کاری (نه فقط مدیر، نه معلم، نه دانش‌آموز)
     * قابل انجام نیست تا وقتی ادمین دوباره فعالش کنه. اطلاعات دست‌نخورده می‌مونه.
     */
    private static function blockIfSchoolSuspended(array $user): void
    {
        if (empty($user['school_id'])) return; // ادمین کل مدرسه نداره

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT status FROM schools WHERE id = ?");
        $stmt->execute([$user['school_id']]);
        $school = $stmt->fetch();

        if ($school && $school['status'] === 'suspended') {
            Response::json(['success' => false, 'message' => 'مدرسه‌ی شما موقتا توسط ادمین سیستم تعلیق شده است'], 403);
        }
    }
}
