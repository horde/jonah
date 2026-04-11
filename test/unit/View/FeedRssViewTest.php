<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\View;

use Horde\Jonah\View\FeedRssView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FeedRssView::class)]
class FeedRssViewTest extends TestCase
{
    public function testDefaultPropertyValues(): void
    {
        $view = new FeedRssView();
        $this->assertSame('', $view->jonah);
        $this->assertSame('', $view->xsl);
        $this->assertSame('', $view->channel_name);
        $this->assertSame('', $view->channel_desc);
        $this->assertSame('', $view->channel_updated);
        $this->assertSame('', $view->channel_official);
        $this->assertSame('', $view->channel_rss);
        $this->assertSame('', $view->channel_rss2);
        $this->assertSame([], $view->stories);
    }
}
