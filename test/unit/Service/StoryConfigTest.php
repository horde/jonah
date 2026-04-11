<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\Service;

use Horde\Jonah\Service\StoryConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StoryConfig::class)]
class StoryConfigTest extends TestCase
{
    public function testGetBodyTypesReturnsTextWhenConfigured(): void
    {
        $config = new StoryConfig(['text']);
        $types = $config->getBodyTypes();
        $this->assertArrayHasKey('text', $types);
        $this->assertSame('Text', $types['text']);
    }

    public function testGetBodyTypesReturnsRichtextWhenConfigured(): void
    {
        $config = new StoryConfig(['richtext']);
        $types = $config->getBodyTypes();
        $this->assertArrayHasKey('richtext', $types);
        $this->assertSame('Rich Text', $types['richtext']);
    }

    public function testGetBodyTypesReturnsBothWhenBothConfigured(): void
    {
        $config = new StoryConfig(['richtext', 'text']);
        $types = $config->getBodyTypes();
        $this->assertArrayHasKey('richtext', $types);
        $this->assertArrayHasKey('text', $types);
    }

    public function testGetBodyTypesDefaultsToTextWhenEmpty(): void
    {
        $config = new StoryConfig([]);
        $types = $config->getBodyTypes();
        $this->assertArrayHasKey('text', $types);
        $this->assertCount(1, $types);
    }

    public function testGetBodyTypesIsCached(): void
    {
        $config = new StoryConfig(['text']);
        $first = $config->getBodyTypes();
        $second = $config->getBodyTypes();
        $this->assertSame($first, $second);
    }

    public function testGetDefaultBodyTypeReturnsTextWhenAvailable(): void
    {
        $config = new StoryConfig(['text', 'richtext']);
        $this->assertSame('text', $config->getDefaultBodyType());
    }

    public function testGetDefaultBodyTypeReturnsRichtextWhenNoText(): void
    {
        $config = new StoryConfig(['richtext']);
        $this->assertSame('richtext', $config->getDefaultBodyType());
    }

    public function testGetDefaultBodyTypeReturnsTextForEmptyConfig(): void
    {
        $config = new StoryConfig([]);
        $this->assertSame('text', $config->getDefaultBodyType());
    }
}
