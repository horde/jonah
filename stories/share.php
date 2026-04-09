<?php

/**
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @package Jonah
 */

/**
 *
 * @global <type> $conf
 * @param <type> $story_part
 * @param <type> $from
 * @param <type> $recipients
 * @param <type> $subject
 * @param <type> $note
 * @return <type>
 */
function _mail($story_part, $from, $recipients, $subject, $note)
{
    throw new Jonah_Exception('Needs refactoring.');
    global $conf;

    /* Create the MIME message. */
    $mail = new Horde_Mime_Mail(['Subject' => $subject,
        'To' => $recipients,
        'From' => $from,
        'User-Agent' => 'Jonah ' . $GLOBALS['registry']->getVersion()]);

    /* If a note has been provided, add it to the message as a text part. */
    if (strlen($note) > 0) {
        $message_note = new MIME_Part('text/plain', null, 'UTF-8');
        $message_note->setContents($message_note->replaceEOL($note));
        $message_note->setDescription(_("Note"));
        $mail->addMIMEPart($message_note);
    }

    /* Get the story as a MIME part and add it to our message. */
    $mail->addMIMEPart($story_part);

    /* Log the pending outbound message. */
    Horde::log(sprintf('<%s> is sending "%s" to (%s)', $from, $subject, $recipients), 'INFO');

    /* Send the message and return the result. */
    return $mail->send(Horde::getMailerConfig());
}

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah', [
    'authentication' => 'none',
    'session_control' => 'readonly',
]);

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$channel_id = $vars->get('channel_id');
$story_id = $vars->get('id');

if (empty($conf['sharing']['allow'])) {
    Horde::url('stories/view.php', true)
        ->add(['story_id' => $story_id, 'channel_id' => $channel_id])
        ->redirect();
    exit;
}

try {
    $story = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStory($story_id);
} catch (Exception $e) {
    $notification->push(sprintf(_("Error fetching story: %s"), $e->getMessage()), 'horde.warning');
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
if ($GLOBALS['registry']->getAuth()) {
    $v->setDefault($injector->getInstance('Horde_Core_Factory_Identity')->create()->getValue('from_addr'));
}
$form->addVariable(_("To"), 'recipients', 'email', true, false, _("Separate multiple email addresses with commas."), true);
$form->addVariable(_("Subject"), 'subject', 'text', true);
$form->addVariable(_("Include"), 'include', 'enum', true, false, null, [[_("A link to the story"), _("The complete text of the story")]]);
$form->addVariable(_("Message"), 'message', 'longtext', false, false, null, [4, 40]);

if ($form->validate($vars)) {
    $info = $form->getInfo($vars);

    if (empty($channel_id)) {
        $notification->push(_("No channel specified."), 'horde.error');
    } else {
        try {
            $channel = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannel($channel_id);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error fetching channel: %s"), $e->getMessage()), 'horde.error');
            $channel = null;
        }
    }

    if (!empty($channel)) {
        if (empty($channel['channel_story_url'])) {
            $story_url = Horde::url('stories/view.php', true)->add(['channel_id' => '%c', 'id' => '%s']);
        } else {
            $story_url = $channel['channel_story_url'];
        }

        $story_url = str_replace(['%25c', '%25s'], ['%c', '%s'], $story_url);
        $story_url = str_replace(['%c', '%s', '&amp;'], [$channel_id, $story['id'], '&'], $story_url);

        if ($info['include'] == 0) {
            require_once 'Horde/MIME/Part.php';

            /* TODO: Create a "URL link" MIME part instead. */
            $message_part = new MIME_Part('text/plain');
            $message_part->setContents($message_part->replaceEOL($story_url));
            $message_part->setDescription(_("Story Link"));
        } else {
            $message_part = Jonah::getStoryAsMessage($story);
        }

        try {
            $result = _mail(
                $message_part,
                $info['from'],
                $info['recipients'],
                $info['subject'],
                $info['message']
            );
            $notification->push(_("The story was sent successfully."), 'horde.success');
            header('Location: ' . $story_url);
            exit;
        } catch (Exception $e) {
            $notification->push(sprintf(_("Unable to send story: %s"), $e->getMessage()), 'horde.error');
        }
    }
}

$page_output->topbar = $page_output->sidebar = false;

$page_output->header([
    'title' => $title,
]);
$notification->notify(['listeners' => 'status']);
$form->renderActive(null, $vars, Horde::url('stories/share.php'), 'post');
$page_output->footer();
