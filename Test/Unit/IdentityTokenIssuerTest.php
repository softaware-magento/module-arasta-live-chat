<?php
declare(strict_types=1);

namespace Softaware\ArastaLiveChat\Test\Unit;

use PHPUnit\Framework\TestCase;
use Softaware\ArastaLiveChat\Model\IdentityTokenIssuer;

class IdentityTokenIssuerTest extends TestCase
{
    private const SECRET = 'test-secret-0123456789-0123456789-abcdef';

    private function decode(string $part): array
    {
        return json_decode(base64_decode(strtr($part, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
    }

    /** HS256 token with sub, email, iat and exp */
    public function testIssuesHs256TokenWithSpecClaims(): void
    {
        $token = (new IdentityTokenIssuer())->issue(self::SECRET, 42, 'alice@example.com', 1_790_000_000, 3600);
        [$header, $claims, $signature] = explode('.', $token);
        self::assertSame(['alg' => 'HS256', 'typ' => 'JWT'], $this->decode($header));
        self::assertSame(['sub' => '42', 'email' => 'alice@example.com', 'iat' => 1_790_000_000, 'exp' => 1_790_003_600], $this->decode($claims));
        $expected = IdentityTokenIssuer::base64Url(hash_hmac('sha256', "$header.$claims", self::SECRET, true));
        self::assertSame($expected, $signature);
    }

    /** exp is capped at one hour */
    public function testCapsLifetimeAtOneHour(): void
    {
        $token = (new IdentityTokenIssuer())->issue(self::SECRET, 1, 'a@b.c', 100, 86_400);
        self::assertSame(100 + 3600, $this->decode(explode('.', $token)[1])['exp']);
    }

    public function testRejectsShortSecrets(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new IdentityTokenIssuer())->issue('short', 1, 'a@b.c', 100);
    }
}
