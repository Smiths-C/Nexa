<?php
/** فراخوانی امن endpoint های ویژه‌ی هاب روی یک هاست مدرسه، با هدر رمز مشترک */
class HostClient
{
    public static function call(string $baseUrl, string $path, array $body, string $sharedSecret): array
    {
        $ch = curl_init(rtrim($baseUrl, '/') . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Hub-Secret: ' . $sharedSecret,
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'message' => "اتصال به هاست برقرار نشد: $error"];
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'message' => 'پاسخ نامعتبر از هاست'];
        }
        return $decoded;
    }
}
