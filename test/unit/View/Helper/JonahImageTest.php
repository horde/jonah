<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\View\Helper;

use Horde\Jonah\Service\FilesystemPathHelper;
use Horde\Jonah\View\Helper\JonahImage;
use Horde_View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JonahImage::class)]
class JonahImageTest extends TestCase
{
    private Horde_View $view;
    private JonahImage $helper;

    protected function setUp(): void
    {
        $paths = new FilesystemPathHelper(
            '/srv/www/jonah',
            '/horde/js',
            '/srv/www/horde/js',
            '/horde/themes',
            '/jonah/themes',
        );
        $this->view = new Horde_View();
        $this->helper = new JonahImage($this->view, $paths);
    }

    public function testJonahImageReturnsImgTag(): void
    {
        $html = $this->helper->jonahImage('edit.png');
        $this->assertStringContainsString('<img ', $html);
        $this->assertStringContainsString('/>', $html);
    }

    public function testJonahImageResolvesAgainstThemesUri(): void
    {
        $html = $this->helper->jonahImage('edit.png');
        $this->assertStringContainsString('src="/horde/themes/edit.png"', $html);
    }

    public function testJonahImageHandlesSubdirectoryInSrc(): void
    {
        $html = $this->helper->jonahImage('mime/pdf.png');
        $this->assertStringContainsString('src="/horde/themes/mime/pdf.png"', $html);
    }

    public function testJonahImageSetsAltAttribute(): void
    {
        $html = $this->helper->jonahImage('edit.png', 'Edit');
        $this->assertStringContainsString('alt="Edit"', $html);
    }

    public function testJonahImageSetsEmptyAltByDefault(): void
    {
        $html = $this->helper->jonahImage('edit.png');
        $this->assertStringContainsString('alt=""', $html);
    }

    public function testJonahImageIncludesExtraAttributes(): void
    {
        $html = $this->helper->jonahImage('edit.png', '', ['width' => '16', 'height' => '16']);
        $this->assertStringContainsString('width="16"', $html);
        $this->assertStringContainsString('height="16"', $html);
    }

    public function testJonahImageEscapesSrc(): void
    {
        $html = $this->helper->jonahImage('img with "quotes".png');
        $this->assertStringContainsString('src="/horde/themes/img with &quot;quotes&quot;.png"', $html);
    }

    public function testJonahImageIsAccessibleFromView(): void
    {
        $html = $this->view->jonahImage('edit.png', 'Edit');
        $this->assertStringContainsString('src="/horde/themes/edit.png"', $html);
        $this->assertStringContainsString('alt="Edit"', $html);
    }

    public function testJonahImageHandlesTrailingSlashInThemesUri(): void
    {
        $paths = new FilesystemPathHelper(
            '/srv/www/jonah',
            '/horde/js',
            '/srv/www/horde/js',
            '/horde/themes/',
            '/jonah/themes',
        );
        $view = new Horde_View();
        new JonahImage($view, $paths);

        $html = $view->jonahImage('edit.png');
        $this->assertStringContainsString('src="/horde/themes/edit.png"', $html);
        $this->assertStringNotContainsString('//', $html);
    }
}
