<?php
declare(strict_types=1);

namespace Platform\Connector\Test\Unit;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Pdf\Invoice as InvoicePdfRenderer;
use PHPUnit\Framework\TestCase;
use Platform\Connector\Model\Data\PdfFile;
use Platform\Connector\Model\Data\PdfFileFactory;
use Platform\Connector\Model\InvoicePdf;

class InvoicePdfTest extends TestCase
{
    private function factory(): PdfFileFactory
    {
        $factory = $this->getMockBuilder(PdfFileFactory::class)->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
        $factory->method('create')->willReturnCallback(fn (array $data) => new PdfFile(...$data));
        return $factory;
    }

    /** R-MOD-01: returns the store's own invoice PDF as base64 with a file name */
    public function testReturnsBase64PdfOfTheInvoice(): void
    {
        $invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->onlyMethods(['getEntityId', 'getIncrementId'])->getMock();
        $invoice->method('getEntityId')->willReturn(7);
        $invoice->method('getIncrementId')->willReturn('000000007');
        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->method('get')->with(7)->willReturn($invoice);
        $pdf = new class {
            public function render(): string { return '%PDF-1.4 test'; }
        };
        $renderer = $this->getMockBuilder(InvoicePdfRenderer::class)->disableOriginalConstructor()->onlyMethods(['getPdf'])->getMock();
        $renderer->method('getPdf')->with([$invoice])->willReturn($pdf);

        $file = (new InvoicePdf($repository, $renderer, $this->factory()))->get(7);
        self::assertSame('invoice-000000007.pdf', $file->getFileName());
        self::assertSame('application/pdf', $file->getContentType());
        self::assertSame('%PDF-1.4 test', base64_decode($file->getContent()));
    }

    /** R-MOD-01: unknown invoice → NoSuchEntityException (HTTP 404) */
    public function testUnknownInvoiceIsNotFound(): void
    {
        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->method('get')->willThrowException(new NoSuchEntityException(__('nope')));
        $renderer = $this->getMockBuilder(InvoicePdfRenderer::class)->disableOriginalConstructor()->getMock();
        $this->expectException(NoSuchEntityException::class);
        (new InvoicePdf($repository, $renderer, $this->factory()))->get(999);
    }
}
