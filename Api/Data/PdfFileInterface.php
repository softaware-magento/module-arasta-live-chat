<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Api\Data;

interface PdfFileInterface
{
    /**
     * @return string
     */
    public function getFileName(): string;

    /**
     * @return string
     */
    public function getContentType(): string;

    /**
     * Base64-encoded PDF bytes.
     *
     * @return string
     */
    public function getContent(): string;
}
