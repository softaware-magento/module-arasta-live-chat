<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Setup\Patch\Data;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Softaware\ArastaLiveChat\Model\Config;

/**
 * Upgrade from platform/module-connector 1.x (Platform_Connector):
 * - copies saved settings from `platform_connector/*` to `softaware_arasta_live_chat/*` (every scope; values already
 *   saved under the new path win; `widget/store_key` becomes `widget/store_id`; the signing secret is stored encrypted);
 * - grants the new ACL resource Softaware_ArastaLiveChat::api to every role and integration that had
 *   Platform_Connector::api, so existing Arasta integrations keep working without re-authorising.
 * The old rows are left in place. Does nothing on a fresh install.
 */
class MigrateFromPlatformConnector implements DataPatchInterface
{
    public const LEGACY_ACL = 'Platform_Connector::api';
    public const NEW_ACL = 'Softaware_ArastaLiveChat::api';

    public function __construct(
        private readonly ModuleDataSetupInterface $setup,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function apply(): self
    {
        $connection = $this->setup->getConnection();
        $connection->startSetup();
        $this->copyConfig();
        $this->copyAclRules();
        $connection->endSetup();
        return $this;
    }

    private function copyConfig(): void
    {
        $connection = $this->setup->getConnection();
        $table = $this->setup->getTable('core_config_data');
        foreach (Config::LEGACY_FIELDS as $field => $legacyField) {
            $rows = $connection->fetchAll(
                $connection->select()->from($table, ['scope', 'scope_id', 'value'])
                    ->where('path = ?', Config::LEGACY_SECTION . '/' . $legacyField)
            );
            foreach ($rows as $row) {
                $value = $row['value'];
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }
                $value = trim((string) $value);
                if ($field === Config::XML_SECRET && preg_match('/^\d+:\d+:/', $value) !== 1) {
                    $value = $this->encryptor->encrypt($value);
                }
                $exists = $connection->fetchOne(
                    $connection->select()->from($table, ['config_id'])
                        ->where('path = ?', Config::SECTION . '/' . $field)
                        ->where('scope = ?', $row['scope'])
                        ->where('scope_id = ?', (int) $row['scope_id'])
                );
                if ($exists) {
                    continue;
                }
                $connection->insert($table, [
                    'scope' => $row['scope'],
                    'scope_id' => (int) $row['scope_id'],
                    'path' => Config::SECTION . '/' . $field,
                    'value' => $value,
                ]);
            }
        }
    }

    private function copyAclRules(): void
    {
        $connection = $this->setup->getConnection();
        $table = $this->setup->getTable('authorization_rule');
        if (!$connection->isTableExists($table)) {
            return;
        }
        $roleIds = $connection->fetchCol(
            $connection->select()->from($table, ['role_id'])
                ->where('resource_id = ?', self::LEGACY_ACL)
                ->where('permission = ?', 'allow')
        );
        foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
            $exists = $connection->fetchOne(
                $connection->select()->from($table, ['rule_id'])
                    ->where('role_id = ?', $roleId)
                    ->where('resource_id = ?', self::NEW_ACL)
            );
            if ($exists) {
                continue;
            }
            $connection->insert($table, [
                'role_id' => $roleId,
                'resource_id' => self::NEW_ACL,
                'privileges' => null,
                'permission' => 'allow',
            ]);
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
