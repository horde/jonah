<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit\Service;

use Horde\Jonah\Service\FilesystemPathHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FilesystemPathHelper::class)]
class FilesystemPathHelperTest extends TestCase
{
    private FilesystemPathHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new FilesystemPathHelper(
            '/srv/www/jonah',
            '/horde/js',
            '/srv/www/horde/js',
            '/horde/themes',
            '/jonah/themes',
        );
    }

    public function testGetTemplatePathReturnsBaseWhenNoSubdirectory(): void
    {
        $this->assertSame('/srv/www/jonah/templates', $this->helper->getTemplatePath());
    }

    public function testGetTemplatePathAppendsSubdirectory(): void
    {
        $this->assertSame(
            '/srv/www/jonah/templates/stories',
            $this->helper->getTemplatePath('stories'),
        );
    }

    public function testGetTemplatePathHandlesNestedSubdirectory(): void
    {
        $this->assertSame(
            '/srv/www/jonah/templates/stories/partial',
            $this->helper->getTemplatePath('stories/partial'),
        );
    }

    public function testGetTemplatePathsReturnsMultiplePaths(): void
    {
        $paths = $this->helper->getTemplatePaths('stories', 'stories/partial', 'stories/layout');
        $this->assertSame([
            '/srv/www/jonah/templates/stories',
            '/srv/www/jonah/templates/stories/partial',
            '/srv/www/jonah/templates/stories/layout',
        ], $paths);
    }

    public function testGetTemplatePathsReturnsEmptyForNoArgs(): void
    {
        $this->assertSame([], $this->helper->getTemplatePaths());
    }

    public function testGetHordeJsUri(): void
    {
        $this->assertSame('/horde/js', $this->helper->getHordeJsUri());
    }

    public function testGetHordeJsFs(): void
    {
        $this->assertSame('/srv/www/horde/js', $this->helper->getHordeJsFs());
    }

    public function testGetHordeThemesUri(): void
    {
        $this->assertSame('/horde/themes', $this->helper->getHordeThemesUri());
    }

    public function testGetJonahThemesUri(): void
    {
        $this->assertSame('/jonah/themes', $this->helper->getJonahThemesUri());
    }
}
