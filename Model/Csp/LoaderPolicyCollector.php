<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model\Csp;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\ArastaLiveChat\Model\Config;
use Softaware\ArastaLiveChat\Model\OrderLinkPayload;

/**
 * Content Security Policy (storefront): allows the configured Arasta widget host, which is set per store view and so
 * cannot be listed in a static csp_whitelist.xml. The widget loads its script from that host and talks to it over
 * HTTPS and WebSocket; it may also show images, styles, fonts and frames from it.
 */
class LoaderPolicyCollector implements PolicyCollectorInterface
{
    private const DIRECTIVES = ['script-src', 'connect-src', 'img-src', 'style-src', 'font-src', 'frame-src'];

    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState
    ) {
    }

    /**
     * @param \Magento\Csp\Api\Data\PolicyInterface[] $defaultPolicies
     * @return \Magento\Csp\Api\Data\PolicyInterface[]
     */
    public function collect(array $defaultPolicies = []): array
    {
        try {
            if ($this->appState->getAreaCode() !== Area::AREA_FRONTEND) {
                return $defaultPolicies;
            }
            $storeId = (int) $this->storeManager->getStore()->getId();
            if (!$this->config->isWidgetActive($storeId)) {
                return $defaultPolicies;
            }
            $origin = OrderLinkPayload::apiOrigin($this->config->loaderUrl($storeId));
        } catch (\Throwable) {
            return $defaultPolicies;
        }
        if ($origin === null) {
            return $defaultPolicies;
        }
        $policies = $defaultPolicies;
        foreach (self::DIRECTIVES as $directive) {
            $hosts = [$origin];
            if ($directive === 'connect-src') {
                $hosts[] = preg_replace('#^http#', 'ws', $origin);
            }
            $policies[] = new FetchPolicy($directive, false, $hosts);
        }
        return $policies;
    }
}
