<?php

declare(strict_types=1);

/**
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Controller\Story;

use Exception;
use Horde\Core\Config\LegacyMergedConfig;
use Horde\Jonah\Service\StoryMailer;
use Horde\Jonah\Service\UrlGenerator;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Core_Factory_Identity;
use Horde_Core_Factory_Mail;
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
        private readonly LegacyMergedConfig $config,
        private readonly Horde_Core_Factory_Identity $identityFactory,
        private readonly Horde_Core_Factory_Mail $mailFactory,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vars = Horde_Variables::getDefaultVariables();
        $route = $request->getAttribute('route', []);
        $channel_id = $route['channel_id'] ?? $vars->get('channel_id');
        $story_id = $route['id'] ?? $vars->get('id');

        if (!$this->config->get('sharing.allow')) {
            return $this->redirect(
                $this->urlGenerator->absoluteUrlFor('StoryView', [
                    'channel_id' => $channel_id,
                    'id' => $story_id,
                ]),
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
                $this->identityFactory->create()->getValue('from_addr'),
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
                            $this->mailFactory->getConfig(),
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

        $html = $this->renderChrome($title, function () use ($form, $vars, $channel_id, $story_id) {
            $form->renderActive(
                null,
                $vars,
                $this->urlGenerator->urlFor('StoryShare', ['channel_id' => $channel_id, 'id' => $story_id]),
                'post',
            );
        });

        return $this->htmlResponse($html);
    }

    private function buildStoryUrl(array $channel, string $channel_id, array $story): string
    {
        if (empty($channel['channel_story_url'])) {
            return $this->urlGenerator->absoluteUrlFor('StoryView', [
                'channel_id' => $channel_id,
                'id' => $story['id'],
            ]);
        }

        $story_url = $channel['channel_story_url'];
        $story_url = str_replace(['%25c', '%25s'], ['%c', '%s'], $story_url);

        return str_replace(
            ['%c', '%s', '&amp;'],
            [$channel_id, $story['id'], '&'],
            $story_url,
        );
    }
}
