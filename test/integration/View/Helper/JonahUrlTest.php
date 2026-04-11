<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Integration\View\Helper;

use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\View\Helper\JonahUrl;
use Horde\Routes\Mapper;
use Horde_View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JonahUrl::class)]
class JonahUrlTest extends TestCase
{
    private Horde_View $view;
    private JonahUrl $helper;

    protected function setUp(): void
    {
        $mapper = new Mapper();
        $mapper->connect('ChannelList', '/channels/', ['controller' => 'channel_list']);
        $mapper->connect('StoryEdit', '/stories/edit/:id', ['controller' => 'story_edit']);

        $urlGenerator = new UrlGenerator($mapper, '/jonah');

        $this->view = new Horde_View();
        $this->helper = new JonahUrl($this->view, $urlGenerator);
    }

    public function testJonahUrlGeneratesRelativeUrl(): void
    {
        $url = $this->helper->jonahUrl('ChannelList');
        $this->assertSame('/jonah/channels', $url);
    }

    public function testJonahUrlPassesParameters(): void
    {
        $url = $this->helper->jonahUrl('StoryEdit', ['id' => '42']);
        $this->assertSame('/jonah/stories/edit/42', $url);
    }

    public function testJonahUrlIsAccessibleFromView(): void
    {
        $url = $this->view->jonahUrl('ChannelList');
        $this->assertSame('/jonah/channels', $url);
    }
}
