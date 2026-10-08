<?php
declare(strict_types=1);

namespace Platform\Connector\Model\Data;

use Platform\Connector\Api\Data\VersionInfoInterface;

class VersionInfo implements VersionInfoInterface
{
    /**
     * @param string[] $capabilities
     */
    public function __construct(
        private readonly string $moduleVersion,
        private readonly string $magentoVersion,
        private readonly array $capabilities
    ) {
    }

    public function getModuleVersion(): string
    {
        return $this->moduleVersion;
    }

    public function getMagentoVersion(): string
    {
        return $this->magentoVersion;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }
}
