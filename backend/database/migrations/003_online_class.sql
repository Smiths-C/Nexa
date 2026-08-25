-- =========================================================
--  مایگریشن ۳: کلاس آنلاین (ویدیو/صدا/تخته زنده روی VPS)
-- =========================================================

CREATE TABLE IF NOT EXISTS online_classes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    school_id       INT NOT NULL,
    class_id        INT NOT NULL,
    main_teacher_id INT NOT NULL,
    created_by      INT NOT NULL,
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
