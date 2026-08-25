-- =========================================================
--  مایگریشن ۲: امتحان، امتیاز مثبت/منفی، نمره تاخیر/ارسال مجدد،
--  تعلیق مدرسه، قابلیت‌های روشن/خاموش‌شدنی، username
--  فقط روی نصب‌های قبلی اجرا بشه (نه رو نصب تازه‌ای که schema.sql کامل رو اجرا کرده)
-- =========================================================

-- ۱) وضعیت تعلیق برای مدرسه‌ها
ALTER TABLE schools
    MODIFY status ENUM('pending','approved','rejected','suspended') DEFAULT 'approved';

-- ۲) جدول قابلیت‌های روشن/خاموش‌شدنی به تفکیک مدرسه
CREATE TABLE IF NOT EXISTS school_features (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    school_id   INT NOT NULL,
    feature_key VARCHAR(50) NOT NULL,
    enabled     TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_school_feature (school_id, feature_key),
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ۳) username برای کاربران (جایگزین phone به‌عنوان شناسه‌ی ورود)
-- ابتدا یه مقدار موقت یکتا از phone می‌سازیم تا NOT NULL/UNIQUE خطا نده،
-- بعدش شما (یا ادمین/مدیر از پنل) می‌تونید یوزرنیم واقعی رو عوض کنید.
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL;
UPDATE users SET username = CONCAT('user', id) WHERE username IS NULL;
ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL;
ALTER TABLE users ADD UNIQUE KEY uniq_username (username);
ALTER TABLE users MODIFY phone VARCHAR(20) NULL;

-- ۴) تکلیف: نمره کامل و کسر نمره‌ی تاخیر (به تشخیص معلم برای هر تکلیف)
ALTER TABLE assignments
    ADD COLUMN max_score DECIMAL(5,2) NULL AFTER due_date,
    ADD COLUMN late_penalty DECIMAL(5,2) NULL AFTER max_score;

-- ۵) تحویل تکلیف: دیرکرد، وضعیت (نیاز به ارسال مجدد)، نمره
ALTER TABLE assignment_submissions
    ADD COLUMN is_late TINYINT(1) NOT NULL DEFAULT 0 AFTER content,
    ADD COLUMN status ENUM('pending','resubmit_requested') DEFAULT 'pending' AFTER is_late,
    ADD COLUMN score DECIMAL(5,2) NULL AFTER status;

-- ۶) امتیاز مثبت/منفی دانش‌آموز
CREATE TABLE IF NOT EXISTS student_points (
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

-- ۷) امتحانات
CREATE TABLE IF NOT EXISTS exams (
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

CREATE TABLE IF NOT EXISTS exam_questions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    exam_id       INT NOT NULL,
    question_text TEXT NOT NULL,
    type          ENUM('multiple_choice','essay') NOT NULL,
    order_index   INT NOT NULL DEFAULT 0,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exam_options (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct  TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exam_submissions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    exam_id      INT NOT NULL,
    student_id   INT NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_exam_student (exam_id, student_id),
    FOREIGN KEY (exam_id)    REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exam_answers (
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
