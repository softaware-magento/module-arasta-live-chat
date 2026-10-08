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
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;
use Softaware\ArastaLiveChat\Model\Config;
use Softaware\ArastaLiveChat\Model\OrderLinkPayload;
use Softaware\ArastaLiveChat\Model\QuoteAttribute;
use Psr\Log\LoggerInterface;

/**
 * On `sales_order_place_after`, an order whose quote carried an Arasta conversation id is posted to Arasta,
 * signed with the store signing secret. Best effort: any failure is logged and never affects the checkout.
 */
class OrderPlaced implements ObserverInterface
{
    private const TIMEOUT_SECONDS = 3;

    public function __construct(
        private readonly Config $config,
        private readonly OrderLinkPayload $payload,
        private readonly Curl $http,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Order|null $order */
        $order = $observer->getEvent()->getData('order');
        if (!$order instanceof Order) {
            return;
        }
        $conversationId = (string) $order->getData(QuoteAttribute::ATTRIBUTE);
        if ($conversationId === '') {
            return;
        }
        try {
            $storeId = (int) $order->getStoreId();
            if (!$this->config->isEnabled($storeId)) {
                return;
            }
            $secret = $this->config->signingSecret($storeId);
            $arastaStoreId = $this->config->arastaStoreId($storeId);
            $origin = OrderLinkPayload::apiOrigin($this->config->loaderUrl($storeId));
            if ($secret === null || $arastaStoreId === '' || $origin === null) {
                return;
            }
            $signed = $this->payload->build($secret, $arastaStoreId, [
                'orderId' => $order->getEntityId() !== null ? (int) $order->getEntityId() : null,
                'incrementId' => (string) $order->getIncrementId(),
                'grandTotal' => (float) $order->getGrandTotal(),
                'currency' => (string) $order->getOrderCurrencyCode(),
            ], $conversationId);
            $this->http->setTimeout(self::TIMEOUT_SECONDS);
            $this->http->addHeader('Content-Type', 'application/json');
            $this->http->addHeader(OrderLinkPayload::SIGNATURE_HEADER, $signed['signature']);
            $this->http->post($origin . OrderLinkPayload::PATH, $signed['body']);
            if ($this->http->getStatus() >= 300) {
                $this->logger->warning('Arasta Live Chat: order link rejected', ['status' => $this->http->getStatus(), 'increment_id' => $order->getIncrementId()]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Arasta Live Chat: order link failed: ' . $e->getMessage(), ['increment_id' => $order->getIncrementId()]);
        }
    }
}
