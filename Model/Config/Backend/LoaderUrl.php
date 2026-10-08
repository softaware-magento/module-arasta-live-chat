<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

/**
 * Widget loader URL: an absolute https:// URL. Plain http:// is accepted only for localhost and 127.0.0.1 (local
 * development), because the loader runs on every storefront page and order links are posted to the same host.
 */
class LoaderUrl extends Value
{
    public function beforeSave()
    {
        $value = trim((string) $this->getValue());
        $this->setValue($value);
        if ($value === '') {
            return parent::beforeSave();
        }
        $parts = parse_url($value);
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $local = in_array($host, ['localhost', '127.0.0.1'], true);
        if ($host === '' || !($scheme === 'https' || ($scheme === 'http' && $local)) || isset($parts['user']) || isset($parts['pass'])) {
            throw new ValidatorException(__('The widget loader URL must be a full https:// address, for example the one shown in your Arasta dashboard.'));
        }
        return parent::beforeSave();
    }
}
