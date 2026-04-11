<?php

declare(strict_types=1);

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Controller\Channel;

use Horde;
use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
use Horde_Themes_Image;
use Horde_View;
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * PSR-15 controller for listing channels.
 *
 * Replaces channels/index.php + Jonah_View_ChannelList.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class ListController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly PermissionChecker $permissions,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $channels = $this->driver->getChannels();
        } catch (Exception $e) {
            $this->notification->push(
                sprintf(_("An error occurred fetching channels: %s"), $e->getMessage()),
                'horde.error',
            );
            $channels = false;
        }

        if ($channels) {
            $channels = $this->permissions->check('channels', Horde_Perms::SHOW, $channels);

            foreach ($channels as $key => $channel) {
                $cid = $channel['channel_id'];

                $channels[$key]['edit_link'] = Horde::link(
                    $this->urlGenerator->urlFor('ChannelEdit', ['channel_id' => $cid]),
                    _("Edit channel"),
                ) . Horde_Themes_Image::tag('edit.png') . '</a>';

                $channels[$key]['delete_link'] = Horde::link(
                    $this->urlGenerator->urlFor('ChannelDelete', ['channel_id' => $cid]),
                    _("Delete channel"),
                ) . Horde_Themes_Image::tag('delete.png') . '</a>';

                $channels[$key]['stories_url'] = $this->urlGenerator->urlFor(
                    'StoryList',
                    ['channel_id' => $cid],
                );

                $channels[$key]['addstory_link'] = '';
                $channels[$key]['refresh_link'] = '';

                $channels[$key]['addstory_link'] = Horde::link(
                    $this->urlGenerator->urlFor('StoryCreate', ['channel_id' => $cid]),
                    _("Add story"),
                ) . Horde_Themes_Image::tag('new.png') . '</a>';
            }
        }

        $view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/view']);
        $view->addHelper('Tag');
        $view->channels = $channels;
        $view->search_img = Horde_Themes_Image::tag('search.png');

        $this->pageOutput->addScriptFile('tables.js', 'horde');
        $this->pageOutput->addScriptFile('quickfinder.js', 'horde');

        $html = $this->renderChrome(_("Feeds"), function () use ($view) {
            echo $view->render('channellist');
        });

        return $this->htmlResponse($html);
    }
}
