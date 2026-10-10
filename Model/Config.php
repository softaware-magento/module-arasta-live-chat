<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed access to Stores > Configuration > Softaware > Arasta Live Chat (store view scope).
 *
 * The 1.x package (platform/module-connector) read the same settings from `platform_connector/*`. A data patch copies
 * database values to the new paths; values that only exist in app/etc/env.php or config.php under the old paths
 * (`config:set --lock-env`) are still read as a fallback when the new path is empty.
 */
class Config
{
    public const SECTION = 'softaware_arasta_live_chat';
    public const LEGACY_SECTION = 'platform_connector';

    public const XML_ENABLED = 'general/enabled';
    public const XML_LOADER_URL = 'widget/loader_url';
    public const XML_STORE_ID = 'widget/store_id';
    public const XML_SECRET = 'identity/signing_secret';
    public const XML_TTL = 'identity/ttl_seconds';
    /** Customer attribute holding a verified phone number (empty = not used). */
    public const XML_PHONE_ATTRIBUTE = 'identity/phone_attribute';

    /** New field => the 1.x field under `platform_connector/`. */
    public const LEGACY_FIELDS = [
        self::XML_LOADER_URL => 'widget/loader_url',
        self::XML_STORE_ID => 'widget/store_key',
        self::XML_SECRET => 'identity/signing_secret',
        self::XML_TTL => 'identity/ttl_seconds',
    ];

    public const DEFAULT_LOADER_URL = 'https://app.arasta.io/widget/v1/assets/loader.js';
    public const DEFAULT_TTL = 3600;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::SECTION . '/' . self::XML_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function loaderUrl(?int $storeId = null): string
    {
        return trim($this->value(self::XML_LOADER_URL, $storeId));
    }

    /** The Arasta store ID (`data-store-id` on the loader tag, `storeId` in the order link). */
    public function arastaStoreId(?int $storeId = null): string
    {
        return trim($this->value(self::XML_STORE_ID, $storeId));
    }

    /** True when the widget loader can be rendered: module on, loader URL and store ID set. */
    public function isWidgetActive(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->loaderUrl($storeId) !== '' && $this->arastaStoreId($storeId) !== '';
    }

    /** The plain signing secret, or null when none is configured. */
    public function signingSecret(?int $storeId = null): ?string
    {
        $value = trim($this->value(self::XML_SECRET, $storeId));
        if ($value === '') {
            return null;
        }
        // Values saved in the admin are encrypted ("0:3:..."); values from env.php (--lock-env) may be plain.
        if (preg_match('/^\d+:\d+:/', $value) === 1) {
            $value = trim($this->encryptor->decrypt($value));
        }
        return $value !== '' ? $value : null;
    }

    /** Identity token lifetime in seconds (the issuer caps it at one hour). */
    public function tokenLifetime(?int $storeId = null): int
    {
        $ttl = (int) $this->value(self::XML_TTL, $storeId);
        return $ttl > 0 ? $ttl : self::DEFAULT_TTL;
    }

    /** Code of the customer attribute with the verified phone number; null when not used. */
    public function phoneAttribute(?int $storeId = null): ?string
    {
        $code = trim($this->value(self::XML_PHONE_ATTRIBUTE, $storeId));
        return $code !== '' ? $code : null;
    }

    private function value(string $field, ?int $storeId): string
    {
        $value = (string) $this->scopeConfig->getValue(self::SECTION . '/' . $field, ScopeInterface::SCOPE_STORE, $storeId);
        $legacyField = self::LEGACY_FIELDS[$field] ?? null;
        if ($legacyField !== null && ($value === '' || ($field === self::XML_LOADER_URL && $value === self::DEFAULT_LOADER_URL))) {
            $legacy = (string) $this->scopeConfig->getValue(self::LEGACY_SECTION . '/' . $legacyField, ScopeInterface::SCOPE_STORE, $storeId);
            if (trim($legacy) !== '') {
                return $legacy;
            }
        }
        return $value;
    }
}
