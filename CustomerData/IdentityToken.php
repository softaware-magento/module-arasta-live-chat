<?php
declare(strict_types=1);

namespace Platform\Connector\CustomerData;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Store\Model\ScopeInterface;
use Platform\Connector\Model\IdentityTokenIssuer;

/**
 * R-MOD-02: private-content section holding the signed identity token of the logged-in customer.
 * Served through customer-data (never full-page cached). Phase 2 (R-PD-02): for guests it also carries the masked id
 * of the active quote (`cartId`), which the widget loader sends to the quote attribute endpoint.
 */
class IdentityToken implements SectionSourceInterface
{
    public const XML_SECRET = 'platform_connector/identity/signing_secret';
    public const XML_TTL = 'platform_connector/identity/ttl_seconds';

    public function __construct(
        private readonly CurrentCustomer $currentCustomer,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly IdentityTokenIssuer $issuer,
        private readonly CheckoutSession $checkoutSession,
        private readonly QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
    }

    /**
     * @return array{token: string|null, expiresAt: int|null, cartId: string|null}
     */
    public function getSectionData(): array
    {
        $customerId = (int) $this->currentCustomer->getCustomerId();
        $secret = $this->secret();
        if ($customerId <= 0 || $secret === null) {
            return ['token' => null, 'expiresAt' => null, 'cartId' => $customerId <= 0 ? $this->guestCartId() : null];
        }
        $customer = $this->currentCustomer->getCustomer();
        $now = time();
        $ttl = (int) ($this->scopeConfig->getValue(self::XML_TTL, ScopeInterface::SCOPE_STORE) ?: IdentityTokenIssuer::MAX_TTL_SECONDS);
        $token = $this->issuer->issue($secret, $customerId, (string) $customer->getEmail(), $now, $ttl);
        return ['token' => $token, 'expiresAt' => $now + min($ttl, IdentityTokenIssuer::MAX_TTL_SECONDS), 'cartId' => null];
    }

    /** Masked id of the guest's active quote (created on demand, as the guest cart API does); null without a quote. */
    private function guestCartId(): ?string
    {
        try {
            $quoteId = (int) $this->checkoutSession->getQuoteId();
            if ($quoteId <= 0) {
                return null;
            }
            $masked = $this->quoteIdToMaskedQuoteId->execute($quoteId);
            if ($masked === '') {
                $mask = $this->quoteIdMaskFactory->create();
                $mask->setQuoteId($quoteId)->save();
                $masked = (string) $mask->getMaskedId();
            }
            return $masked !== '' ? $masked : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function secret(): ?string
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_SECRET, ScopeInterface::SCOPE_STORE);
        if ($value === '') {
            return null;
        }
        // Accept values stored encrypted (e.g. "0:3:...") as well as plain values from env.php (--lock-env).
        return preg_match('/^\d+:\d+:/', $value) === 1 ? $this->encryptor->decrypt($value) : $value;
    }
}
