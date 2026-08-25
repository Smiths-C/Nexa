<?php
/**
 * لیست دانش‌آموزان یک کلاس با وضعیت "چراغ" بر اساس آخرین تکلیفی که معلم داده:
 *   - red    : هنوز اون تکلیف رو ارسال نکرده
 *   - yellow : ارسال کرده (چه در انتظار بررسی، چه معلم گفته دوباره بفرسته)
 *   - null   : هنوز هیچ تکلیفی برای این کلاس ثبت نشده
 * هم معلمِ خودِ همون کلاس می‌تونه ببینه، هم مدیرِ همون مدرسه.
 * امتیاز مثبت/منفی هر دانش‌آموز هم (در صورت روشن بودن قابلیت points) برگردونده می‌شه.
 */
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/FeatureGate.php';

$user = Auth::requireRole(['teacher', 'manager']);
$classId = $_GET['class_id'] ?? null;
if (!$classId) Response::json(['success' => false, 'message' => 'class_id الزامی است'], 422);

$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch();
if (!$class) Response::json(['success' => false, 'message' => 'کلاس یافت نشد'], 404);

if ($user['role'] === 'teacher') {
    $stmt = $db->prepare("SELECT id FROM class_teacher WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$classId, $user['user_id']]);
    if (!$stmt->fetch()) Response::json(['success' => false, 'message' => 'شما به این کلاس دسترسی ندارید'], 403);
} else {
    if ((int) $class['school_id'] !== (int) $user['school_id']) {
        Response::json(['success' => false, 'message' => 'این کلاس متعلق به مدرسه‌ی شما نیست'], 403);
    }
}

// آخرین تکلیفی که برای این کلاس ثبت شده
$stmt = $db->prepare("SELECT id, title FROM assignments WHERE class_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$classId]);
$lastAssignment = $stmt->fetch();

$stmt = $db->prepare("SELECT id, full_name, username FROM users WHERE role = 'student' AND class_id = ? ORDER BY full_name ASC");
$stmt->execute([$classId]);
$students = $stmt->fetchAll();

$pointsEnabled = FeatureGate::isEnabled($db, (int) $class['school_id'], FeatureGate::POINTS);

foreach ($students as &$s) {
    if ($lastAssignment) {
        $stmt = $db->prepare(
            "SELECT status FROM assignment_submissions WHERE assignment_id = ? AND student_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$lastAssignment['id'], $s['id']]);
        $submission = $stmt->fetch();
        $s['light'] = $submission ? 'yellow' : 'red';
        $s['resubmit_requested'] = $submission && $submission['status'] === 'resubmit_requested';
    } else {
        $s['light'] = null;
        $s['resubmit_requested'] = false;
    }

    if ($pointsEnabled) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(delta), 0) AS total FROM student_points WHERE student_id = ?");
        $stmt->execute([$s['id']]);
        $s['points'] = (int) $stmt->fetch()['total'];
    } else {
        $s['points'] = null;
    }
}

Response::json([
    'success' => true,
    'last_assignment' => $lastAssignment ?: null,
    'data' => $students,
]);
