<?php
/**
 * Softaware Arasta Live Chat: unit test bootstrap.
 *
 * Runs against a Magento installation (MAGENTO_ROOT, default: four levels up from app/code/Softaware/<Module>)
 * or against this package's own vendor/ directory. Generated factories are declared here when the Magento code
 * generator is not available.
 */
declare(strict_types=1);

$root = getenv('MAGENTO_ROOT') ?: dirname(__DIR__, 5);
if (is_file($root . '/app/autoload.php')) {
    require $root . '/app/autoload.php';
} else {
    require __DIR__ . '/../vendor/autoload.php';
}
spl_autoload_register(static function (string $class): void {
    $prefix = 'Softaware\\ArastaLiveChat\\';
    if (str_starts_with($class, $prefix)) {
        $file = dirname(__DIR__) . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

if (!function_exists('__')) {
    function __(string $text, ...$args): \Magento\Framework\Phrase
    {
        return new \Magento\Framework\Phrase($text, $args);
    }
}

foreach ([
    'Softaware\ArastaLiveChat\Model\Data\PdfFileFactory',
    'Softaware\ArastaLiveChat\Model\Data\VersionInfoFactory',
    'Magento\Quote\Model\QuoteIdMaskFactory',
] as $factory) {
    if (!class_exists($factory)) {
        $pos = strrpos($factory, '\\');
        eval(sprintf('namespace %s; class %s { public function create(array $data = []) {} }', substr($factory, 0, $pos), substr($factory, $pos + 1)));
    }
}
