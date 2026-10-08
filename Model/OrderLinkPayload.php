<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model;

/**
 * the signed body the module posts to the platform when an order placed from a tagged quote is saved.
 * `POST {api}/public/v1/integrations/magento/orders` with `X-Platform-Signature: sha256=<hex HMAC of the raw body>`
 * keyed with the store signing secret (the secret that also signs the customer identity).
 * Framework-independent so it can be unit tested without Magento.
 */
class OrderLinkPayload
{
    public const PATH = '/public/v1/integrations/magento/orders';
    public const SIGNATURE_HEADER = 'X-Platform-Signature';

    /**
     * @param array{orderId: int|string|null, incrementId: string, grandTotal: float, currency: string} $order
     * @return array{body: string, signature: string}
     */
    public function build(string $secret, string $storeKey, array $order, string $conversationId): array
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('The signing secret must be at least 32 characters.');
        }
        $body = (string) json_encode([
            'storeId' => $storeKey,
            'orderId' => $order['orderId'],
            'incrementId' => $order['incrementId'],
            'grandTotal' => round($order['grandTotal'], 2),
            'currency' => strtoupper($order['currency']),
            'conversationId' => $conversationId,
        ], JSON_UNESCAPED_SLASHES);
        return ['body' => $body, 'signature' => self::sign($secret, $body)];
    }

    public static function sign(string $secret, string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', $body, $secret);
    }

    /** The platform API origin, derived from the configured loader URL (`https://api…/widget/v1/assets/loader.js`). */
    public static function apiOrigin(string $loaderUrl): ?string
    {
        $parts = parse_url($loaderUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
