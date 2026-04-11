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

use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\Traits\ResponseTrait;
use Horde\Jonah\View\ViewFactory;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
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
        private readonly ViewFactory $viewFactory,
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
                $channels[$key]['stories_url'] = $this->urlGenerator->urlFor(
                    'StoryList',
                    ['channel_id' => $cid],
                );
                $channels[$key]['can_edit'] = true;
                $channels[$key]['can_delete'] = true;
            }
        }

        $view = $this->viewFactory->createChannelListView();
        $view->channels = $channels;

        $this->pageOutput->addScriptFile('tables.js', 'horde');
        $this->pageOutput->addScriptFile('quickfinder.js', 'horde');

        $html = $this->renderChrome(_("Feeds"), function () use ($view) {
            echo $view->render('channellist');
        });

        return $this->htmlResponse($html);
    }
}
