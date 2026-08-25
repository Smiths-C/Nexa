# استقرار هاب مرکزی Nexa (سیستم چندهاستی)

این بخش کاملاً مستقل از `backend/` هست و روی یه دامنه/ساب‌دامنه‌ی جدا نصب
می‌شه (مثلا `hub.yourdomain.com`). هیچ اطلاعات دانش‌آموز/معلمی نداره — فقط
می‌دونه هر مدرسه رو کدوم هاست میزبانی می‌کنه.

## ۱. آپلود و دیتابیس
مثل `backend/`: پوشه‌ی `hub-backend/` رو آپلود کنید، یه دیتابیس MySQL جدا
براش بسازید، `database/schema.sql` رو اجرا کنید، و `config/config.php` رو
با اطلاعات همون دیتابیس پر کنید.

## ۲. ساخت اولین ادمین هاب
```
https://hub.yourdomain.com/create_hub_admin.php?username=admin&password=YourStrongPass
```
بعدش فایل `create_hub_admin.php` رو پاک کنید.

## ۳. افزودن هاست‌ها (سرورهایی که مدرسه‌ها روشون میزبانی می‌شن)
برای هر هاست مدرسه (که همون `backend/` معمولی رو داره)، باید:

1. روی خودِ اون هاست، تو فایل `backend/config/config.php`، یه مقدار تصادفیِ
   طولانی برای `hub_shared_secret` بذارید (یا `HUB_SHARED_SECRET` رو به‌عنوان
   env تنظیم کنید). این رمز رو جایی امن یادداشت کنید.
2. از پنل هاب (یا مستقیم API) با توکن ادمین هاب، این درخواست رو بزنید:
```
POST https://hub.yourdomain.com/hosts/add
Authorization: Bearer <توکن ادمین هاب>
{
  "name": "هاست ایران ۱",
  "api_base_url": "https://school-host1.com",
  "shared_secret": "همون رمزی که تو مرحله‌ی ۱ گذاشتید"
}
```

## ۴. ساخت مدرسه‌ی جدید (روی یکی از هاست‌ها)
```
POST https://hub.yourdomain.com/schools/create
Authorization: Bearer <توکن ادمین هاب>
{
  "host_id": 1,
  "name": "دبیرستان نمونه",
  "national_code": "12345678",
  "city": "تهران",
  "manager_full_name": "خانم احمدی",
  "manager_username": "manager1",
  "manager_password": "..."
}
```
پاسخ یه `school_code` (مثلا `NEXA-4821`) برمی‌گردونه — همین کد رو به مدیر
مدرسه بدید تا موقع اولین ورود تو اپ واردش کنه.

## ۵. تعلیق/رفع‌تعلیق/حذف
```
POST https://hub.yourdomain.com/schools/action
{ "registry_id": 5, "action": "suspend" }   // یا unsuspend یا delete
```
این خودکار به همون هاست مقصد وصل می‌شه و همونجا هم اعمالش می‌کنه.

## نکات مهم امنیتی
- `hub_shared_secret` هر هاست باید **طولانی و تصادفی** باشه (مثلا با
  `openssl rand -hex 32`) و فقط بین هاب و همون هاست رد و بدل بشه.
- هم هاب هم همه‌ی هاست‌ها **حتما HTTPS** باشن، وگرنه این رمز رو می‌شه شنود کرد.
- اگه دوست ندارید سیستم چندهاستی داشته باشید و فقط یه بک‌اند کافیه، کلا از
  `hub-backend/` صرف‌نظر کنید و تو فلاتر مقدار `ApiService.useHub` رو
  `false` بذارید — همه‌چیز دقیقاً مثل قبل (تک‌هاستی) کار می‌کنه.
