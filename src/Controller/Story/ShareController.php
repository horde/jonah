<?php

declare(strict_types=1);

/**
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Controller\Story;

use Exception;
use Horde;
use Horde\Jonah\Service\StoryMailer;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Form;
use Horde_Mime_Part;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Registry;
use Horde_Variables;
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 controller for sharing stories via email.
 *
 * Replaces stories/share.php.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class ShareController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly StoryMailer $mailer,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Registry $registry,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vars = Horde_Variables::getDefaultVariables();
        $channel_id = $vars->get('channel_id');
        $story_id = $vars->get('id');

        $conf = $GLOBALS['conf'];

        if (empty($conf['sharing']['allow'])) {
            return $this->redirect(
                (string) Horde::url('stories/view.php', true)
                    ->add(['story_id' => $story_id, 'channel_id' => $channel_id]),
            );
        }

        try {
            $story = $this->driver->getStory($story_id);
        } catch (Exception $e) {
            $this->notification->push(
                sprintf(_("Error fetching story: %s"), $e->getMessage()),
                'horde.warning',
            );
            $story = ['title' => '', 'id' => '', 'body' => '', 'description' => ''];
        }
        $vars->set('subject', $story['title'] ?? '');

        /* Set up the form. */
        $form = new Horde_Form($vars);
        $title = _("Share Story");
        $form->setTitle($title);
        $form->setButtons(_("Send"));
        $form->addHidden('', 'channel_id', 'int', false);
        $form->addHidden('', 'id', 'int', false);
        $v = $form->addVariable(_("From"), 'from', 'email', true, false);
        if ($this->registry->getAuth()) {
            $v->setDefault(
                $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Identity')
                    ->create()
                    ->getValue('from_addr'),
            );
        }
        $form->addVariable(
            _("To"),
            'recipients',
            'email',
            true,
            false,
            _("Separate multiple email addresses with commas."),
            true,
        );
        $form->addVariable(_("Subject"), 'subject', 'text', true);
        $form->addVariable(
            _("Include"),
            'include',
            'enum',
            true,
            false,
            null,
            [[_("A link to the story"), _("The complete text of the story")]],
        );
        $form->addVariable(
            _("Message"),
            'message',
            'longtext',
            false,
            false,
            null,
            [4, 40],
        );

        if ($form->validate($vars)) {
            $info = $form->getInfo($vars);

            if (empty($channel_id)) {
                $this->notification->push(_("No channel specified."), 'horde.error');
            } else {
                $channel = null;
                try {
                    $channel = $this->driver->getChannel($channel_id);
                } catch (Exception $e) {
                    $this->notification->push(
                        sprintf(_("Error fetching channel: %s"), $e->getMessage()),
                        'horde.error',
                    );
                }

                if (!empty($channel)) {
                    $story_url = $this->buildStoryUrl($channel, $channel_id, $story);

                    if ($info['include'] == 0) {
                        $message_part = new Horde_Mime_Part();
                        $message_part->setType('text/plain');
                        $message_part->setCharset('UTF-8');
                        $message_part->setContents($story_url);
                        $message_part->setDescription(_("Story Link"));
                    } else {
                        $message_part = $this->mailer->buildStoryPart($story);
                    }

                    try {
                        $this->mailer->send(
                            $message_part,
                            $info['from'],
                            $info['recipients'],
                            $info['subject'],
                            $info['message'],
                            'Jonah ' . $this->registry->getVersion(),
                            Horde::getMailerConfig(),
                        );
                        $this->notification->push(
                            _("The story was sent successfully."),
                            'horde.success',
                        );
                        return $this->redirect($story_url);
                    } catch (Exception $e) {
                        $this->notification->push(
                            sprintf(_("Unable to send story: %s"), $e->getMessage()),
                            'horde.error',
                        );
                    }
                }
            }
        }

        $this->pageOutput->topbar = $this->pageOutput->sidebar = false;

        $html = $this->renderChrome($title, function () use ($form, $vars) {
            $form->renderActive(null, $vars, Horde::url('stories/share.php'), 'post');
        });

        return $this->htmlResponse($html);
    }

    private function buildStoryUrl(array $channel, string $channel_id, array $story): string
    {
        if (empty($channel['channel_story_url'])) {
            $story_url = (string) Horde::url('stories/view.php', true)
                ->add(['channel_id' => '%c', 'id' => '%s']);
        } else {
            $story_url = $channel['channel_story_url'];
        }

        $story_url = str_replace(['%25c', '%25s'], ['%c', '%s'], $story_url);

        return str_replace(
            ['%c', '%s', '&amp;'],
            [$channel_id, $story['id'], '&'],
            $story_url,
        );
    }
}
