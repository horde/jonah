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

namespace Horde\Jonah\Controller\Feed;

use Exception;
use Horde\Http\Response;
use Horde\Http\StreamFactory;
use Horde\Jonah\Service\UrlGenerator;
use Horde_Browser;
use Horde_Core_Factory_Identity;
use Horde_Core_Factory_TextFilter;
use Horde_Notification_Handler;
use Horde_PageOutput;
use Horde_Registry;
use Horde_Text_Filter_Text2html;
use Horde_Themes;
use Horde_View;
use Jonah_Driver;
use Jonah_Tagger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * PSR-15 controller for RSS/XML feed delivery.
 *
 * Replaces delivery/rss.php.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class RssController implements RequestHandlerInterface
{
    public function __construct(
        private readonly Jonah_Driver $driver,
        private readonly Jonah_Tagger $tagger,
        private readonly Horde_Notification_Handler $notification,
        private readonly Horde_PageOutput $pageOutput,
        private readonly Horde_Registry $registry,
        private readonly Horde_Browser $browser,
        private readonly LoggerInterface $logger,
        private readonly Horde_Core_Factory_Identity $identityFactory,
        private readonly Horde_Core_Factory_TextFilter $textFilter,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        /* Accept criteria from the REST dispatcher (delivery/index.php) */
        $criteria = $request->getAttribute('criteria');
        if (!$criteria) {
            $criteria = [
                'channel_id' => $queryParams['channel_id'] ?? null,
                'feed_type' => basename($queryParams['type'] ?? ''),
                'limit' => 10,
            ];
            if ($tag_id = ($queryParams['tag_id'] ?? null)) {
                $criteria['tags'] = array_reduce(
                    $this->tagger->getTags(explode(':', $tag_id)),
                    'array_merge',
                    [],
                );
            }
            if ($tag = ($queryParams['tag'] ?? null)) {
                $criteria['tags'] = explode(':', $tag);
            }
        }

        /* Default to RSS2 */
        if (empty($criteria['feed_type'])) {
            $criteria['feed_type'] = 'rss2';
        }
        /* Only published stories */
        $criteria['published'] = true;

        /* Fetch channel — 404 on failure */
        try {
            $channel = $this->driver->getChannel($criteria['channel_id']);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->errorResponse(
                404,
                sprintf('The requested feed (%s) was not found on this server.', $criteria['channel_id'] ?? ''),
            );
        }

        /* Fetch stories */
        try {
            $stories = $this->driver->getStories($criteria);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $stories = [];
        }

        /* Build the RSS view */
        $view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/delivery']);
        $view->jonah = 'Jonah ' . $this->registry->getVersion() . ' (http://www.horde.org/jonah/)';
        $view->xsl = Horde_Themes::getFeedXsl();

        if (!empty($criteria['tag_id'])) {
            $view->channel_name = sprintf(
                _("Stories tagged with %s in %s"),
                implode(',', $criteria['tags']),
                htmlspecialchars($channel['channel_name']),
            );
        } else {
            $view->channel_name = htmlspecialchars($channel['channel_name']);
        }

        $view->channel_desc = htmlspecialchars($channel['channel_desc'] ?? '');
        $view->channel_updated = htmlspecialchars(date('r', (int) $channel['channel_updated']));
        $view->channel_official = htmlspecialchars($channel['channel_official']);
        $view->channel_rss = htmlspecialchars(
            $this->urlGenerator->absoluteUrlFor('FeedRss', [
                'channel_id' => $channel['channel_id'],
                'type' => 'rss',
            ]),
        );
        $view->channel_rss2 = htmlspecialchars(
            $this->urlGenerator->absoluteUrlFor('FeedRss', [
                'channel_id' => $channel['channel_id'],
                'type' => 'rss2',
            ]),
        );

        foreach ($stories as &$story) {
            $story['title'] = htmlspecialchars($story['title']);
            $story['description'] = htmlspecialchars($story['description']);
            $story['permalink'] = htmlspecialchars($story['permalink'] ?? '');
            $story['storylink'] = htmlspecialchars($this->driver->getStoryLink($channel, $story));
            $story['published'] = htmlspecialchars(date('r', (int) $story['published']));

            $identity = $this->identityFactory->create($story['author']);
            $name = $identity->getValue('fullname');
            $story['author'] = htmlspecialchars($name ?: $story['author']);

            if (!empty($story['body_type']) && $story['body_type'] === 'text') {
                $story['body'] = $this->textFilter->filter(
                        $story['body'],
                        'text2html',
                        ['parselevel' => Horde_Text_Filter_Text2html::MICRO],
                    );
            }
        }
        $view->stories = $stories;

        /* Determine template */
        $tpl = $criteria['feed_type'];
        if (!empty($channel['channel_full_feed'])) {
            $tpl .= '_full';
        }

        $xml = $view->render($tpl . '.xml');

        /* Build PSR-7 XML response */
        $streamFactory = new StreamFactory();
        $response = new Response();

        return $response
            ->withBody($streamFactory->createStream($xml))
            ->withHeader('Content-Type', 'text/xml; charset=utf-8')
            ->withHeader(
                'Content-Disposition',
                'inline; filename="' . ($channel['channel_name'] ?? 'feed') . '.rss"',
            )
            ->withStatus(200);
    }

    /**
     * Build a simple HTML error response.
     */
    private function errorResponse(int $status, string $message): ResponseInterface
    {
        $html = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">'
            . '<html><head><title>' . $status . ' Not Found</title></head>'
            . '<body><h1>Not Found</h1>'
            . '<p>' . htmlspecialchars($message) . '</p>'
            . '</body></html>';

        $streamFactory = new StreamFactory();
        $response = new Response();

        return $response
            ->withBody($streamFactory->createStream($html))
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withStatus($status);
    }
}
