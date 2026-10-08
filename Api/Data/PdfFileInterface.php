<?php
declare(strict_types=1);

namespace Platform\Connector\Api\Data;

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
