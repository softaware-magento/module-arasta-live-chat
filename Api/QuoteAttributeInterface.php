<?php
declare(strict_types=1);

namespace Platform\Connector\Api;

/**
 * R-PD-02 (Phase 2): tags the shopper's active quote with the platform conversation id, so the order placed from it
 * can be linked to the conversation (R-PD-03). Called by the widget loader from the storefront with the shopper's
 * session (customer) or the guest's masked quote id.
 */
interface QuoteAttributeInterface
{
    /**
     * @param string $conversationId Platform conversation id (UUID).
     * @param string|null $cartId Masked quote id of a guest cart; ignored for logged-in customers.
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function set(string $conversationId, ?string $cartId = null): bool;
}
