<?php
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Test\Unit;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use PHPUnit\Framework\TestCase;
use Softaware\ArastaLiveChat\Model\Config\Source\CustomerPhoneAttribute;

class CustomerPhoneAttributeTest extends TestCase
{
    private function attribute(string $code, string $label, string $input): AttributeMetadataInterface
    {
        $a = $this->createMock(AttributeMetadataInterface::class);
        $a->method('getAttributeCode')->willReturn($code);
        $a->method('getFrontendLabel')->willReturn($label);
        $a->method('getFrontendInput')->willReturn($input);
        return $a;
    }

    /** The merchant picks any text attribute (phone, mobile, telephone…); name/email/non-text attributes are not offered */
    public function testListsTextAttributesExceptKnownNonPhoneOnes(): void
    {
        $metadata = $this->createMock(CustomerMetadataInterface::class);
        $metadata->method('getAllAttributesMetadata')->willReturn([
            $this->attribute('email', 'Email', 'text'),
            $this->attribute('mobile_phone', 'Mobile phone', 'text'),
            $this->attribute('dob', 'Date of birth', 'date'),
            $this->attribute('telephone', 'Telephone', 'text'),
        ]);
        $options = (new CustomerPhoneAttribute($metadata))->toOptionArray();
        self::assertSame(['', 'mobile_phone', 'telephone'], array_column($options, 'value'));
        self::assertSame('Mobile phone (mobile_phone)', $options[1]['label']);
    }
}
