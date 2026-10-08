<?php
declare(strict_types=1);

namespace Platform\Connector\Api;

/**
 * R-MOD-03: capability discovery.
 */
interface VersionInterface
{
    /**
     * @return \Platform\Connector\Api\Data\VersionInfoInterface
     */
    public function get(): Data\VersionInfoInterface;
}
