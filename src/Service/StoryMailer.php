<?php

declare(strict_types=1);

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Service;

use Horde_Mime_Mail;
use Horde_Mime_Part;
use Horde_String;
use Horde_Text_Filter_Text2html;
use Psr\Log\LoggerInterface;

/**
 * Builds story MIME messages and sends them via email.
 *
 * Extracted from Jonah_Driver::getStoryAsMessage() and the _mail()
 * function in stories/share.php.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class StoryMailer
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Build a MIME part representing the story body.
     *
     * @param array $story  A story data array with keys: body, body_type,
     *                      description.
     *
     * @return Horde_Mime_Part  The MIME part for the story content.
     */
    public function buildStoryPart(array $story): Horde_Mime_Part
    {
        $bodyType = $story['body_type'] ?? 'text';

        if ($bodyType === 'richtext') {
            return $this->buildRichtextPart($story);
        }

        return $this->buildPlaintextPart($story);
    }

    /**
     * Send a story via email.
     *
     * @param Horde_Mime_Part $storyPart  The MIME part containing the story.
     * @param string          $from       Sender email address.
     * @param string          $recipients Comma-separated recipient addresses.
     * @param string          $subject    Email subject line.
     * @param string          $note       Optional note from the sender.
     * @param string          $userAgent  User-Agent header value.
     * @param array           $mailerConfig  Horde mailer configuration.
     */
    public function send(
        Horde_Mime_Part $storyPart,
        string $from,
        string $recipients,
        string $subject,
        string $note,
        string $userAgent,
        array $mailerConfig,
    ): void {
        $mail = new Horde_Mime_Mail([
            'Subject' => $subject,
            'To' => $recipients,
            'From' => $from,
            'User-Agent' => $userAgent,
        ]);

        if (strlen($note) > 0) {
            $notePart = new Horde_Mime_Part();
            $notePart->setType('text/plain');
            $notePart->setCharset('UTF-8');
            $notePart->setContents($note);
            $notePart->setDescription(_("Note"));
            $mail->addMimePart($notePart);
        }

        $mail->addMimePart($storyPart);

        $this->logger->info('StoryMailer: <{from}> sending "{subject}" to ({to})', [
            'from' => $from,
            'subject' => $subject,
            'to' => $recipients,
        ]);

        $mail->send($mailerConfig);
    }

    /**
     * Build a multipart/alternative MIME part for richtext stories.
     */
    private function buildRichtextPart(array $story): Horde_Mime_Part
    {
        $bodyHtml = $story['body'] ?? '';
        $description = $story['description'] ?? '';

        $textFilter = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_TextFilter');

        $bodyText = $textFilter->filter($bodyHtml, 'html2text');

        /* Prepend description to both versions. */
        $bodyHtml = '<p>'
            . $textFilter->filter(
                $description,
                'text2html',
                [
                    'parselevel' => Horde_Text_Filter_Text2html::MICRO,
                    'callback' => null,
                ],
            )
            . "</p>\n" . $bodyHtml;

        $bodyText = Horde_String::wrap('  ' . $description, 70)
            . "\n\n" . $bodyText;

        $textPart = new Horde_Mime_Part();
        $textPart->setType('text/plain');
        $textPart->setCharset('UTF-8');
        $textPart->setContents($bodyText);
        $textPart->setDescription(_("Plaintext Version of Story"));

        $htmlPart = new Horde_Mime_Part();
        $htmlPart->setType('text/html');
        $htmlPart->setCharset('UTF-8');
        $htmlPart->setContents(Horde_String::wrap($bodyHtml));
        $htmlPart->setDescription(_("HTML Version of Story"));
        $htmlPart->setDisposition('inline');

        $basePart = new Horde_Mime_Part();
        $basePart->setType('multipart/alternative');
        $basePart->addPart($textPart);
        $basePart->addPart($htmlPart);

        return $basePart;
    }

    /**
     * Build a plain text MIME part for text stories.
     */
    private function buildPlaintextPart(array $story): Horde_Mime_Part
    {
        $description = $story['description'] ?? '';
        $body = $story['body'] ?? '';

        $textPart = new Horde_Mime_Part();
        $textPart->setType('text/plain');
        $textPart->setCharset('UTF-8');
        $textPart->setContents($description . "\n\n" . $body);

        return $textPart;
    }
}
