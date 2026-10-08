<?php
declare(strict_types=1);

namespace Platform\Connector\Api;

/**
 * R-MOD-01: the store's own invoice PDF.
 */
interface InvoicePdfInterface
{
    /**
     * @param int $invoiceId
     * @return \Platform\Connector\Api\Data\PdfFileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $invoiceId): Data\PdfFileInterface;
}
