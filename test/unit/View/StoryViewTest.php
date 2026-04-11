<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\View;

use Horde\Jonah\View\StoryView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StoryView::class)]
class StoryViewTest extends TestCase
{
    public function testDefaultPropertyValues(): void
    {
        $view = new StoryView();
        $this->assertSame([], $view->story);
        $this->assertSame('', $view->tagcloud);
        $this->assertNull($view->shareUrl);
        $this->assertNull($view->comments);
    }
}
