<?php
/**
 * Softaware Arasta Live Chat
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Pdf\Invoice as InvoicePdfRenderer;
use Softaware\ArastaLiveChat\Api\Data\PdfFileInterface;
use Softaware\ArastaLiveChat\Api\InvoicePdfInterface;
use Softaware\ArastaLiveChat\Model\Data\PdfFileFactory;

/**
 * renders the invoice with the store's own PDF template and returns it base64-encoded.
 */
class InvoicePdf implements InvoicePdfInterface
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly InvoicePdfRenderer $renderer,
        private readonly PdfFileFactory $pdfFileFactory
    ) {
    }

    public function get(int $invoiceId): PdfFileInterface
    {
        $invoice = $this->invoices->get($invoiceId);
        if (!$invoice->getEntityId()) {
            throw new NoSuchEntityException(__('Invoice %1 does not exist.', $invoiceId));
        }
        $pdf = $this->renderer->getPdf([$invoice]);
        return $this->pdfFileFactory->create([
            'fileName' => sprintf('invoice-%s.pdf', $invoice->getIncrementId()),
            'content' => base64_encode($pdf->render()),
        ]);
    }
}
