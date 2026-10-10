<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\CustomerData;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Softaware\ArastaLiveChat\Model\Config;
use Softaware\ArastaLiveChat\Model\IdentityTokenIssuer;

/**
 * Private-content section `platform-identity`: the signed identity token of the logged-in customer. Served through
 * customer-data (never full-page cached). For guests it carries the masked id of the active quote (`cartId`), which
 * the widget sends to the quote attribute endpoint. Empty when the module is disabled or not configured.
 */
class IdentityToken implements SectionSourceInterface
{
    private const EMPTY = ['token' => null, 'expiresAt' => null, 'cartId' => null];

    public function __construct(
        private readonly CurrentCustomer $currentCustomer,
        private readonly Config $config,
        private readonly IdentityTokenIssuer $issuer,
        private readonly CheckoutSession $checkoutSession,
        private readonly QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{token: string|null, expiresAt: int|null, cartId: string|null}
     */
    public function getSectionData(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isWidgetActive($storeId)) {
            return self::EMPTY;
        }
        $customerId = (int) $this->currentCustomer->getCustomerId();
        if ($customerId <= 0) {
            return ['cartId' => $this->guestCartId()] + self::EMPTY;
        }
        $secret = $this->config->signingSecret($storeId);
        if ($secret === null) {
            return self::EMPTY;
        }
        $now = time();
        $ttl = min(max($this->config->tokenLifetime($storeId), IdentityTokenIssuer::MIN_TTL_SECONDS), IdentityTokenIssuer::MAX_TTL_SECONDS);
        try {
            $customer = $this->currentCustomer->getCustomer();
            $token = $this->issuer->issue($secret, $customerId, (string) $customer->getEmail(), $now, $ttl, $this->phone($customer, $storeId));
        } catch (\InvalidArgumentException $e) {
            // Secret shorter than 32 characters (only possible through env.php / config:set): no token.
            $this->logger->warning('Arasta Live Chat: identity token not issued: ' . $e->getMessage());
            return self::EMPTY;
        }
        return ['token' => $token, 'expiresAt' => $now + $ttl, 'cartId' => null];
    }

    /** The selected telephone attribute's value; null when no attribute is selected or the customer has none. */
    private function phone(\Magento\Customer\Api\Data\CustomerInterface $customer, int $storeId): ?string
    {
        $code = $this->config->phoneAttribute($storeId);
        if ($code === null) {
            return null;
        }
        $value = $customer->getCustomAttribute($code)?->getValue();
        return is_scalar($value) && trim((string) $value) !== '' ? (string) $value : null;
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
}
