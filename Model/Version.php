<?php
declare(strict_types=1);

namespace Platform\Connector\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Platform\Connector\Api\Data\VersionInfoInterface;
use Platform\Connector\Api\VersionInterface;
use Platform\Connector\Model\Data\VersionInfoFactory;

/**
 * R-MOD-03: module version and the capabilities it adds, for capability discovery.
 */
class Version implements VersionInterface
{
    public const MODULE_VERSION = '1.1.0';
    /** Phase 2 (R-PD-02/03): quote_attribute = conversation tagging endpoint, order_link = signed order post. */
    public const CAPABILITIES = ['invoice_pdf', 'signed_identity', 'quote_attribute', 'order_link'];

    public function __construct(
        private readonly ProductMetadataInterface $productMetadata,
        private readonly VersionInfoFactory $versionInfoFactory
    ) {
    }

    public function get(): VersionInfoInterface
    {
        return $this->versionInfoFactory->create([
            'moduleVersion' => self::MODULE_VERSION,
            'magentoVersion' => $this->productMetadata->getVersion(),
            'capabilities' => self::CAPABILITIES,
        ]);
    }
}
