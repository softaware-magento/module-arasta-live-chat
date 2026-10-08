<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model\Data;

use Softaware\ArastaLiveChat\Api\Data\PdfFileInterface;

class PdfFile implements PdfFileInterface
{
    public function __construct(
        private readonly string $fileName,
        private readonly string $content,
        private readonly string $contentType = 'application/pdf'
    ) {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
