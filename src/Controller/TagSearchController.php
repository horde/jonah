<?php

declare(strict_types=1);

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Controller;

use Exception;
use Horde\Core\Config\LegacyMergedConfig;
use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\Traits\ResponseTrait;
use Horde\Jonah\View\ViewFactory;
use Horde_Date;
use Horde_Exception_AuthenticationFailure;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
use Horde_Prefs;
use Horde_Registry;
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * PSR-15 controller for tag search results.
 *
 * Replaces stories/results.php + Jonah_View_TagSearchList.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class TagSearchController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly PermissionChecker $permissions,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Registry $registry,
        private readonly Horde_Prefs $prefs,
        private readonly LoggerInterface $logger,
        private readonly LegacyMergedConfig $config,
        private readonly UrlGenerator $urlGenerator,
        private readonly ViewFactory $viewFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $tag = $queryParams['tag'] ?? null;
        $channel_id = $queryParams['channel_id'] ?? null;

        if (empty($tag)) {
            $this->notification->push(_("No tag requested."), 'horde.error');
            return $this->redirect($this->urlGenerator->absoluteUrlFor('ChannelList'));
        }

        /* Get channel(s) to search */
        if ($channel_id === null) {
            $channels = $this->driver->getChannels();
        } else {
            try {
                $channels = [$this->driver->getChannel($channel_id)];
            } catch (Exception $e) {
                $this->notification->push(
                    sprintf(_("Invalid channel requested. %s"), $e->getMessage()),
                    'horde.error',
                );
                return $this->redirect($this->urlGenerator->absoluteUrlFor('ChannelList'));
            }
        }

        /* Search stories across channels */
        $stories = [];
        foreach ($channels as $channel) {
            if (!$this->permissions->check('channels', Horde_Perms::SHOW, [$channel['channel_id']])) {
                $this->notification->push(
                    _("You are not authorised for this action."),
                    'horde.warning',
                );
                throw new Horde_Exception_AuthenticationFailure();
            }

            try {
                $cstories = $this->driver->getStories([
                    'tags' => [$tag],
                    'limit' => 10,
                    'channel_id' => $channel['channel_id'],
                ]);
            } catch (Exception $e) {
                $this->notification->push(
                    sprintf(_("Invalid channel requested. %s"), $e->getMessage()),
                    'horde.error',
                );
                return $this->redirect($this->urlGenerator->absoluteUrlFor('ChannelList'));
            }

            $stories = array_merge($stories, $cstories);
        }

        if (empty($stories)) {
            $this->notification->push(_("No available stories."), 'horde.warning');
        }

        foreach ($stories as $key => $story) {
            $storyChannelId = $story['channel_id'];

            /* Format date */
            if (!empty($stories[$key]['published'])) {
                $pubDate = new Horde_Date($stories[$key]['published']);
                $stories[$key]['published_date'] = $pubDate->format($this->prefs->getValue('date_format'), new \Horde\Date\Formatter\IcuFormatter(), $GLOBALS['language'] ?? 'en_US')
                    . ', ' . $pubDate->format($this->prefs->getValue('twentyFour') ? 'HH:mm' : 'h:mma', new \Horde\Date\Formatter\IcuFormatter(), $GLOBALS['language'] ?? 'en_US');
            } else {
                $stories[$key]['published_date'] = '';
            }

            $stories[$key]['view_url'] = $story['link'] ?? '';
            $stories[$key]['can_edit'] = $this->permissions->check('channels', Horde_Perms::EDIT, [$storyChannelId]);
            $stories[$key]['can_delete'] = $this->permissions->check('channels', Horde_Perms::DELETE, [$storyChannelId]);

            /* Comment count */
            if ($this->config->get('comments.allow')
                && $this->registry->hasMethod('forums/numMessages')) {
                $comments = 0;
                try {
                    $comments = $this->registry->call(
                        'forums/numMessages',
                        [$stories[$key]['id'], 'jonah'],
                    );
                } catch (Exception $e) {
                    $comments = 0;
                }
                $stories[$key]['comments'] = $comments;
            }
        }

        $title = _("Tag Search Results");
        $view = $this->viewFactory->createStoryListView();
        $view->stories = $stories;
        $view->read = true;
        $view->comments = $this->config->get('comments.allow')
            && $this->registry->hasMethod('forums/numMessages');

        $html = $this->renderChrome($title, function () use ($view) {
            echo $view->render('index');
        });

        return $this->htmlResponse($html);
    }
}
