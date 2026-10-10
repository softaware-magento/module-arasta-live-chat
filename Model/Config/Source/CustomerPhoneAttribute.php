<?php
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model\Config\Source;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Customer attributes that can hold a phone number (text inputs), for the "Telephone Attribute" setting. Magento has no
 * standard phone attribute on the customer, so the merchant chooses theirs (e.g. phone, mobile, telephone).
 */
class CustomerPhoneAttribute implements OptionSourceInterface
{
    /** Text attributes that never hold a phone number. */
    private const EXCLUDED = ['email', 'firstname', 'lastname', 'middlename', 'prefix', 'suffix', 'taxvat', 'password_hash', 'rp_token'];

    public function __construct(private readonly CustomerMetadataInterface $customerMetadata)
    {
    }

    /** @return list<array{value: string, label: string}> */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => (string) __('-- Not used --')]];
        foreach ($this->customerMetadata->getAllAttributesMetadata() as $attribute) {
            $code = (string) $attribute->getAttributeCode();
            if ($attribute->getFrontendInput() !== 'text' || in_array($code, self::EXCLUDED, true)) {
                continue;
            }
            $options[] = ['value' => $code, 'label' => sprintf('%s (%s)', (string) $attribute->getFrontendLabel(), $code)];
        }
        return $options;
    }
}
