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
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Exception_AuthenticationFailure;
use Horde_Form;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
use Horde_Variables;
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 controller for deleting stories.
 *
 * Replaces stories/delete.php + Jonah_View_StoryDelete.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class DeleteController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly PermissionChecker $permissions,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vars = Horde_Variables::getDefaultVariables();

        $form_submit = $vars->get('submitbutton');
        $channel_id = $vars->get('channel_id');
        $story_id = $vars->get('id');

        try {
            $channel = $this->driver->getChannel($channel_id);
        } catch (Exception $e) {
            $this->notification->push(
                sprintf(_("Story editing failed: %s"), $e->getMessage()),
                'horde.error',
            );
            return $this->redirect((string) Horde::url('channels/index.php', true));
        }

        if (!$this->permissions->check('channels', Horde_Perms::DELETE, [$channel_id])) {
            $this->notification->push(
                _("You are not authorised for this action."),
                'horde.warning',
            );
            throw new Horde_Exception_AuthenticationFailure();
        }

        try {
            $story = $this->driver->getStory($story_id);
        } catch (Exception $e) {
            $this->notification->push(
                _("No valid story requested for deletion."),
                'horde.message',
            );
            return $this->redirect((string) Horde::url('channels/index.php', true));
        }

        if (empty($form_submit)) {
            $vars = new Horde_Variables($story);
        }

        $title = sprintf(_("Delete News Story \"%s\"?"), $vars->get('title'));

        $form = new Horde_Form($vars, $title);
        $form->setButtons([_("Delete"), _("Do not delete")]);
        $form->addHidden('', 'channel_id', 'int', true, true);
        $form->addHidden('', 'id', 'int', true, true);
        $form->addVariable(
            _("Really delete this News Story?"),
            'confirm',
            'description',
            false,
        );

        if ($form_submit === _("Delete")) {
            if ($form->validate($vars)) {
                $info = $form->getInfo($vars);
                try {
                    $this->driver->deleteStory($info['channel_id'], $info['id']);
                    $this->notification->push(
                        _("The story has been deleted."),
                        'horde.success',
                    );
                    return $this->redirect(
                        (string) Horde::url('stories/index.php', true)
                            ->add('channel_id', $channel_id)
                            ->setRaw(true),
                    );
                } catch (Exception $e) {
                    $this->notification->push(
                        sprintf(
                            _("There was an error deleting the story: %s"),
                            $e->getMessage(),
                        ),
                        'horde.error',
                    );
                }
            }
        } elseif (!empty($form_submit)) {
            $this->notification->push(
                _("Story has not been deleted."),
                'horde.message',
            );
            return $this->redirect(
                (string) Horde::url('stories/index.php', true)
                    ->add('channel_id', $channel_id)
                    ->setRaw(true),
            );
        }

        $html = $this->renderChrome($title, function () use ($form, $vars) {
            $form->renderActive(null, $vars, Horde::url('stories/delete.php'), 'post');
        });

        return $this->htmlResponse($html);
    }
}
