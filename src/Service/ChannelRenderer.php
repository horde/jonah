<?php

declare(strict_types=1);

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Service;

use Horde\Jonah\StoryOrder;
use Horde_Themes_Image;
use Horde_View;
use Jonah_Driver;
use Psr\Log\LoggerInterface;

/**
 * Renders channel story listings using templates.
 *
 * Extracted from Jonah_Driver::renderChannel(), _escapeStories(),
 * and _escapeStoryDescriptions().
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class ChannelRenderer
{
    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly LoggerInterface $logger,
        private readonly FilesystemPathHelper $paths,
    ) {}

    /**
     * Render a channel's stories using the specified template.
     *
     * @param int         $channelId  The channel to render.
     * @param string      $tpl        Template key from templates.php config.
     * @param array       $templates  The loaded templates configuration.
     * @param int|null    $max        Max stories (null for all).
     * @param int         $from       Story offset for pagination.
     * @param StoryOrder  $order      Sort order.
     *
     * @return string  Rendered HTML.
     */
    public function render(
        int $channelId,
        string $tpl,
        array $templates,
        ?int $max = 10,
        int $from = 0,
        StoryOrder $order = StoryOrder::Published,
    ): string {
        $channel = $this->driver->getChannel($channelId);

        $escape = !isset($templates[$tpl]['escape'])
            || !empty($templates[$tpl]['escape']);

        $view = new Horde_View(['templatePath' => $this->paths->getTemplatePath('channels')]);

        if ($escape) {
            $channel['channel_name'] = htmlspecialchars($channel['channel_name']);
            $channel['channel_desc'] = htmlspecialchars($channel['channel_desc'] ?? '');
        }
        $view->channel = $channel;

        /* Get one story more than requested to see if there are more. */
        if ($max !== null) {
            $stories = $this->driver->getStories(
                [
                    'channel_id' => $channelId,
                    'published' => true,
                    'startnumber' => $from,
                    'limit' => $max,
                ],
                $order->value,
            );
        } else {
            $stories = $this->driver->getStories(
                ['channel_id' => $channelId, 'published' => true],
                $order->value,
            );
            $max = count($stories);
        }

        if (!$stories) {
            $view->error = _("No stories are currently available.");
            $view->stories = false;
            $view->image = false;
            $view->form = false;
        } else {
            if ($escape) {
                array_walk($stories, $this->escapeStory(...));
            }
            array_walk($stories, $this->escapeDescription(...));

            $view->error = false;
            $view->story_marker = Horde_Themes_Image::tag('story_marker.png');
            $view->image = false;
            $view->form = false;

            $view->previous = $from ? max(0, $from - $max) : false;
            $view->previous_link = ($from && !empty($channel['channel_page_link']))
                ? $this->replacePaginationTokens(
                    $channel['channel_page_link'],
                    $channel['channel_id'],
                    max(0, $from - $max),
                )
                : false;

            $more = count($stories) > $max;
            if ($more) {
                $view->next = $from + $max;
                array_pop($stories);
            } else {
                $view->next = false;
            }

            $view->next_link = ($more && !empty($channel['channel_page_link']))
                ? $this->replacePaginationTokens(
                    $channel['channel_page_link'],
                    $channel['channel_id'],
                    $from + $max,
                )
                : false;

            $view->stories = $stories;
        }

        return $view->render($templates[$tpl]['view_template']);
    }

    /**
     * HTML-escape story fields.
     */
    private function escapeStory(array &$value, int|string $key): void
    {
        $value['title'] = htmlspecialchars($value['title'] ?? '');
        $value['description'] = htmlspecialchars($value['description'] ?? '');
        if (isset($value['link'])) {
            $value['link'] = htmlspecialchars($value['link']);
        }
        if (empty($value['body_type']) || $value['body_type'] !== 'richtext') {
            $value['body'] = htmlspecialchars($value['body'] ?? '');
        }
    }

    /**
     * Convert newlines to <br> in story descriptions.
     */
    private function escapeDescription(array &$value, int|string $key): void
    {
        $value['description'] = nl2br($value['description'] ?? '');
    }

    /**
     * Replace pagination tokens in a page link template.
     */
    private function replacePaginationTokens(
        string $link,
        int|string $channelId,
        int $offset,
    ): string {
        return str_replace(
            ['%25c', '%25n', '%c', '%n'],
            ['%c', '%n', (string) $channelId, (string) $offset],
            $link,
        );
    }
}
