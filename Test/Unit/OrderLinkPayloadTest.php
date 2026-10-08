<?php
declare(strict_types=1);

namespace Platform\Connector\Test\Unit;

use PHPUnit\Framework\TestCase;
use Platform\Connector\Model\OrderLinkPayload;

class OrderLinkPayloadTest extends TestCase
{
    private const SECRET = 'test-secret-0123456789-0123456789-abcdef';
    private const STORE = '0199a0e8-7b1c-7000-8000-000000000010';
    private const CONVERSATION = '0199a0e8-7b1c-7000-8000-000000000001';

    /** R-PD-03: body carries store key, order ids, total, currency and conversation id; signature is sha256=HMAC(body) */
    public function testBuildsSignedBody(): void
    {
        $signed = (new OrderLinkPayload())->build(self::SECRET, self::STORE, [
            'orderId' => 812, 'incrementId' => '000000812', 'grandTotal' => 49.9, 'currency' => 'gbp',
        ], self::CONVERSATION);
        self::assertSame([
            'storeId' => self::STORE, 'orderId' => 812, 'incrementId' => '000000812', 'grandTotal' => 49.9, 'currency' => 'GBP',
            'conversationId' => self::CONVERSATION,
        ], json_decode($signed['body'], true, 512, JSON_THROW_ON_ERROR));
        self::assertSame('sha256=' . hash_hmac('sha256', $signed['body'], self::SECRET), $signed['signature']);
    }

    /** R-PD-03: the order entity id may be unknown at sales_order_place_after; the increment id identifies the order */
    public function testAllowsMissingEntityId(): void
    {
        $signed = (new OrderLinkPayload())->build(self::SECRET, self::STORE, [
            'orderId' => null, 'incrementId' => '000000813', 'grandTotal' => 5.0, 'currency' => 'EUR',
        ], self::CONVERSATION);
        $body = json_decode($signed['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($body['orderId']);
        self::assertSame('000000813', $body['incrementId']);
    }

    public function testRejectsShortSecrets(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new OrderLinkPayload())->build('short', self::STORE, ['orderId' => 1, 'incrementId' => '1', 'grandTotal' => 1.0, 'currency' => 'GBP'], self::CONVERSATION);
    }

    /** R-PD-03: the platform API origin is derived from the configured loader URL */
    public function testDerivesApiOriginFromLoaderUrl(): void
    {
        self::assertSame('https://api.example.com', OrderLinkPayload::apiOrigin('https://api.example.com/widget/v1/assets/loader.js'));
        self::assertSame('http://localhost:3001', OrderLinkPayload::apiOrigin('http://localhost:3001/widget/v1/assets/loader.js'));
        self::assertNull(OrderLinkPayload::apiOrigin(''));
        self::assertNull(OrderLinkPayload::apiOrigin('not a url'));
    }
}
