<?php

use Horde\Url\Url;

/**
 * Jonah_Driver:: is responsible for storing, searching, sorting and filtering
 * locally generated and managed articles.
 *
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_Driver
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = [];

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Constructs a new Driver storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = [])
    {
        $this->_params = $params;
        $this->_logger = new Psr\Log\NullLogger();
    }

    /**
     * Set the logger instance.
     *
     * @param Psr\Log\LoggerInterface $logger
     */
    public function setLogger(Psr\Log\LoggerInterface $logger): void
    {
        $this->_logger = $logger;
    }

    /**
     * Remove a channel from storage.
     *
     * @param array $info  A channel info array. (@TODO: Look at passing just
     *                     the id?)
     */
    public function deleteChannel($info)
    {
        return $this->_deleteChannel($info['channel_id']);
    }

    /**
     * Get a list of stored channels.
     *
     * @return array  An array of channel hashes.
     * @throws Jonah_Exception
     */
    public function getChannels()
    {
        return $this->_getChannels();
    }

    /**
     * Fetches the requested channel, while actually passing on the request to
     * the backend _getChannel() function to do the real work.
     *
     * @param integer $channel_id  The channel id to fetch.
     *
     * @return array  The channel details as an array
     * @throws InvalidArgumentException
     */
    public function getChannel($channel_id)
    {
        static $channel = [];

        /* We need a non empty channel id. */
        if (empty($channel_id)) {
            throw new InvalidArgumentException(_("Missing channel id."));
        }

        /* Cache the fetching of channels. */
        if (!isset($channel[$channel_id])) {
            $channel[$channel_id] = $this->_getChannel($channel_id);
            if (empty($channel[$channel_id]['channel_link'])) {
                $channel[$channel_id]['channel_official']
                    = Horde::url('delivery/html.php', true, -1)->add('channel_id', $channel_id)->setRaw(false);
            } else {
                $channel[$channel_id]['channel_official'] = str_replace(['%25c', '%c'], ['%c', $channel_id], $channel[$channel_id]['channel_link']);
            }

        }

        return $channel[$channel_id];
    }

    /**
     * Returns the most recent or all stories from a channel.
     *
     * @param integer $criteria    An associative array of attributes on which
     *                             the resulting stories should be filtered.
     *  Examples:
     *      'channel' => (string) Channel slug
     *      'channel_id' => (integer) Channel ID (Either an id or slug is required)
     *      'author' => (string) Story author
     *      'updated-min' => (Horde_Date) Only return stories updated on or
     *          after this date
     *      'updated-max' => (Horde_Date) Only return stories updatedon or
     *          before this date
     *      'published-min' => (Horde_Date) Only return stories published on or
     *          after this date
     *      'published-max' => (Horde_Date) Only return stories published on or
     *          before date
     *      'tags' => (array) Tag names that must match to be included
     *      'keywords' => (array) Strings which must match to be included
     *      'published' => (boolean) Whether to return only published stories:
     *          Possible values:
     *              null          return both
     *              'published'   returns publised
     *              'unpublished' returns unpublished
     *      'startnumber' => (integer) Story number to start at
     *      'limit' => (integer) Max number of stories
     * @param integer $order  How to order the results. A Jonah::ORDER_*
     *                        constant.
     *
     * @return array  The specified number (or less, if there are fewer) of
     *                stories from the given channel.
     * @throws InvalidArgumentException
     */
    public function getStories($criteria, $order = Jonah::ORDER_PUBLISHED)
    {
        // Convert a channel slug into a channel ID if necessary
        if (isset($criteria['channel']) && !isset($criteria['channel_id'])) {
            $criteria['channel_id'] = $this->getIdBySlug($criteria['channel']);
        }

        if (!isset($criteria['channel']) && empty($criteria['channel_id'])) {
            $criteria['channel_id'] = array_map(function ($ar) {
                return $ar['channel_id'];
            }, $this->getChannels());
        }

        // Validate that we have proper Horde_Date objects
        if (isset($criteria['updated-min'])) {
            if (!is_a($criteria['updated-min'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for update start date.');
            }
        }
        if (isset($criteria['updated-max'])) {
            if (!is_a($criteria['updated-max'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for update end date.');
            }
        }
        if (isset($criteria['published-min'])) {
            if (!is_a($criteria['published-min'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for published start date.');
            }
        }
        if (isset($criteria['published-max'])) {
            if (!is_a($criteria['published-max'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for published end date.');
            }
        }

        if (!empty($criteria['tags'])) {
            $criteria['ids'] = $GLOBALS['injector']
                ->getInstance('Jonah_Tagger')
                ->search($criteria['tags'], ['channel_ids' => $criteria['channel_id']]);
            unset($criteria['tags']);
        }

        return $this->_getStories($criteria, $order);
    }

    /**
     * Save the provided story to storage.
     *
     * @param array $info  The story information array. Passed by reference so
     *                     we can add/change the id when saved.
     */
    public function saveStory(&$info)
    {
        $this->_saveStory($info);
    }

    /**
     * Retrieve the requested story from storage.
     *
     * @param integer $story_id    The story id to obtain.
     * @param boolean $read        Increment the read counter?
     *
     * @return array  The story information array
     */
    public function getStory($story_id, $read = false)
    {
        $story = $this->_getStory($story_id, $read);
        $channel = $this->getChannel($story['channel_id']);

        /* Format story link. */
        $story['link'] = $this->getStoryLink($channel, $story);

        /* Format dates. */
        $date_format = $GLOBALS['prefs']->getValue('date_format');
        if (!empty($story['updated'])) {
            $story['updated_date'] = (new Horde_Date($story['updated']))->strftime($date_format);
        } else {
            $story['updated_date'] = '';
        }
        if (!empty($story['published'])) {
            $story['published_date'] = (new Horde_Date($story['published']))->strftime($date_format);
        }

        return $story;
    }

    /**
     * Returns the official link to a story.
     *
     * @param array $channel  A channel hash.
     * @param array $story    A story hash.
     *
     * @return Url  The story link.
     */
    public function getStoryLink($channel, $story)
    {
        if (!empty($story['url']) && empty($story['body'])) {
            $url = $story['url'];
        } elseif ((empty($story['url']) || !empty($story['body']))
            && !empty($channel['channel_story_url'])) {
            $url = $channel['channel_story_url'];
        } else {
            $url = Horde::url('stories/view.php', true, -1)->add(['channel_id' => '%c', 'id' => '%s'])->setRaw(false);
        }

        return new Url(str_replace(
            ['%25c', '%25s', '%c', '%s'],
            ['%c', '%s', $channel['channel_id'], $story['id']],
            $url
        ));
    }

    /**
     */
    public function getChecksum($story)
    {
        return md5(($story['title'] ?? '') . ($story['description'] ?? ''));
    }

    /**
     * Returns the stories of a channel rendered with the specified template.
     *
     * @deprecated Use Horde\Jonah\Service\ChannelRenderer::render() instead.
     *
     * @param integer $channel_id  The news channel to get stories from.
     * @param string  $tpl         The name of the template to use.
     * @param integer $max         The maximum number of stories to get.
     * @param integer $from        The number of the story to start with.
     * @param integer $order       Jonah::ORDER_* constant.
     *
     * @return string  The rendered story listing.
     */
    public function renderChannel($channel_id, $tpl, $max = 10, $from = 0, $order = Jonah::ORDER_PUBLISHED)
    {
        $renderer = $GLOBALS['injector']
            ->getInstance(Horde\Jonah\Service\ChannelRenderer::class);

        /**
         * ARCHITECTURE VIOLATION: Using deprecated Horde::loadConfiguration()
         * @deprecated Use $registry->loadConfigFile() instead
         */
        $templates = Horde::loadConfiguration('templates.php', 'templates', 'jonah');

        return $renderer->render(
            (int) $channel_id,
            $tpl,
            $templates,
            $max,
            (int) $from,
            Horde\Jonah\StoryOrder::from($order),
        );
    }

    /**
     * Return a list of story_ids contained in the specified
     * channel.
     *
     * @param integer $channel_id  The channel_id
     *
     * @return array  An array of story_ids.
     */
    public function getStoryIdsByChannel($channel_id)
    {
        return $this->_getStoryIdsByChannel($channel_id);
    }

    public function listTagInfo($channel_id = null)
    {
        global $injector;

        // All channels
        if (!isset($channel_id)) {
            return $injector
                ->getInstance('Jonah_Tagger')
                ->getCloud(null, null);
        }

        // Limit by channel_id
        $story_ids = $this->_getStoryIdsByChannel($channel_id);
        return $injector
            ->getInstance('Jonah_Tagger')
            ->getTagCountsByObjects($story_ids, Jonah_Tagger::TYPE_STORY);
    }

    public function getIdBySlug($channel)
    {
        return $this->_getIdBySlug($channel);
    }

}
