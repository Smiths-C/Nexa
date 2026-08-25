<?php
class JWT
{
    public static function encode(array $payload, string $secret, int $expireSeconds): string
    {
        $header = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + $expireSeconds;
        $payloadEncoded = self::b64(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payloadEncoded", $secret, true);
        return "$header.$payloadEncoded." . self::b64($signature);
    }

    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $payload, $signature] = $parts;

        $expected = self::b64(hash_hmac('sha256', "$header.$payload", $secret, true));
        if (!hash_equals($expected, $signature)) return null;

        $data = json_decode(self::unb64($payload), true);
        if (!$data || (isset($data['exp']) && $data['exp'] < time())) return null;

        return $data;
    }

    private static function b64(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }
    private static function unb64(string $data): string { return base64_decode(strtr($data, '-_', '+/')); }
}
