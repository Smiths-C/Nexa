<?php
function env(string $key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

return [
    'db_host' => env('DB_HOST', 'localhost'),
    'db_name' => env('DB_NAME', 'nexa_hub'),
    'db_user' => env('DB_USER', 'root'),
    'db_pass' => env('DB_PASS', ''),
    'jwt_secret' => env('JWT_SECRET', 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET'),
    'jwt_expire_seconds' => 60 * 60 * 24 * 7,
];
