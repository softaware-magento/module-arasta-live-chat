<?php
declare(strict_types=1);

namespace Platform\Connector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Module configuration (store scope): widget loader URL and store key, and the store signing secret that signs the
 * customer identity (R-MOD-02) and the order link (R-PD-03).
 */
class Config
{
    public const XML_LOADER_URL = 'platform_connector/widget/loader_url';
    public const XML_STORE_KEY = 'platform_connector/widget/store_key';
    public const XML_SECRET = 'platform_connector/identity/signing_secret';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function loaderUrl(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_LOADER_URL, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function storeKey(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_STORE_KEY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function signingSecret(?int $storeId = null): ?string
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_SECRET, ScopeInterface::SCOPE_STORE, $storeId);
        if ($value === '') {
            return null;
        }
        // Accept values stored encrypted (e.g. "0:3:...") as well as plain values from env.php (--lock-env).
        return preg_match('/^\d+:\d+:/', $value) === 1 ? $this->encryptor->decrypt($value) : $value;
    }
}
