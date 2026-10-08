<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\ArastaLiveChat\Api\QuoteAttributeInterface;

/**
 * `POST /V1/platform/quote/attribute`: writes `platform_conversation_id` on the active quote. The quote is resolved server-side: a logged-in
 * customer's active cart from the web API user context (storefront session), else the guest cart behind the masked id.
 * The attribute is copied to the order (etc/fieldset.xml) and posted to Arasta when the order is placed.
 */
class QuoteAttribute implements QuoteAttributeInterface
{
    public const ATTRIBUTE = 'platform_conversation_id';
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly CartRepositoryInterface $carts,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function set(string $conversationId, ?string $cartId = null): bool
    {
        if (preg_match(self::UUID, $conversationId) !== 1) {
            throw new InputException(__('conversationId must be a UUID.'));
        }
        if (!$this->config->isEnabled((int) $this->storeManager->getStore()->getId())) {
            // Module switched off for this store view: behave as if there were nothing to tag.
            throw new NoSuchEntityException(__('No active cart.'));
        }
        $quote = $this->resolveQuote($cartId);
        if ($quote->getData(self::ATTRIBUTE) === $conversationId) {
            return true;
        }
        $quote->setData(self::ATTRIBUTE, $conversationId);
        $this->carts->save($quote);
        return true;
    }

    /**
     * @throws NoSuchEntityException when the shopper has no active quote
     */
    private function resolveQuote(?string $cartId): Quote
    {
        $customerId = (int) $this->userContext->getUserId();
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER && $customerId > 0) {
            return $this->asQuote($this->carts->getActiveForCustomer($customerId));
        }
        if ($cartId === null || $cartId === '') {
            throw new NoSuchEntityException(__('No active cart.'));
        }
        $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        $quote = $this->asQuote($this->carts->get($quoteId));
        if ($quote->getCustomerId()) {
            // A masked id never grants access to a customer's cart; the customer session does.
            throw new NoSuchEntityException(__('No active cart.'));
        }
        return $quote;
    }

    /**
     * The cart repository returns the CartInterface contract; the custom attribute and the customer id are read and
     * written through the Quote model's data accessors.
     *
     * @throws NoSuchEntityException
     */
    private function asQuote(CartInterface $cart): Quote
    {
        if (!$cart instanceof Quote) {
            throw new NoSuchEntityException(__('No active cart.'));
        }
        return $cart;
    }
}
