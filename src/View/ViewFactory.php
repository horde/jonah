<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\View;

use Horde\Core\Assets\ResponsiveAssets;
use Horde\Jonah\Service\FilesystemPathHelper;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\View\Helper\JonahImage;
use Horde\Jonah\View\Helper\JonahLink;
use Horde\Jonah\View\Helper\JonahUrl;

/**
 * Factory for creating typed views with pre-registered helpers.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class ViewFactory
{
    public function __construct(
        private readonly UrlGenerator $urlGenerator,
        private readonly FilesystemPathHelper $paths,
        private readonly ResponsiveAssets $assets,
    ) {}

    public function createStoryListView(): StoryListView
    {
        $view = new StoryListView([
            'templatePath' => $this->paths->getTemplatePath('stories'),
        ]);
        $this->registerHelpers($view);

        return $view;
    }

    public function createStoryView(): StoryView
    {
        $view = new StoryView([
            'templatePath' => $this->paths->getTemplatePaths(
                'stories',
                'stories/partial',
                'stories/layout',
            ),
        ]);
        $view->addHelper('Tag');
        $view->addHelper('Text');
        $this->registerHelpers($view);

        return $view;
    }

    public function createChannelListView(): ChannelListView
    {
        $view = new ChannelListView([
            'templatePath' => $this->paths->getTemplatePath('view'),
        ]);
        $view->addHelper('Tag');
        $this->registerHelpers($view);

        return $view;
    }

    public function createFeedHtmlView(): FeedHtmlView
    {
        $view = new FeedHtmlView([
            'templatePath' => $this->paths->getTemplatePath('delivery'),
        ]);
        $this->registerHelpers($view);

        return $view;
    }

    public function createFeedRssView(): FeedRssView
    {
        $view = new FeedRssView([
            'templatePath' => $this->paths->getTemplatePath('delivery'),
        ]);

        return $view;
    }

    /**
     * Register the Jonah-specific helpers on a view.
     */
    private function registerHelpers($view): void
    {
        new JonahUrl($view, $this->urlGenerator);
        new JonahLink($view);
        new JonahImage($view, $this->assets);
    }
}
