<?php
declare(strict_types=1);

namespace Platform\Connector\Test\Unit;

use Magento\Framework\App\ProductMetadataInterface;
use PHPUnit\Framework\TestCase;
use Platform\Connector\Model\Data\VersionInfo;
use Platform\Connector\Model\Data\VersionInfoFactory;
use Platform\Connector\Model\Version;

class VersionTest extends TestCase
{
    /** R-MOD-03: version endpoint reports module version, Magento version and capabilities */
    public function testReportsVersionsAndCapabilities(): void
    {
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.9');
        $factory = $this->getMockBuilder(VersionInfoFactory::class)->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $factory->method('create')->willReturnCallback(fn (array $data) => new VersionInfo(...$data));

        $info = (new Version($metadata, $factory))->get();
        self::assertSame('1.1.0', $info->getModuleVersion());
        self::assertSame('2.4.9', $info->getMagentoVersion());
        self::assertSame(['invoice_pdf', 'signed_identity', 'quote_attribute', 'order_link'], $info->getCapabilities());
    }
}
