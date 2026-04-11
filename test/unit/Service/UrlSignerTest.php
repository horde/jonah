<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\Service;

use Horde\Jonah\Service\UrlSigner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlSigner::class)]
class UrlSignerTest extends TestCase
{
    private UrlSigner $signer;

    protected function setUp(): void
    {
        $this->signer = new UrlSigner('test-secret-key', 30);
    }

    public function testSignAppendsTimestampAndHmac(): void
    {
        $signed = $this->signer->sign('http://example.com/page', 1000000);
        $this->assertStringContainsString('_t=1000000', $signed);
        $this->assertStringContainsString('&_h=', $signed);
    }

    public function testSignUsesQuestionMarkForFirstParam(): void
    {
        $signed = $this->signer->sign('http://example.com/page', 1000000);
        $this->assertStringContainsString('?_t=', $signed);
    }

    public function testSignUsesAmpersandWhenQueryExists(): void
    {
        $signed = $this->signer->sign('http://example.com/page?foo=bar', 1000000);
        $this->assertStringContainsString('&_t=', $signed);
    }

    public function testSignReturnsOriginalUrlWhenSecretKeyEmpty(): void
    {
        $signer = new UrlSigner('', 30);
        $url = 'http://example.com/page';
        $this->assertSame($url, $signer->sign($url));
    }

    public function testSignReturnsEmptyStringForEmptyUrl(): void
    {
        $this->assertSame('', $this->signer->sign(''));
    }

    public function testVerifyReturnsFalseForUnsignedUrl(): void
    {
        $this->assertFalse($this->signer->verify('http://example.com/page'));
    }

    public function testVerifyReturnsFalseForTamperedUrl(): void
    {
        $signed = $this->signer->sign('http://example.com/page', 1000000);
        $tampered = str_replace('example.com', 'evil.com', $signed);
        $this->assertFalse($this->signer->verify($tampered, 1000000));
    }

    public function testSignThenVerifyRoundTrip(): void
    {
        $url = 'http://example.com/page?action=delete';
        $now = 1000000;
        $signed = $this->signer->sign($url, $now);
        $result = $this->signer->verify($signed, $now);
        $this->assertSame($url, $result);
    }

    public function testVerifyReturnsFalseWhenExpired(): void
    {
        $url = 'http://example.com/page';
        $now = 1000000;
        $signed = $this->signer->sign($url, $now);

        // 30 min lifetime = 1800 seconds; verify at now + 1801
        $this->assertFalse($this->signer->verify($signed, $now + 1801));
    }

    public function testVerifySucceedsJustBeforeExpiry(): void
    {
        $url = 'http://example.com/page';
        $now = 1000000;
        $signed = $this->signer->sign($url, $now);

        // Verify at exactly now + 1800 (30 min boundary)
        $result = $this->signer->verify($signed, $now + 1800);
        $this->assertSame($url, $result);
    }

    public function testVerifyReturnsFalseForTamperedHmac(): void
    {
        $signed = $this->signer->sign('http://example.com/page', 1000000);
        // Flip last character of HMAC
        $flipped = substr($signed, 0, -1) . (substr($signed, -1) === 'a' ? 'b' : 'a');
        $this->assertFalse($this->signer->verify($flipped, 1000000));
    }

    public function testDifferentKeysProduceDifferentSignatures(): void
    {
        $signer2 = new UrlSigner('different-key', 30);
        $url = 'http://example.com/page';
        $now = 1000000;

        $signed1 = $this->signer->sign($url, $now);
        $signed2 = $signer2->sign($url, $now);

        $this->assertNotSame($signed1, $signed2);

        // Cross-verification must fail
        $this->assertFalse($this->signer->verify($signed2, $now));
        $this->assertFalse($signer2->verify($signed1, $now));
    }
}
