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
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Controller\Story;

use Exception;
use Horde;
use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Exception_AuthenticationFailure;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
use Horde_Registry;
use Horde_Variables;
use Jonah_Driver;
use Jonah_Form_Story;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 controller for creating and editing stories.
 *
 * Replaces stories/edit.php + Jonah_View_StoryEdit.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class EditController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly PermissionChecker $permissions,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Registry $registry,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vars = Horde_Variables::getDefaultVariables();

        $channel_id = $vars->get('channel_id');

        try {
            $channel = $this->driver->getChannel($channel_id);
        } catch (Exception $e) {
            $this->notification->push(
                sprintf(_("Story editing failed: %s"), $e->getMessage()),
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

        $story_id = $vars->get('id');
        if ($story_id && !$vars->get('formname')) {
            try {
                $story = $this->driver->getStory($story_id);
                $story['tags'] = implode(',', array_values($story['tags'] ?? []));
                $vars = new Horde_Variables($story);
            } catch (Exception $e) {
                $this->notification->push(
                    sprintf(_("Error loading story: %s"), $e->getMessage()),
                    'horde.error',
                );
                return $this->redirect(
                    $this->urlGenerator->urlFor('StoryList', ['channel_id' => $channel_id]),
                );
            }
        }

        $form = new Jonah_Form_Story($vars);
        if ($form->validate($vars)) {
            $info = $form->getInfo($vars);
            $info['author'] = $this->registry->getAuth();
            try {
                $this->driver->saveStory($info);
                $this->notification->push(
                    sprintf(_("The story \"%s\" has been saved."), $info['title']),
                    'horde.success',
                );
                return $this->redirect(
                    $this->urlGenerator->urlFor('StoryList', ['channel_id' => $channel_id]),
                );
            } catch (Exception $e) {
                $this->notification->push(
                    sprintf(_("There was an error saving the story: %s"), $e->getMessage()),
                    'horde.error',
                );
            }
        }

        $html = $this->renderChrome($form->getTitle(), function () use ($form, $vars) {
            $form->renderActive(
                $form->getRenderer(),
                $vars,
                $this->urlGenerator->urlFor('StoryCreate', ['channel_id' => $channel_id]),
                'post',
            );
        });

        return $this->htmlResponse($html);
    }
}
