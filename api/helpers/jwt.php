<?php
/**
 * JWT Helper
 * Simple JWT implementation for API authentication
 */

class JWT
{

    /**
     * Encode payload to JWT token
     */
    public static function encode($payload)
    {
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => JWT_ALGORITHM
        ]));

        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_ACCESS_EXPIRY;

        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = self::sign("$header.$payloadEncoded");

        return "$header.$payloadEncoded.$signature";
    }

    /**
     * Decode and verify JWT token
     */
    public static function decode($token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;

        // Verify signature
        $expectedSignature = self::sign("$header.$payload");
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decode payload
        $data = json_decode(self::base64UrlDecode($payload), true);

        if (!$data) {
            return null;
        }

        // Check expiration
        if (isset($data['exp']) && $data['exp'] < time()) {
            return null;
        }

        return $data;
    }

    /**
     * Generate refresh token
     */
    public static function generateRefreshToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash refresh token for storage
     */
    public static function hashRefreshToken($token)
    {
        return hash('sha256', $token);
    }

    /**
     * Create signature
     */
    private static function sign($data)
    {
        return self::base64UrlEncode(
            hash_hmac('sha256', $data, JWT_SECRET, true)
        );
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
