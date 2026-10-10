<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model;

/**
 * Signed customer identity for the Arasta widget: HS256 JWT with sub = Magento customer id, email, iat, exp (between one minute and one hour).
 * Framework-independent so it can be unit tested without Magento.
 */
class IdentityTokenIssuer
{
    public const MAX_TTL_SECONDS = 3600;
    public const MIN_TTL_SECONDS = 60;

    public function issue(string $secret, int $customerId, string $email, int $now, int $ttlSeconds = self::MAX_TTL_SECONDS, ?string $phone = null): string
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('The signing secret must be at least 32 characters.');
        }
        $ttl = max(self::MIN_TTL_SECONDS, min($ttlSeconds, self::MAX_TTL_SECONDS));
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $claims = ['sub' => (string) $customerId, 'email' => $email, 'iat' => $now, 'exp' => $now + $ttl];
        // `phone`: the customer's verified phone (Telephone Attribute), used to match other channels such as WhatsApp.
        $phone = $phone !== null ? trim($phone) : '';
        if ($phone !== '') {
            $claims['phone'] = substr($phone, 0, 40);
        }
        $signingInput = self::base64Url((string) json_encode($header)) . '.' . self::base64Url((string) json_encode($claims));
        return $signingInput . '.' . self::base64Url(hash_hmac('sha256', $signingInput, $secret, true));
    }

    public static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
