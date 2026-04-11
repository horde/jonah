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
use Psr\Log\LoggerInterface;
use Exception;

/**
 * PSR-15 controller for deleting channels.
 *
 * Replaces channels/delete.php + Jonah_View_ChannelDelete.
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
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vars = Horde_Variables::getDefaultVariables();

        $form_submit = $vars->get('submitbutton');
        $channel_id = $vars->get('channel_id');

        try {
            $channel = $this->driver->getChannel($channel_id);
        } catch (Exception $e) {
            $this->logger->error('Invalid channel for deletion: {error}', [
                'error' => $e->getMessage(),
            ]);
            $this->notification->push(
                _("Invalid channel specified for deletion."),
                'horde.message',
            );
            return $this->redirect((string) Horde::url('channels'));
        }

        /* If not yet submitted, populate form vars from fetched channel. */
        if (empty($form_submit)) {
            $vars = new Horde_Variables($channel);
        }

        /* Check permissions. */
        if (!$this->permissions->check('channels', Horde_Perms::DELETE, [$channel_id])) {
            $this->notification->push(
                _("You are not authorised for this action."),
                'horde.warning',
            );
            throw new Horde_Exception_AuthenticationFailure();
        }

        $title = sprintf(_("Delete News Channel \"%s\"?"), $vars->get('channel_name'));
        $form = new Horde_Form($vars, $title);
        $form->setButtons([_("Delete"), _("Do not delete")]);
        $form->addHidden('', 'channel_id', 'int', true, true);
        $form->addVariable(
            _("Really delete this News Channel? All stories created in this channel will be lost!"),
            'confirm',
            'description',
            false,
        );

        if ($form_submit === _("Delete")) {
            if ($form->validate($vars)) {
                $info = $form->getInfo($vars);
                try {
                    $this->driver->deleteChannel($info);
                    $this->notification->push(
                        _("The channel has been deleted."),
                        'horde.success',
                    );
                    return $this->redirect((string) Horde::url('channels'));
                } catch (Exception $e) {
                    $this->notification->push(
                        sprintf(
                            _("There was an error deleting the channel: %s"),
                            $e->getMessage(),
                        ),
                        'horde.error',
                    );
                }
            }
        } elseif (!empty($form_submit)) {
            $this->notification->push(
                _("Channel has not been deleted."),
                'horde.message',
            );
            return $this->redirect((string) Horde::url('channels'));
        }

        $html = $this->renderChrome($title, function () use ($form, $vars) {
            $form->renderActive(null, $vars, Horde::selfUrl(), 'post');
        });

        return $this->htmlResponse($html);
    }
}
