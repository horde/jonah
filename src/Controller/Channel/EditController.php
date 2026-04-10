<?php

declare(strict_types=1);

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Controller\Channel;

use Horde;
use Horde\Jonah\Service\PermissionChecker;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Exception_AuthenticationFailure;
use Horde_Form_Renderer;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Perms;
use Horde_Variables;
use Jonah_Driver;
use Jonah_Form_Feed;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * PSR-15 controller for creating and editing channels.
 *
 * Replaces channels/edit.php + Jonah_View_ChannelEdit.
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
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vars = Horde_Variables::getDefaultVariables();
        $form = new Jonah_Form_Feed($vars);

        $formname = $vars->get('formname');
        $channel_id = $vars->get('channel_id');

        /* Form not yet submitted and is being edited. */
        if (!$formname && $channel_id) {
            try {
                $vars = new Horde_Variables($this->driver->getChannel($channel_id));
            } catch (Exception $e) {
                $this->notification->push(
                    sprintf(_("Invalid channel requested. %s"), $e->getMessage()),
                    'horde.error',
                );
                return $this->redirect((string) Horde::url('channels/index.php', true));
            }
        }

        $channel_type = $vars->get('channel_type');

        /* Check permissions and deny if not allowed. */
        if (!$this->permissions->check('channels', Horde_Perms::EDIT, [$channel_id])) {
            $this->notification->push(
                _("You are not authorised for this action."),
                'horde.warning',
            );
            throw new Horde_Exception_AuthenticationFailure();
        }

        $form->setExtraFields($channel_id);
        if ($formname) {
            if ($form->validate($vars)) {
                $info = $form->getInfo($vars);
                try {
                    $this->driver->saveChannel($info);
                    $this->notification->push(
                        sprintf(_("The feed \"%s\" has been saved."), $info['channel_name']),
                        'horde.success',
                    );
                    return $this->redirect((string) Horde::url('channels'));
                } catch (Exception $e) {
                    $this->notification->push(
                        sprintf(_("There was an error saving the feed: %s"), $e->getMessage()),
                        'horde.error',
                    );
                }
            }
        }

        $html = $this->renderChrome($form->getTitle(), function () use ($form, $vars) {
            $form->renderActive(
                new Horde_Form_Renderer(),
                $vars,
                Horde::url('channels/edit.php'),
                'post',
            );
        });

        return $this->htmlResponse($html);
    }
}
