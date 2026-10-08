<?php
declare(strict_types=1);

// Standalone bootstrap (CI: Magento packages from the Mage-OS mirror, no full Magento install).
// Generated factories are declared here because Magento's code generator is not available.
require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('__')) {
    function __(string $text, ...$args): \Magento\Framework\Phrase
    {
        return new \Magento\Framework\Phrase($text, $args);
    }
}

eval('namespace Platform\Connector\Model\Data; class PdfFileFactory { public function create(array $data = []) {} }');
eval('namespace Platform\Connector\Model\Data; class VersionInfoFactory { public function create(array $data = []) {} }');
eval('namespace Magento\Quote\Model; class QuoteIdMaskFactory { public function create(array $data = []) {} }');
