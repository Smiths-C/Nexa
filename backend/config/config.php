<?php
/**
 * تنظیمات پروژه
 * روی هاست اشتراکی: مقادیر رو مستقیم اینجا پر کنید.
 * روی VPS: می‌تونید همین مقادیر رو از Environment Variable بخونید (getenv) —
 * از قبل پشتیبانی شده تا وقتی بخشی از پروژه رفت روی VPS نیازی به تغییر کد نباشه.
 */
function env(string $key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

return [
    'db_host' => env('DB_HOST', 'localhost'),
    'db_name' => env('DB_NAME', 'school_app'),
    'db_user' => env('DB_USER', 'root'),
    'db_pass' => env('DB_PASS', ''),

    'jwt_secret' => env('JWT_SECRET', 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_BEFORE_GOING_LIVE'),
    'jwt_expire_seconds' => 60 * 60 * 24 * 30, // ۳۰ روز

    // اگر خواستید ثبت‌نام مدیر رو به یک AI واقعی (مثل Claude) وصل کنید، کلید رو اینجا بذارید
    'ai_api_key' => env('AI_API_KEY', ''),

    // رمز مشترک بین این هاست و هاب مرکزی (اگه این هاست بخشی از یه سیستم چندهاستیه).
    // خالی بذارید یعنی این هاست مستقل کار می‌کنه و به هاب وصل نیست.
    'hub_shared_secret' => env('HUB_SHARED_SECRET', ''),

    // آدرس پایه‌ی uploads برای ساخت لینک کامل فایل‌ها در خروجی API
    'app_base_url' => env('APP_BASE_URL', 'https://yourdomain.com'),
];
