<?php
declare(strict_types=1);

namespace Platform\Connector\Api\Data;

interface VersionInfoInterface
{
    /**
     * @return string
     */
    public function getModuleVersion(): string;

    /**
     * @return string
     */
    public function getMagentoVersion(): string;

    /**
     * @return string[]
     */
    public function getCapabilities(): array;
}
