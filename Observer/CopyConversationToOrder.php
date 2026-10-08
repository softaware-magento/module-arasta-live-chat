<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Softaware\ArastaLiveChat\Model\QuoteAttribute;

/**
 * `sales_model_service_quote_submit_before`: copies the Arasta conversation id from the quote to the order.
 * The fieldset copy alone is not enough: QuoteManagement merges the converted order through the OrderInterface
 * getters, which drops custom columns. Runs before the order is placed, so the order link observer sees the id.
 */
class CopyConversationToOrder implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getData('quote');
        $order = $observer->getEvent()->getData('order');
        if (!$quote instanceof Quote || !$order instanceof Order) {
            return;
        }
        $conversationId = (string) $quote->getData(QuoteAttribute::ATTRIBUTE);
        if ($conversationId !== '' && (string) $order->getData(QuoteAttribute::ATTRIBUTE) === '') {
            $order->setData(QuoteAttribute::ATTRIBUTE, $conversationId);
        }
    }
}
