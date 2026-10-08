<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Api;

/**
 * capability discovery.
 */
interface VersionInterface
{
    /**
     * @return \Softaware\ArastaLiveChat\Api\Data\VersionInfoInterface
     */
    public function get(): Data\VersionInfoInterface;
}
