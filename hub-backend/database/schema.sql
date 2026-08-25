-- =========================================================
--  دیتابیس هاب مرکزی Nexa
--  این دیتابیس هیچ اطلاعات دانش‌آموز/معلم/تکلیفی نداره — فقط "دفترچه راهنماست":
--  کدوم مدرسه رو کدوم هاست هست، و ادمین کل هاب چطور بهش وصل بشه.
-- =========================================================

-- ادمین(های) هاب مرکزی (سطح بالاتر از ادمین هر هاست)
CREATE TABLE hub_admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255) NOT NULL,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- هر سروری (هاست) که قراره مدرسه‌ها روش میزبانی بشن.
-- می‌تونه هاست اشتراکی معمولی باشه یا VPS؛ هرکدوم می‌تونن چند مدرسه هم داشته باشن.
CREATE TABLE hosts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,           -- مثلا: "هاست ایران ۱" یا "سرور اروپا"
    api_base_url   VARCHAR(500) NOT NULL,            -- مثلا https://school-host1.com
    shared_secret  VARCHAR(255) NOT NULL,             -- برای احراز هویت هاب هنگام صدا زدن این هاست
    status         ENUM('active','disabled') DEFAULT 'active',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- دفترچه راهنمای مدرسه‌ها: هر مدرسه یه school_code یکتا داره که کاربرهاش
-- موقع ورود واردش می‌کنن تا اپ بفهمه باید به کدوم هاست وصل بشه.
CREATE TABLE schools_registry (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    school_code      VARCHAR(30)  NOT NULL UNIQUE,     -- مثلا NEXA-4821
    name             VARCHAR(255) NOT NULL,
    national_code    VARCHAR(50)  NOT NULL,
    city             VARCHAR(100) NOT NULL,
    host_id          INT NOT NULL,
    remote_school_id INT NOT NULL,                     -- id همین مدرسه توی دیتابیس خودِ هاست
    status           ENUM('active','suspended','deleted') DEFAULT 'active',
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (host_id) REFERENCES hosts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
