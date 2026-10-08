<?php
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Test\Unit;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;
use Softaware\ArastaLiveChat\Model\Config;

class ConfigTest extends TestCase
{
    /** @param array<string, string> $values */
    private function config(array $values): Config
    {
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(fn (string $path) => $values[$path] ?? null);
        $scope->method('isSetFlag')->willReturnCallback(fn (string $path) => (bool) ($values[$path] ?? false));
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('decrypt')->willReturnCallback(fn (string $v) => 'decrypted-' . $v);
        return new Config($scope, $encryptor);
    }

    public function testNewPathsWin(): void
    {
        $config = $this->config([
            'softaware_arasta_live_chat/general/enabled' => '1',
            'softaware_arasta_live_chat/widget/loader_url' => Config::DEFAULT_LOADER_URL,
            'softaware_arasta_live_chat/widget/store_id' => 'new-id',
            'platform_connector/widget/store_key' => 'old-id',
        ]);
        self::assertSame('new-id', $config->arastaStoreId());
        self::assertSame(Config::DEFAULT_LOADER_URL, $config->loaderUrl());
        self::assertTrue($config->isWidgetActive());
    }

    /** 1.x values kept in env.php under platform_connector/* still work */
    public function testFallsBackToLegacyPaths(): void
    {
        $config = $this->config([
            'softaware_arasta_live_chat/general/enabled' => '1',
            'softaware_arasta_live_chat/widget/loader_url' => Config::DEFAULT_LOADER_URL,
            'platform_connector/widget/loader_url' => 'https://api.example.com/widget/v1/assets/loader.js',
            'platform_connector/widget/store_key' => 'old-id',
            'platform_connector/identity/signing_secret' => 'plain-secret-0123456789-0123456789',
        ]);
        self::assertSame('old-id', $config->arastaStoreId());
        self::assertSame('https://api.example.com/widget/v1/assets/loader.js', $config->loaderUrl());
        self::assertSame('plain-secret-0123456789-0123456789', $config->signingSecret());
    }

    public function testDecryptsEncryptedSecretAndDisabledMeansInactive(): void
    {
        $config = $this->config([
            'softaware_arasta_live_chat/general/enabled' => '0',
            'softaware_arasta_live_chat/widget/loader_url' => Config::DEFAULT_LOADER_URL,
            'softaware_arasta_live_chat/widget/store_id' => 'id',
            'softaware_arasta_live_chat/identity/signing_secret' => '0:3:abc',
        ]);
        self::assertSame('decrypted-0:3:abc', $config->signingSecret());
        self::assertFalse($config->isWidgetActive());
        self::assertSame(Config::DEFAULT_TTL, $config->tokenLifetime());
    }
}
