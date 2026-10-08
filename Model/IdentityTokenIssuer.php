<?php
declare(strict_types=1);

namespace Platform\Connector\Model;

/**
 * R-MOD-02 / spec 9.4: HS256 JWT with sub = Magento customer id, email, iat, exp (at most 1 hour).
 * Framework-independent so it can be unit tested without Magento.
 */
class IdentityTokenIssuer
{
    public const MAX_TTL_SECONDS = 3600;

    public function issue(string $secret, int $customerId, string $email, int $now, int $ttlSeconds = self::MAX_TTL_SECONDS): string
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('The signing secret must be at least 32 characters.');
        }
        $ttl = max(60, min($ttlSeconds, self::MAX_TTL_SECONDS));
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $claims = ['sub' => (string) $customerId, 'email' => $email, 'iat' => $now, 'exp' => $now + $ttl];
        $signingInput = self::base64Url((string) json_encode($header)) . '.' . self::base64Url((string) json_encode($claims));
        return $signingInput . '.' . self::base64Url(hash_hmac('sha256', $signingInput, $secret, true));
    }

    public static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
