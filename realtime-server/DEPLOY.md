# استقرار سرور کلاس آنلاین (Nexa Realtime) روی VPS

این سرور فقط سیگنالینگ (WebRTC + تخته + دست‌بالا) رو انجام می‌ده و هیچ
دیتابیسی نداره؛ هر بار برای تایید هویت به بک‌اند PHP روی هاست وصل می‌شه.

## ۱. پیش‌نیاز روی VPS
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt-get install -y nodejs
sudo npm install -g pm2
```

## ۲. آپلود و نصب
```bash
# پوشه‌ی realtime-server رو روی VPS آپلود کنید (scp/git/...)
cd realtime-server
cp .env.example .env
nano .env   # PHP_API_BASE_URL رو به آدرس واقعی هاست‌تون تغییر بدید
npm install --production
```

## ۳. اجرا با PM2 (می‌مونه حتی بعد از ری‌استارت سرور)
```bash
pm2 start ecosystem.config.js
pm2 save
pm2 startup   # دستوری که نشون می‌ده رو اجرا کنید تا بعد از ریبوت هم بالا بیاد
```

## ۴. Nginx + SSL (برای wss:// امن)
WebRTC/Socket.IO روی HTTPS باید حتما wss:// باشه، وگرنه مرورگرها/اپ موبایل بلاکش می‌کنن.

```nginx
server {
    listen 443 ssl http2;
    server_name realtime.yourdomain.com;

    ssl_certificate     /etc/letsencrypt/live/realtime.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/realtime.yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:4000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 86400;
    }
}
```
گواهی SSL رایگان با Certbot:
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d realtime.yourdomain.com
```

## ۵. تست
```bash
curl https://realtime.yourdomain.com/health
# باید برگردونه: {"ok":true,"service":"nexa-realtime-server"}
```

## ۶. آدرس رو به فلاتر بدید
تو `frontend/lib/services/signaling_service.dart` مقدار `socketUrl` رو به
`https://realtime.yourdomain.com` تغییر بدید.

## نکته درباره‌ی TURN Server
برای اینکه ویدیو/صدا پشت فایروال‌ها و NAT های سخت‌گیر هم وصل بشه (نه فقط
شبکه‌ی ساده)، بهتره یه TURN server هم داشته باشید. ساده‌ترینش نصب coturn
روی همین VPS:
```bash
sudo apt install coturn
```
و آدرسش رو به تنظیمات ICE Servers توی `signaling_service.dart` اضافه کنید؛
در غیر این صورت فقط STUN عمومی گوگل استفاده می‌شه که برای اکثر شبکه‌های
خانگی/آموزشی کافیه ولی صد در صد نیست.
