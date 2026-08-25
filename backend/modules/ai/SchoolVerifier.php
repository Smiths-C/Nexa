<?php
/**
 * قبل از ثبت‌نام هر مدیر، این کلاس چک می‌کنه:
 *  ۱. فرمت نام مدرسه، کد ملی/رهگیری و شهر درست باشه
 *  ۲. این مدرسه قبلا با همین کد ملی ثبت نشده باشه
 *  ۳. (اختیاری) با یک مدل هوش مصنوعی واقعی، منطقی بودن اطلاعات رو تایید می‌کنه
 *
 * فعلا مرحله‌ی ۳ به صورت placeholder هست و فقط وقتی کلید AI_API_KEY در config.php
 * پر بشه فعال می‌شه؛ در غیر این صورت فقط اعتبارسنجی قانونی (۱ و ۲) انجام می‌شه.
 */
class SchoolVerifier
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function verify(string $name, string $nationalCode, string $city): array
    {
        $name = trim($name);
        $city = trim($city);
        $nationalCode = trim($nationalCode);

        if (mb_strlen($name) < 3) {
            return ['valid' => false, 'message' => 'نام مدرسه نامعتبر است'];
        }
        if (!preg_match('/^\d{8,11}$/', $nationalCode)) {
            return ['valid' => false, 'message' => 'کد ملی/کد رهگیری مدرسه باید بین ۸ تا ۱۱ رقم باشد'];
        }
        if (mb_strlen($city) < 2) {
            return ['valid' => false, 'message' => 'نام شهر نامعتبر است'];
        }

        $stmt = $this->db->prepare("SELECT id FROM schools WHERE national_code = ?");
        $stmt->execute([$nationalCode]);
        if ($stmt->fetch()) {
            return ['valid' => false, 'message' => 'مدرسه‌ای با این کد ملی قبلا ثبت شده است'];
        }

        $stmt = $this->db->prepare("SELECT id FROM schools WHERE name = ? AND city = ?");
        $stmt->execute([$name, $city]);
        if ($stmt->fetch()) {
            return ['valid' => false, 'message' => 'مدرسه‌ای با همین نام در همین شهر قبلا ثبت شده است'];
        }

        return $this->callAi($name, $nationalCode, $city);
    }

    /** اتصال به یک API هوش مصنوعی برای بررسی منطقی بودن اطلاعات (اختیاری) */
    private function callAi(string $name, string $nationalCode, string $city): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        if (empty($config['ai_api_key'])) {
            // هیچ کلید AI تنظیم نشده -> فقط با همون اعتبارسنجی قانونی بالا کافیه
            return ['valid' => true, 'message' => 'ok'];
        }

        $prompt = "بررسی کن آیا اطلاعات زیر برای یک مدرسه واقعی منطقی و معتبر به نظر می‌رسد؟ "
            . "فقط با یکی از دو کلمه‌ی VALID یا INVALID و یک دلیل خیلی کوتاه جواب بده.\n"
            . "نام مدرسه: {$name}\nکد ملی/رهگیری: {$nationalCode}\nشهر: {$city}";

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $config['ai_api_key'],
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 150,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            // اگه سرویس AI در دسترس نبود، جلوی ثبت‌نام رو نمی‌گیریم (fail-open)
            return ['valid' => true, 'message' => 'ok'];
        }

        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? '';

        if (stripos($text, 'INVALID') !== false) {
            return ['valid' => false, 'message' => 'اطلاعات مدرسه توسط سیستم هوشمند تایید نشد'];
        }
        return ['valid' => true, 'message' => 'ok'];
    }
}
