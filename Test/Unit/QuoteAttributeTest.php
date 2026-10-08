<?php
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Test\Unit;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Softaware\ArastaLiveChat\Model\Config;
use Softaware\ArastaLiveChat\Model\QuoteAttribute;

class QuoteAttributeTest extends TestCase
{
    private const CONVERSATION = '0199a0e8-7b1c-7000-8000-000000000001';

    /** @return Quote&MockObject */
    private function quote(?int $customerId, ?string $current = null): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->onlyMethods(['getData', 'setData'])
            // getCustomerId is a magic DataObject getter, not a declared method of Quote.
            ->addMethods(['getCustomerId'])->getMock();
        $quote->method('getCustomerId')->willReturn($customerId);
        $quote->method('getData')->with(QuoteAttribute::ATTRIBUTE)->willReturn($current);
        return $quote;
    }

    private function service(UserContextInterface $context, CartRepositoryInterface $carts, MaskedQuoteIdToQuoteIdInterface $masked, bool $enabled = true): QuoteAttribute
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $stores = $this->createMock(StoreManagerInterface::class);
        $stores->method('getStore')->willReturn($store);
        return new QuoteAttribute($context, $carts, $masked, $config, $stores);
    }

    private function context(int $type, ?int $userId): UserContextInterface
    {
        $ctx = $this->createMock(UserContextInterface::class);
        $ctx->method('getUserType')->willReturn($type);
        $ctx->method('getUserId')->willReturn($userId);
        return $ctx;
    }

    /** a logged-in customer's active cart is tagged from the session; the masked id is ignored */
    public function testTagsTheCustomersActiveCart(): void
    {
        $quote = $this->quote(42);
        $quote->expects(self::once())->method('setData')->with(QuoteAttribute::ATTRIBUTE, self::CONVERSATION);
        $carts = $this->createMock(CartRepositoryInterface::class);
        $carts->method('getActiveForCustomer')->with(42)->willReturn($quote);
        $carts->expects(self::once())->method('save')->with($quote);
        $masked = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $masked->expects(self::never())->method('execute');

        $service = $this->service($this->context(UserContextInterface::USER_TYPE_CUSTOMER, 42), $carts, $masked);
        self::assertTrue($service->set(self::CONVERSATION, 'ignored-masked-id'));
    }

    /** a guest's cart is resolved from the masked quote id */
    public function testTagsTheGuestCartBehindTheMaskedId(): void
    {
        $quote = $this->quote(null);
        $quote->expects(self::once())->method('setData')->with(QuoteAttribute::ATTRIBUTE, self::CONVERSATION);
        $masked = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $masked->method('execute')->with('m4sk3d')->willReturn(77);
        $carts = $this->createMock(CartRepositoryInterface::class);
        $carts->method('get')->with(77)->willReturn($quote);
        $carts->expects(self::once())->method('save');

        $service = $this->service($this->context(UserContextInterface::USER_TYPE_GUEST, null), $carts, $masked);
        self::assertTrue($service->set(self::CONVERSATION, 'm4sk3d'));
    }

    /** idempotent — the same conversation id is not saved twice */
    public function testSkipsTheSaveWhenAlreadyTagged(): void
    {
        $quote = $this->quote(42, self::CONVERSATION);
        $quote->expects(self::never())->method('setData');
        $carts = $this->createMock(CartRepositoryInterface::class);
        $carts->method('getActiveForCustomer')->willReturn($quote);
        $carts->expects(self::never())->method('save');

        $service = $this->service($this->context(UserContextInterface::USER_TYPE_CUSTOMER, 42), $carts, $this->createMock(MaskedQuoteIdToQuoteIdInterface::class));
        self::assertTrue($service->set(self::CONVERSATION));
    }

    /** a masked id never reaches a customer's cart, and a guest without a cart gets 404 */
    public function testRefusesCustomerCartsThroughMaskedIdsAndGuestsWithoutCart(): void
    {
        $masked = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $masked->method('execute')->willReturn(78);
        $carts = $this->createMock(CartRepositoryInterface::class);
        $carts->method('get')->willReturn($this->quote(5));
        $carts->expects(self::never())->method('save');
        $service = $this->service($this->context(UserContextInterface::USER_TYPE_GUEST, null), $carts, $masked);

        try {
            $service->set(self::CONVERSATION, 'someone-elses');
            self::fail('expected NoSuchEntityException');
        } catch (NoSuchEntityException) {
            self::assertTrue(true);
        }
        $this->expectException(NoSuchEntityException::class);
        $service->set(self::CONVERSATION, null);
    }

    /** Module disabled for the store view: nothing is tagged */
    public function testDoesNothingWhenDisabled(): void
    {
        $carts = $this->createMock(CartRepositoryInterface::class);
        $carts->expects(self::never())->method('save');
        $service = $this->service($this->context(UserContextInterface::USER_TYPE_CUSTOMER, 42), $carts, $this->createMock(MaskedQuoteIdToQuoteIdInterface::class), false);
        $this->expectException(NoSuchEntityException::class);
        $service->set(self::CONVERSATION);
    }

    public function testRejectsANonUuidConversationId(): void
    {
        $service = $this->service(
            $this->context(UserContextInterface::USER_TYPE_CUSTOMER, 42),
            $this->createMock(CartRepositoryInterface::class),
            $this->createMock(MaskedQuoteIdToQuoteIdInterface::class)
        );
        $this->expectException(InputException::class);
        $service->set('<script>', null);
    }
}
