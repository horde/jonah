<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\View\Helper;

use Horde\Jonah\Service\FilesystemPathHelper;
use Horde\Jonah\View\Helper\JonahImage;
use Horde\Jonah\View\Helper\JonahLink;
use Horde_View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JonahLink::class)]
class JonahLinkTest extends TestCase
{
    private Horde_View $view;
    private JonahLink $helper;

    protected function setUp(): void
    {
        $this->view = new Horde_View();
        $this->helper = new JonahLink($this->view);
    }

    public function testJonahLinkReturnsCompleteAnchorElement(): void
    {
        $html = $this->helper->jonahLink('/path', 'Click me');
        $this->assertSame('<a href="/path">Click me</a>', $html);
    }

    public function testJonahLinkEscapesUrl(): void
    {
        $html = $this->helper->jonahLink('/path?a=1&b=2', 'Link');
        $this->assertStringContainsString('href="/path?a=1&amp;b=2"', $html);
    }

    public function testJonahLinkIncludesTitle(): void
    {
        $html = $this->helper->jonahLink('/path', 'Link', 'My title');
        $this->assertStringContainsString('title="My title"', $html);
    }

    public function testJonahLinkOmitsTitleWhenEmpty(): void
    {
        $html = $this->helper->jonahLink('/path', 'Link');
        $this->assertStringNotContainsString('title=', $html);
    }

    public function testJonahLinkIncludesExtraAttributes(): void
    {
        $html = $this->helper->jonahLink('/path', 'Link', '', ['class' => 'btn', 'id' => 'my-link']);
        $this->assertStringContainsString('class="btn"', $html);
        $this->assertStringContainsString('id="my-link"', $html);
    }

    public function testJonahLinkEscapesAttributeValues(): void
    {
        $html = $this->helper->jonahLink('/path', 'Link', 'Title "quoted"');
        $this->assertStringContainsString('title="Title &quot;quoted&quot;"', $html);
    }

    public function testJonahLinkDoesNotEscapeContent(): void
    {
        $html = $this->helper->jonahLink('/path', '<img src="x" />');
        $this->assertStringContainsString('><img src="x" /></a>', $html);
    }

    public function testJonahLinkIsAccessibleFromView(): void
    {
        $html = $this->view->jonahLink('/test', 'Hello');
        $this->assertSame('<a href="/test">Hello</a>', $html);
    }

    public function testJonahIconLinkReturnsAnchorWrappingImage(): void
    {
        $paths = new FilesystemPathHelper(
            '/srv/www/jonah',
            '/horde/js',
            '/srv/www/horde/js',
            '/horde/themes',
            '/jonah/themes',
        );
        new JonahImage($this->view, $paths);

        $html = $this->helper->jonahIconLink('/edit', 'edit.png', 'Edit');
        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('<img ', $html);
        $this->assertStringContainsString('</a>', $html);
    }

    public function testJonahIconLinkPassesTitleAsAlt(): void
    {
        $paths = new FilesystemPathHelper(
            '/srv/www/jonah',
            '/horde/js',
            '/srv/www/horde/js',
            '/horde/themes',
            '/jonah/themes',
        );
        new JonahImage($this->view, $paths);

        $html = $this->helper->jonahIconLink('/edit', 'edit.png', 'Edit story');
        $this->assertStringContainsString('title="Edit story"', $html);
        $this->assertStringContainsString('alt="Edit story"', $html);
    }
}
