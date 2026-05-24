<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Integration\View;

use Horde\Jonah\Service\FilesystemPathHelper;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\Test\Stub\GroupMapperRoutesProvider;
use Horde\Jonah\View\ChannelListView;
use Horde\Jonah\View\FeedHtmlView;
use Horde\Jonah\View\FeedRssView;
use Horde\Jonah\View\StoryListView;
use Horde\Jonah\View\StoryView;
use Horde\Jonah\View\ViewFactory;
use Horde\Routes\GroupMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViewFactory::class)]
class ViewFactoryTest extends TestCase
{
    private ViewFactory $factory;

    protected function setUp(): void
    {
        $mapper = new GroupMapper();
        $mapper->buildRoute(uri: '/jonah/channels', name: 'ChannelList')->add();
        $mapper->compile();

        $provider = new GroupMapperRoutesProvider($mapper);
        $urlGenerator = new UrlGenerator($provider, '/jonah');

        $paths = new FilesystemPathHelper(
            '/srv/www/jonah',
            '/horde/js',
            '/srv/www/horde/js',
            '/horde/themes',
            '/jonah/themes',
        );

        $this->factory = new ViewFactory($urlGenerator, $paths);
    }

    public function testCreateStoryListViewReturnsTypedView(): void
    {
        $view = $this->factory->createStoryListView();
        $this->assertInstanceOf(StoryListView::class, $view);
    }

    public function testCreateStoryListViewHasJonahHelpers(): void
    {
        $view = $this->factory->createStoryListView();
        $url = $view->jonahUrl('ChannelList');
        $this->assertSame('/jonah/channels', $url);
    }

    public function testCreateStoryListViewHasLinkHelper(): void
    {
        $view = $this->factory->createStoryListView();
        $html = $view->jonahLink('/test', 'Click');
        $this->assertSame('<a href="/test">Click</a>', $html);
    }

    public function testCreateStoryListViewHasImageHelper(): void
    {
        $view = $this->factory->createStoryListView();
        $html = $view->jonahImage('edit.png', 'Edit');
        $this->assertStringContainsString('src="/horde/themes/edit.png"', $html);
    }

    public function testCreateStoryViewReturnsTypedView(): void
    {
        $view = $this->factory->createStoryView();
        $this->assertInstanceOf(StoryView::class, $view);
    }

    public function testCreateChannelListViewReturnsTypedView(): void
    {
        $view = $this->factory->createChannelListView();
        $this->assertInstanceOf(ChannelListView::class, $view);
    }

    public function testCreateFeedHtmlViewReturnsTypedView(): void
    {
        $view = $this->factory->createFeedHtmlView();
        $this->assertInstanceOf(FeedHtmlView::class, $view);
    }

    public function testCreateFeedRssViewReturnsTypedView(): void
    {
        $view = $this->factory->createFeedRssView();
        $this->assertInstanceOf(FeedRssView::class, $view);
    }

    public function testStoryListViewHasDefaultProperties(): void
    {
        $view = $this->factory->createStoryListView();
        $this->assertSame([], $view->stories);
        $this->assertFalse($view->read);
        $this->assertFalse($view->comments);
    }

    public function testStoryViewHasDefaultProperties(): void
    {
        $view = $this->factory->createStoryView();
        $this->assertSame([], $view->story);
        $this->assertSame('', $view->tagcloud);
        $this->assertNull($view->shareUrl);
        $this->assertNull($view->comments);
    }

    public function testFeedRssViewHasDefaultProperties(): void
    {
        $view = $this->factory->createFeedRssView();
        $this->assertSame('', $view->jonah);
        $this->assertSame('', $view->xsl);
        $this->assertSame([], $view->stories);
    }
}
