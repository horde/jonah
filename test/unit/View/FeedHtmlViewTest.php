<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\View;

use Horde\Jonah\View\FeedHtmlView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FeedHtmlView::class)]
class FeedHtmlViewTest extends TestCase
{
    public function testDefaultPropertyValues(): void
    {
        $view = new FeedHtmlView();
        $this->assertSame('', $view->url);
        $this->assertSame('', $view->session);
        $this->assertSame('', $view->channel_id);
        $this->assertSame('', $view->format);
        $this->assertSame([], $view->options);
        $this->assertSame('', $view->channel_name);
        $this->assertSame('', $view->stories);
    }
}
