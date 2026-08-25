<?php
/**
 * بررسی می‌کنه یه قابلیت (مثلا exams، late_penalty، resubmit، points) برای
 * یه مدرسه‌ی خاص روشنه یا نه. اگه هیچ ردیفی برای اون (school_id, feature_key)
 * ثبت نشده باشه، یعنی روشنه (پیش‌فرض). ادمین از پنل خودش می‌تونه خاموشش کنه.
 */
class FeatureGate
{
    public const EXAMS = 'exams';
    public const LATE_PENALTY = 'late_penalty';
    public const RESUBMIT = 'resubmit';
    public const POINTS = 'points';
    public const ONLINE_CLASS = 'online_class';

    public static function isEnabled(PDO $db, int $schoolId, string $featureKey): bool
    {
        $stmt = $db->prepare("SELECT enabled FROM school_features WHERE school_id = ? AND feature_key = ?");
        $stmt->execute([$schoolId, $featureKey]);
        $row = $stmt->fetch();
        return $row === false ? true : (bool) $row['enabled'];
    }

    /** اگه قابلیت خاموش بود، پاسخ 403 می‌ده و اجرا رو متوقف می‌کنه */
    public static function require(PDO $db, int $schoolId, string $featureKey): void
    {
        if (!self::isEnabled($db, $schoolId, $featureKey)) {
            Response::json(['success' => false, 'message' => 'این قابلیت برای مدرسه‌ی شما فعال نیست'], 403);
        }
    }
}
