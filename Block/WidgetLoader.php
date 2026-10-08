<?php
declare(strict_types=1);

namespace Platform\Connector\Block;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

/**
 * Renders the widget loader tag (cacheable, no customer data). The identity token is added client-side from the
 * `platform-identity` customer-data section as `data-customer-token` (R-MOD-02).
 */
class WidgetLoader extends Template
{
    public function getLoaderUrl(): string
    {
        return (string) $this->_scopeConfig->getValue('platform_connector/widget/loader_url', ScopeInterface::SCOPE_STORE);
    }

    public function getStoreKey(): string
    {
        return (string) $this->_scopeConfig->getValue('platform_connector/widget/store_key', ScopeInterface::SCOPE_STORE);
    }

    protected function _toHtml(): string
    {
        return $this->getLoaderUrl() === '' || $this->getStoreKey() === '' ? '' : parent::_toHtml();
    }
}
