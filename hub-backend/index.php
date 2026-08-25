<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/core/Response.php';

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

$routes = [
    'auth/login'      => 'modules/auth/login.php',

    'hosts/add'       => 'modules/hosts/add.php',
    'hosts/list'      => 'modules/hosts/list.php',

    'schools/create'  => 'modules/schools/create.php',
    'schools/list'    => 'modules/schools/list.php',
    'schools/action'  => 'modules/schools/action.php',
    'schools/resolve' => 'modules/schools/resolve.php', // عمومی - بدون نیاز به توکن
];

if (isset($routes[$uri])) {
    require __DIR__ . '/' . $routes[$uri];
} else {
    Response::json(['success' => false, 'message' => 'مسیر یافت نشد: ' . $uri], 404);
}
