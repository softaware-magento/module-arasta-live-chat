<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\Exception\ValidatorException;

/**
 * Stores the Arasta signing secret encrypted and refuses secrets shorter than 32 characters (Arasta rejects them, and
 * so does the token issuer).
 */
class SigningSecret extends Encrypted
{
    public const MIN_LENGTH = 32;

    public function beforeSave()
    {
        $value = trim((string) $this->getValue());
        if ($value !== '' && preg_match('/^\*+$/', $value) !== 1) {
            if (strlen($value) < self::MIN_LENGTH) {
                throw new ValidatorException(
                    __('The Arasta signing secret must be at least %1 characters long.', self::MIN_LENGTH)
                );
            }
            $this->setValue($value);
        }
        return parent::beforeSave();
    }
}
