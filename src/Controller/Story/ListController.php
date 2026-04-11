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

namespace Horde\Jonah\Controller\Story;

use Exception;
use Horde;
use Horde\Core\Config\LegacyMergedConfig;
use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Service\UrlGenerator;
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
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Horde_Url;

/**
 * PSR-15 controller for listing stories in a channel.
 *
 * Replaces stories/index.php + Jonah_View_StoryList.
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
        private readonly Horde_Registry $registry,
        private readonly Horde_Prefs $prefs,
        private readonly LoggerInterface $logger,
        private readonly LegacyMergedConfig $config,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $channel_id = $queryParams['channel_id'] ?? null;

        if (empty($channel_id)) {
            $this->notification->push(_("No channel requested."), 'horde.error');
            return $this->redirect($this->urlGenerator->absoluteUrlFor('ChannelList'));
        }

        try {
            $channel = $this->driver->getChannel($channel_id);
        } catch (Exception $e) {
            $this->notification->push(
                sprintf(_("Invalid channel requested. %s"), $e->getMessage()),
                'horde.error',
            );
            return $this->redirect($this->urlGenerator->absoluteUrlFor('ChannelList'));
        }

        if (!$this->permissions->check('channels', Horde_Perms::EDIT, [$channel_id])) {
            $this->notification->push(
                _("You are not authorised for this action."),
                'horde.warning',
            );
            throw new Horde_Exception_AuthenticationFailure();
        }

        /* Check if a URL has been passed. */
        $parsedBody = (array) ($request->getParsedBody() ?? []);
        $signedUrl = $parsedBody['url'] ?? $queryParams['url'] ?? null;
        if ($signedUrl && ($url = Horde::verifySignedUrl($signedUrl))) {
            return $this->redirect((string) new Horde_Url($url));
        }

        try {
            $stories = $this->driver->getStories(['channel_id' => $channel_id]);
        } catch (Exception $e) {
            $this->notification->push(
                sprintf(_("Invalid channel requested. %s"), $e->getMessage()),
                'horde.error',
            );
            return $this->redirect($this->urlGenerator->absoluteUrlFor('ChannelList'));
        }

        if (empty($stories)) {
            $this->notification->push(_("No available stories."), 'horde.warning');
        }

        foreach ($stories as $key => $story) {
            if (!empty($stories[$key]['published'])) {
                $dateFormat = $this->prefs->getValue('date_format') . ', '
                    . ($this->prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p');
                $stories[$key]['published_date'] = (new Horde_Date($stories[$key]['published']))->strftime($dateFormat);
            } else {
                $stories[$key]['published_date'] = '';
            }

            $stories[$key]['pdf_link'] = '';
            $stories[$key]['edit_link'] = '';
            $stories[$key]['delete_link'] = '';
            $stories[$key]['view_link'] = Horde::link(
                $this->driver->getStoryLink($channel, $story),
                $story['description'],
            ) . htmlspecialchars($story['title']) . '</a>';

            $stories[$key]['pdf_link'] = Horde::link(
                $this->urlGenerator->urlFor('StoryPdf', ['id' => $story['id'], 'channel_id' => $channel_id]),
                _("PDF version"),
            ) . Horde_Themes_Image::tag('mime/pdf.png') . '</a>';

            $stories[$key]['edit_link'] = Horde::link(
                $this->urlGenerator->urlFor('StoryEdit', ['id' => $story['id'], 'channel_id' => $channel_id]),
                _("Edit story"),
            ) . Horde_Themes_Image::tag('edit.png') . '</a>';

            if ($this->permissions->check('channels', Horde_Perms::DELETE, [$channel_id])) {
                $stories[$key]['delete_link'] = Horde::link(
                    $this->urlGenerator->urlFor('StoryDelete', ['id' => $story['id'], 'channel_id' => $channel_id]),
                    _("Delete story"),
                ) . Horde_Themes_Image::tag('delete.png') . '</a>';
            }

            if ($this->config->get('comments.allow')
                && $this->registry->hasMethod('forums/numMessages')) {
                try {
                    $stories[$key]['comments'] = $this->registry->call(
                        'forums/numMessages',
                        [$stories[$key]['id'], 'jonah'],
                    );
                } catch (Exception $e) {
                    $this->logger->error('Error fetching comment count: {error}', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $title = $channel['channel_name'];
        $view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/stories']);
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
