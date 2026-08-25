<?php
/**
 * ورودی اصلی API. همه‌ی درخواست‌ها از اینجا رد می‌شن (به کمک .htaccess).
 * اگه بک‌اند رو توی ساب‌فولدر (مثلا yourdomain.com/api) آپلود کردید،
 * مقدار $basePath پایین رو مطابق مسیر واقعی تنظیم کنید.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/core/Response.php';

$basePath = ''; // مثلا: 'api' اگه آدرس شما yourdomain.com/api/... است

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($basePath !== '') {
    $uri = preg_replace('#^' . preg_quote($basePath, '#') . '/?#', '', $uri);
}

$routes = [
    // --- احراز هویت ---
    'auth/register-manager'  => 'modules/auth/register_manager.php',
    'auth/login'              => 'modules/auth/login.php',
    'auth/change-password'    => 'modules/auth/change_password.php',

    // --- ادمین کل ---
    'admin/add-school'           => 'modules/admin/add_school.php',
    'admin/list-schools'         => 'modules/admin/list_schools.php',
    'admin/set-school-theme'     => 'modules/admin/set_school_theme.php',
    'admin/set-school-settings'  => 'modules/admin/set_school_settings.php',
    'admin/suspend-school'       => 'modules/admin/suspend_school.php',
    'admin/unsuspend-school'     => 'modules/admin/unsuspend_school.php',
    'admin/delete-school'        => 'modules/admin/delete_school.php',
    'admin/set-school-feature'   => 'modules/admin/set_school_feature.php',
    'admin/list-school-features' => 'modules/admin/list_school_features.php',
    'admin/add-student'          => 'modules/admin/add_student.php',
    'admin/add-teacher'          => 'modules/admin/add_teacher.php',
    'admin/reset-user-password'  => 'modules/admin/reset_user_password.php',

    // --- تم مدرسه (برای همه‌ی نقش‌ها) ---
    'school/get-theme'       => 'modules/school/get_theme.php',

    // --- مدیر مدرسه ---
    'manager/add-class'          => 'modules/manager/add_class.php',
    'manager/list-classes'       => 'modules/manager/list_classes.php',
    'manager/add-student'        => 'modules/manager/add_student.php',
    'manager/list-students'      => 'modules/manager/list_students.php',
    'manager/add-teacher'        => 'modules/manager/add_teacher.php',
    'manager/list-teachers'      => 'modules/manager/list_teachers.php',
    'manager/assign-teacher'     => 'modules/manager/assign_teacher_to_class.php',
    'manager/add-announcement'   => 'modules/manager/add_announcement.php',
    'manager/reset-user-password' => 'modules/manager/reset_user_password.php',

    // --- معلم ---
    'teacher/list-classes'     => 'modules/teacher/list_classes.php',
    'teacher/list-students'    => 'modules/teacher/list_students.php',
    'teacher/add-assignment'   => 'modules/teacher/add_assignment.php',
    'teacher/list-assignments' => 'modules/teacher/list_assignments.php',
    'teacher/grade-submission' => 'modules/teacher/grade_submission.php',

    // --- دانش‌آموز ---
    'student/list-teachers'     => 'modules/student/list_teachers.php',
    'student/list-assignments'  => 'modules/student/list_assignments.php',
    'student/submit-assignment' => 'modules/student/submit_assignment.php',

    // --- کلاس (لیست دانش‌آموزان با چراغ وضعیت - معلم و مدیر) ---
    'class/roster' => 'modules/class/roster.php',

    // --- امتیاز مثبت/منفی (معلم و مدیر) ---
    'points/add' => 'modules/points/add.php',

    // --- امتحان ---
    'exam/create'       => 'modules/exam/create.php',
    'exam/add-question' => 'modules/exam/add_question.php',
    'exam/list'         => 'modules/exam/list.php',
    'exam/get'          => 'modules/exam/get.php',
    'exam/submit'       => 'modules/exam/submit.php',
    'exam/submissions'  => 'modules/exam/submissions.php',
    'exam/grade-essay'  => 'modules/exam/grade_essay.php',

    // --- کلاس آنلاین (ویدیو/صدا/تخته زنده روی سرور VPS) ---
    'onlineclass/create'        => 'modules/onlineclass/create.php',
    'onlineclass/list'          => 'modules/onlineclass/list.php',
    'onlineclass/verify-access' => 'modules/onlineclass/verify_access.php',
    'onlineclass/end'           => 'modules/onlineclass/end.php',

    // --- درگاه هاب مرکزی (فقط با رمز مشترک X-Hub-Secret) ---
    'hub/provision-school' => 'modules/hub/provision_school.php',
    'hub/school-action'    => 'modules/hub/school_action.php',

    // --- پیام‌ها (معلم/دانش‌آموز + نظارت مدیر) ---
    'messages/send'          => 'modules/messages/send.php',
    'messages/list'          => 'modules/messages/list.php',
    'messages/list-threads'  => 'modules/messages/list_threads.php',
    'messages/allow'         => 'modules/messages/allow_continue.php',
];

if (isset($routes[$uri])) {
    require __DIR__ . '/' . $routes[$uri];
} else {
    Response::json(['success' => false, 'message' => 'مسیر یافت نشد: ' . $uri], 404);
}
