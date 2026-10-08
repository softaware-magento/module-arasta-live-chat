<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Api;

/**
 * the store's own invoice PDF.
 */
interface InvoicePdfInterface
{
    /**
     * @param int $invoiceId
     * @return \Softaware\ArastaLiveChat\Api\Data\PdfFileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $invoiceId): Data\PdfFileInterface;
}
