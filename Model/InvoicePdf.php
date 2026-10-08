<?php
declare(strict_types=1);

namespace Platform\Connector\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Pdf\Invoice as InvoicePdfRenderer;
use Platform\Connector\Api\Data\PdfFileInterface;
use Platform\Connector\Api\InvoicePdfInterface;
use Platform\Connector\Model\Data\PdfFileFactory;

/**
 * R-MOD-01: renders the invoice with the store's own PDF template and returns it base64-encoded.
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
