-- =========================================================
--  اسکیمای دیتابیس Nexa
--  Charset: utf8mb4 (پشتیبانی کامل فارسی و ایموجی)
--  توجه: اگه از قبل نصب دارید و می‌خواید فقط آپدیت کنید، از
--  database/migrations/002_features.sql استفاده کنید، نه این فایل.
-- =========================================================

CREATE TABLE schools (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    name                   VARCHAR(255) NOT NULL,
    national_code          VARCHAR(50)  NOT NULL UNIQUE,
    city                   VARCHAR(100) NOT NULL,
    status                 ENUM('pending','approved','rejected','suspended') DEFAULT 'approved',
    theme_primary_color    VARCHAR(7)  DEFAULT '#2F5FFF',
    theme_secondary_color  VARCHAR(7)  DEFAULT '#FFB020',
    logo_path              VARCHAR(500) NULL,
    free_message_limit     INT NOT NULL DEFAULT 1,
    created_at             DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- قابلیت‌هایی که ادمین می‌تونه جدا جدا و به‌تفکیک هر مدرسه روشن/خاموش کنه.
-- کلیدهای رایج: exams | late_penalty | resubmit | points
-- اگه ردیفی برای یه (school_id, feature_key) وجود نداشته باشه، یعنی روشنه (پیش‌فرض).
CREATE TABLE school_features (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    school_id   INT NOT NULL,
    feature_key VARCHAR(50) NOT NULL,
    enabled     TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_school_feature (school_id, feature_key),
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    role          ENUM('admin','manager','teacher','student') NOT NULL,
    school_id     INT NULL,
    class_id      INT NULL,
    full_name     VARCHAR(255) NOT NULL,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    phone         VARCHAR(20)  NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status        ENUM('active','blocked') DEFAULT 'active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE classes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    school_id   INT NOT NULL,
    name        VARCHAR(100) NOT NULL,
    grade_level INT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users
    ADD CONSTRAINT fk_users_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL;

CREATE TABLE class_teacher (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    class_id   INT NOT NULL,
    teacher_id INT NOT NULL,
    UNIQUE KEY uniq_class_teacher (class_id, teacher_id),
    FOREIGN KEY (class_id)   REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE announcements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    school_id  INT NOT NULL,
    manager_id INT NOT NULL,
    class_id   INT NULL,
    title      VARCHAR(255) NOT NULL,
    content    TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)  REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE assignments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    school_id    INT NOT NULL,
    class_id     INT NOT NULL,
    teacher_id   INT NOT NULL,
    title        VARCHAR(255) NOT NULL,
    description  TEXT NULL,
    type         ENUM('text','video','file') DEFAULT 'text',
    file_path    VARCHAR(500) NULL,
    due_date     DATETIME NULL,
    max_score    DECIMAL(5,2) NULL,
    late_penalty DECIMAL(5,2) NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)  REFERENCES schools(id)  ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id)  ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE assignment_submissions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id    INT NOT NULL,
    content_type  ENUM('text','image','voice') NOT NULL,
    content       VARCHAR(500) NOT NULL,
    is_late       TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('pending','resubmit_requested') DEFAULT 'pending',
    score         DECIMAL(5,2) NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)    REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_points (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    school_id  INT NOT NULL,
    class_id   INT NOT NULL,
    student_id INT NOT NULL,
    given_by   INT NOT NULL,
    delta      INT NOT NULL,
    reason     VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)  REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (given_by)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE message_threads (
    id                        INT AUTO_INCREMENT PRIMARY KEY,
    school_id                 INT NOT NULL,
    class_id                  INT NOT NULL,
    student_id                INT NOT NULL,
    teacher_id                INT NOT NULL,
    assignment_submission_id  INT NOT NULL,
    status                    ENUM('awaiting_teacher','allowed','closed') DEFAULT 'awaiting_teacher',
    created_at                DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)   REFERENCES schools(id)               ON DELETE CASCADE,
    FOREIGN KEY (class_id)    REFERENCES classes(id)                ON DELETE CASCADE,
    FOREIGN KEY (student_id)  REFERENCES users(id)                  ON DELETE CASCADE,
    FOREIGN KEY (teacher_id)  REFERENCES users(id)                  ON DELETE CASCADE,
    FOREIGN KEY (assignment_submission_id) REFERENCES assignment_submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    thread_id    INT NOT NULL,
    sender_id    INT NOT NULL,
    sender_role  ENUM('teacher','student') NOT NULL,
    content_type ENUM('text','image','voice') NOT NULL,
    content      VARCHAR(500) NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
--  امتحانات (نیازمند قابلیت exams)
-- =========================================================
CREATE TABLE exams (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    school_id   INT NOT NULL,
    class_id    INT NOT NULL,
    teacher_id  INT NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)  REFERENCES schools(id)  ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id)  ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_questions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    exam_id       INT NOT NULL,
    question_text TEXT NOT NULL,
    type          ENUM('multiple_choice','essay') NOT NULL,
    order_index   INT NOT NULL DEFAULT 0,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_options (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct  TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_submissions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    exam_id      INT NOT NULL,
    student_id   INT NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_exam_student (exam_id, student_id),
    FOREIGN KEY (exam_id)    REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_answers (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    exam_submission_id  INT NOT NULL,
    question_id         INT NOT NULL,
    selected_option_id  INT NULL,
    essay_answer        TEXT NULL,
    score               DECIMAL(5,2) NULL,
    FOREIGN KEY (exam_submission_id) REFERENCES exam_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id)        REFERENCES exam_questions(id)   ON DELETE CASCADE,
    FOREIGN KEY (selected_option_id) REFERENCES exam_options(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
--  کلاس آنلاین (نیازمند قابلیت online_class)
--  خود ویدیو/صدا/تخته روی سرور Node.js/VPS ردوبدل می‌شه؛ این جدول فقط
--  "این جلسه برای کدوم کلاسه، معلم اصلیش کیه، الان زنده‌ست یا نه" رو نگه می‌داره.
-- =========================================================
CREATE TABLE online_classes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    school_id       INT NOT NULL,
    class_id        INT NOT NULL,
    main_teacher_id INT NOT NULL,             -- معلم اصلی؛ موقع ساخت توسط مدیر/ادمین تعیین می‌شه
    created_by       INT NOT NULL,             -- کاربری که جلسه رو ساخته (مدیر یا ادمین)
    title           VARCHAR(255) NULL,
    status          ENUM('scheduled','live','ended') DEFAULT 'scheduled',
    started_at      DATETIME NULL,
    ended_at        DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id)       REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)        REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (main_teacher_id) REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (created_by)      REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- توجه: ادمین کل پیش‌فرض اینجا ساخته نمی‌شه چون هش رمز عبور باید واقعا توسط PHP
-- (تابع password_hash) تولید بشه، نه یک مقدار ثابت. بعد از اجرای این اسکیمای دیتابیس،
-- فایل backend/create_admin.php رو یک‌بار از مسیر مرورگر یا CLI اجرا کنید تا اولین ادمین ساخته بشه.
