<?php
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Test\Unit;

use Magento\Framework\App\ProductMetadataInterface;
use PHPUnit\Framework\TestCase;
use Softaware\ArastaLiveChat\Model\Data\VersionInfo;
use Softaware\ArastaLiveChat\Model\Data\VersionInfoFactory;
use Softaware\ArastaLiveChat\Model\Version;

class VersionTest extends TestCase
{
    /** version endpoint reports module version, Magento version and capabilities */
    public function testReportsVersionsAndCapabilities(): void
    {
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.9');
        $factory = $this->getMockBuilder(VersionInfoFactory::class)->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $factory->method('create')->willReturnCallback(fn (array $data) => new VersionInfo(...$data));

        $info = (new Version($metadata, $factory))->get();
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $info->getModuleVersion());
        self::assertSame('2.4.9', $info->getMagentoVersion());
        self::assertSame(['invoice_pdf', 'signed_identity', 'quote_attribute', 'order_link'], $info->getCapabilities());
    }
}
