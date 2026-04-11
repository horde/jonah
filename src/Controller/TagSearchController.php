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
use Horde;
use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Date;
use Horde_Exception_AuthenticationFailure;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
use Horde_Prefs;
use Horde_Registry;
use Horde_Themes_Image;
use Horde_View;
use Jonah;
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
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $tag = $queryParams['tag'] ?? null;
        $channel_id = $queryParams['channel_id'] ?? null;

        if (empty($tag)) {
            $this->notification->push(_("No tag requested."), 'horde.error');
            return $this->redirect((string) Horde::url('channels/index.php', true));
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
                return $this->redirect((string) Horde::url('channels/index.php', true));
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
                return $this->redirect((string) Horde::url('channels/index.php', true));
            }

            $stories = array_merge($stories, $cstories);
        }

        if (empty($stories)) {
            $this->notification->push(_("No available stories."), 'horde.warning');
        }

        $conf = $GLOBALS['conf'];

        foreach ($stories as $key => $story) {
            $storyChannelId = $story['channel_id'];

            /* Format date */
            if (!empty($stories[$key]['published'])) {
                $dateFormat = $this->prefs->getValue('date_format') . ', '
                    . ($this->prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p');
                $stories[$key]['published_date'] = (new Horde_Date($stories[$key]['published']))
                    ->strftime($dateFormat);
            } else {
                $stories[$key]['published_date'] = '';
            }

            /* Default links */
            $stories[$key]['pdf_link'] = '';
            $stories[$key]['edit_link'] = '';
            $stories[$key]['delete_link'] = '';
            $stories[$key]['view_link'] = Horde::url($story['link'])
                ->link(['title' => $story['description']])
                . htmlspecialchars($story['title']) . '</a>';

            /* PDF link */
            $url = Horde::url('stories/pdf.php')->add([
                'id' => $story['id'],
                'channel_id' => $storyChannelId,
            ]);
            $stories[$key]['pdf_link'] = $url->link(['title' => _("PDF version")])
                . Horde_Themes_Image::tag('mime/pdf.png') . '</a>';

            /* Edit link */
            if ($this->permissions->check('channels', Horde_Perms::EDIT, [$storyChannelId])) {
                $url = Horde::url('stories/edit.php')->add([
                    'id' => $story['id'],
                    'channel_id' => $storyChannelId,
                ]);
                $stories[$key]['edit_link'] = $url->link(['title' => _("Edit story")])
                    . Horde_Themes_Image::tag('edit.png') . '</a>';
            }

            /* Delete link */
            if ($this->permissions->check('channels', Horde_Perms::DELETE, [$storyChannelId])) {
                $url = Horde::url('stories/delete.php')->add([
                    'id' => $story['id'],
                    'channel_id' => $storyChannelId,
                ]);
                $stories[$key]['delete_link'] = $url->link(['title' => _("Delete story")])
                    . Horde_Themes_Image::tag('delete.png') . '</a>';
            }

            /* Comment count */
            if (!empty($conf['comments']['allow'])
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
        $view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/stories']);
        $view->stories = $stories;
        $view->read = true;
        $view->comments = !empty($conf['comments']['allow'])
            && $this->registry->hasMethod('forums/numMessages');

        $html = $this->renderChrome($title, function () use ($view) {
            echo $view->render('index');
        });

        return $this->htmlResponse($html);
    }
}
