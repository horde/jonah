<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit;

use Horde\Jonah\StoryOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StoryOrder::class)]
class StoryOrderTest extends TestCase
{
    public function testPublishedHasValueZero(): void
    {
        $this->assertSame(0, StoryOrder::Published->value);
    }

    public function testReadHasValueOne(): void
    {
        $this->assertSame(1, StoryOrder::Read->value);
    }

    public function testCommentsHasValueTwo(): void
    {
        $this->assertSame(2, StoryOrder::Comments->value);
    }

    public function testFromValidValue(): void
    {
        $this->assertSame(StoryOrder::Read, StoryOrder::from(1));
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(StoryOrder::tryFrom(99));
    }

    public function testCasesReturnsAllThree(): void
    {
        $this->assertCount(3, StoryOrder::cases());
    }
}
