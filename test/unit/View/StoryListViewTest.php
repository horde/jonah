<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\View;

use Horde\Jonah\View\StoryListView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StoryListView::class)]
class StoryListViewTest extends TestCase
{
    public function testDefaultPropertyValues(): void
    {
        $view = new StoryListView();
        $this->assertSame([], $view->stories);
        $this->assertFalse($view->read);
        $this->assertFalse($view->comments);
    }
}
