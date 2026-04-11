<?php

declare(strict_types=1);

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Controller\Story;

use Exception;
use Horde\Jonah\Traits\ResponseTrait;
use Horde_Browser;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Pdf_Writer;
use Horde_Util;
use Jonah_Driver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Horde\Http\Response;
use Horde\Http\StreamFactory;

/**
 * PSR-15 controller for generating PDF versions of stories.
 *
 * Replaces stories/pdf.php + Jonah_View_StoryPdf.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class PdfController implements RequestHandlerInterface
{
    use ResponseTrait;

    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Browser $browser,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $story_id = $queryParams['id'] ?? null;
        $channel_id = $queryParams['channel_id'] ?? null;

        if (!$story_id) {
            try {
                $story_id = $this->driver->getLatestStoryId($channel_id);
            } catch (Exception $e) {
                $this->notification->push($e->getMessage(), 'horde.error');
                $html = $this->renderChrome(_("PDF"), function () {});
                return $this->htmlResponse($html);
            }
        }

        try {
            $story = $this->driver->getStory($story_id, !$this->browser->isRobot());
        } catch (Exception $e) {
            $this->notification->push($e->getMessage(), 'horde.error');
            $html = $this->renderChrome(_("PDF"), function () {});
            return $this->htmlResponse($html);
        }

        /* Convert richtext HTML body to plain text for PDF */
        if (!empty($story['body_type']) && $story['body_type'] === 'richtext') {
            $story['body'] = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_TextFilter')
                ->filter($story['body'], 'html2text');
        }

        $pdf = new Horde_Pdf_Writer(['format' => 'Letter', 'unit' => 'pt']);
        $pdf->setMargins(50, 50);
        $pdf->setAutoPageBreak(true, 50);
        $pdf->open();
        $pdf->addPage();

        if (!empty($story['published_date'])) {
            $pdf->setFont('Times', 'B', 14);
            $pdf->cell(0, 14, $story['published_date'], 0, 1);
            $pdf->newLine(10);
        }

        $pdf->setFont('Times', 'B', 24);
        $pdf->multiCell(0, 24, $story['title'], 'B', 1);
        $pdf->newLine(20);

        $pdf->setFont('Times', '', 14);
        $pdf->write(14, $story['body']);

        $pdfOutput = $pdf->getOutput();
        $filename = ($story['title'] ?? 'story') . '.pdf';

        $streamFactory = new StreamFactory();
        $body = $streamFactory->createStream($pdfOutput);
        $response = new Response();

        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string) strlen($pdfOutput))
            ->withBody($body);
    }
}
