<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Softaware\ArastaLiveChat\Model\Config;

/**
 * Renders the Arasta widget loader (cacheable, no customer data). The identity token and the guest cart id are added
 * in the browser from the private `platform-identity` customer-data section (`data-customer-token`, `data-cart-id`).
 * Renders nothing when the module is disabled or the loader URL / store ID are missing.
 */
class WidgetLoader extends Template
{
    /** Customer-data section holding the identity token (name kept from 1.x). */
    public const SECTION = 'platform-identity';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getLoaderUrl(): string
    {
        return $this->config->loaderUrl($this->storeId());
    }

    public function getArastaStoreId(): string
    {
        return $this->config->arastaStoreId($this->storeId());
    }

    public function getSectionName(): string
    {
        return self::SECTION;
    }

    protected function _toHtml(): string
    {
        return $this->config->isWidgetActive($this->storeId()) ? parent::_toHtml() : '';
    }

    private function storeId(): int
    {
        return (int) $this->_storeManager->getStore()->getId();
    }
}
