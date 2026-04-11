<?php

/**
 * REST-style URL dispatcher for feed delivery.
 *
 * Parses path-based URLs and delegates to Feed\HtmlController or
 * Feed\RssController. Retains the original routing logic so that
 * URLs like /delivery/rss/channel_id/5 continue to work.
 *
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah', [
    'authentication' => 'none',
    'session_control' => 'readonly',
]);

use Horde\Http\RequestFactory;
use Horde\Http\StreamFactory;
use Horde\Http\UriFactory;
use Horde\Http\Server\RequestBuilder;
use Horde\Http\Server\ResponseWriterWeb;
use Horde\Jonah\Controller\Feed\HtmlController;
use Horde\Jonah\Controller\Feed\RssController;

/* Parse REST-style path into criteria */
$parts = explode('/', Horde_Util::getPathInfo());
$lastpart = null;
$deliveryType = null;
$criteria = [];

foreach ($parts as $part) {
    if (empty($part)) {
        continue;
    }

    if (strpos($part, '.') !== false) {
        $deliveryType = substr($part, strrpos($part, '.') + 1);
        $part = substr($part, 0, strrpos($part, '.'));
    }

    switch ($part) {
        case 'html':
        case 'rss':
            $deliveryType = $part;
            break;

        case 'type':
            $lastpart = 'feed_type';
            break;

        case 'format':
            $lastpart = 'channel_format';
            break;

        case 'author':
        case 'channel_format':
        case 'tag':
        case 'tag_id':
        case 'story':
        case 'story_id':
        case 'channel':
        case 'channel_id':
            $lastpart = $part;
            break;

        default:
            if (!empty($lastpart)) {
                $criteria[$lastpart] = $part;
                $lastpart = null;
            } else {
                $GLOBALS['injector']->getInstance(Psr\Log\LoggerInterface::class)
                    ->warning('Malformed request URL: {url}', ['url' => Horde_Util::getPathInfo()]);
                exit;
            }
            break;
    }
}

if (empty($deliveryType)) {
    $deliveryType = 'html';
}

/* Store criteria so controllers can pick them up via nonInputVar */
Horde_Util::nonInputVar('criteria', $criteria);

$request = (new RequestBuilder(
    new RequestFactory(),
    new StreamFactory(),
    new UriFactory(),
))->withGlobalVariables()->build();

if ($deliveryType === 'rss') {
    $controller = $GLOBALS['injector']->getInstance(RssController::class);
} else {
    $controller = $GLOBALS['injector']->getInstance(HtmlController::class);
}

$response = $controller->handle($request);

(new ResponseWriterWeb())->writeResponse($response);
