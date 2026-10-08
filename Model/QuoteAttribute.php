<?php
declare(strict_types=1);

namespace Platform\Connector\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Platform\Connector\Api\QuoteAttributeInterface;

/**
 * R-PD-02: writes `platform_conversation_id` on the active quote. The quote is resolved server-side: a logged-in
 * customer's active cart from the web API user context (storefront session), else the guest cart behind the masked id.
 * The attribute is copied to the order (etc/fieldset.xml) and posted to the platform when the order is placed.
 */
class QuoteAttribute implements QuoteAttributeInterface
{
    public const ATTRIBUTE = 'platform_conversation_id';
    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly CartRepositoryInterface $carts,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
    }

    public function set(string $conversationId, ?string $cartId = null): bool
    {
        if (preg_match(self::UUID, $conversationId) !== 1) {
            throw new InputException(__('conversationId must be a UUID.'));
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
    private function resolveQuote(?string $cartId): CartInterface
    {
        $customerId = (int) $this->userContext->getUserId();
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER && $customerId > 0) {
            return $this->carts->getActiveForCustomer($customerId);
        }
        if ($cartId === null || $cartId === '') {
            throw new NoSuchEntityException(__('No active cart.'));
        }
        $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        $quote = $this->carts->get($quoteId);
        if ($quote->getCustomerId()) {
            // A masked id never grants access to a customer's cart; the customer session does.
            throw new NoSuchEntityException(__('No active cart.'));
        }
        return $quote;
    }
}
