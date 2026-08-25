<?php
/**
 * همه‌ی فایل‌های آپلودی (تکلیف، پیام صوتی/عکس و ...) با این کلاس ذخیره می‌شن.
 * ساختار پوشه‌ها: uploads/<type>/school_<id>/class_<id>/
 * نام فایل: <فرستنده>_to_<گیرنده>_<تاریخ‌ساعت>_<رندوم>.<پسوند>
 * این طوری هم فایل‌های هر مدرسه/کلاس از هم جدا می‌مونن، هم از روی اسم فایل
 * مشخصه چه کسی برای چه کسی فرستاده.
 */
class FileHelper
{
    /** حذف کاراکترهای غیرمجاز در نام فایل و تبدیل فاصله به آندرلاین (فارسی رو دست‌نخورده نگه می‌داره) */
    public static function slug(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/[\/\\\\:\*\?"<>\|]/u', '', $text);
        $text = preg_replace('/\s+/u', '-', $text);
        return $text !== '' ? $text : 'user';
    }

    /**
     * مسیر پوشه‌ی مخصوص یک مدرسه/کلاس رو می‌سازه (اگه نبود می‌سازتش) و برمی‌گردونه.
     * $type یکی از: assignments | submissions | messages
     */
    public static function dirFor(string $type, int $schoolId, int $classId): string
    {
        $dir = __DIR__ . "/../uploads/{$type}/school_{$schoolId}/class_{$classId}/";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * فایل آپلودی رو ذخیره می‌کنه و مسیر نسبی (برای ذخیره در DB و ساخت لینک) رو برمی‌گردونه.
     */
    public static function save(array $uploadedFile, string $type, int $schoolId, int $classId, string $senderName, string $receiverName): string
    {
        $dir = self::dirFor($type, $schoolId, $classId);
        $ext = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $ext = $ext ? '.' . strtolower($ext) : '';

        $fileName = self::slug($senderName) . '_to_' . self::slug($receiverName)
            . '_' . date('Ymd_His') . '_' . substr(uniqid(), -5) . $ext;

        move_uploaded_file($uploadedFile['tmp_name'], $dir . $fileName);

        // مسیر نسبی از ریشه‌ی بک‌اند، برای ذخیره در دیتابیس
        return "uploads/{$type}/school_{$schoolId}/class_{$classId}/{$fileName}";
    }
}
