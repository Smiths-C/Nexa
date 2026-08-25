<?php
/**
 * فقط درخواست‌هایی که هدر X-Hub-Secret درستی دارن اجازه‌ی صدا زدن endpoint های
 * hub/* رو دارن. این جایگزین Auth::requireRole معمولیه، چون این درخواست‌ها از
 * طرف هاب مرکزی میان، نه یه کاربر لاگین‌کرده‌ی معمولی.
 */
class HubAuth
{
    public static function require(): void
    {
        $config = require __DIR__ . '/../config/config.php';
        $expected = $config['hub_shared_secret'] ?? '';

        if (empty($expected)) {
            Response::json(['success' => false, 'message' => 'این هاست به هاب مرکزی متصل نیست'], 403);
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $provided = $headers['X-Hub-Secret'] ?? $headers['x-hub-secret'] ?? ($_SERVER['HTTP_X_HUB_SECRET'] ?? '');

        if (!hash_equals($expected, $provided)) {
            Response::json(['success' => false, 'message' => 'رمز هاب نامعتبر است'], 403);
        }
    }
}
