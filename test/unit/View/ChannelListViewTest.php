<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\View;

use Horde\Jonah\View\ChannelListView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChannelListView::class)]
class ChannelListViewTest extends TestCase
{
    public function testDefaultChannelsIsFalse(): void
    {
        $view = new ChannelListView();
        $this->assertFalse($view->channels);
    }

    public function testChannelsAcceptsArray(): void
    {
        $view = new ChannelListView();
        $view->channels = [['channel_id' => '1', 'channel_name' => 'News']];
        $this->assertIsArray($view->channels);
        $this->assertCount(1, $view->channels);
    }
}
