<?php
declare(strict_types=1);

namespace Platform\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\Order;
use Platform\Connector\Model\Config;
use Platform\Connector\Model\OrderLinkPayload;
use Platform\Connector\Model\QuoteAttribute;
use Psr\Log\LoggerInterface;

/**
 * R-PD-03: on `sales_order_place_after`, an order whose quote carried the conversation id is posted to the platform,
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
            $secret = $this->config->signingSecret($storeId);
            $storeKey = $this->config->storeKey($storeId);
            $origin = OrderLinkPayload::apiOrigin($this->config->loaderUrl($storeId));
            if ($secret === null || $storeKey === '' || $origin === null) {
                return;
            }
            $signed = $this->payload->build($secret, $storeKey, [
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
                $this->logger->warning('Platform order link rejected', ['status' => $this->http->getStatus(), 'increment_id' => $order->getIncrementId()]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Platform order link failed: ' . $e->getMessage(), ['increment_id' => $order->getIncrementId()]);
        }
    }
}
