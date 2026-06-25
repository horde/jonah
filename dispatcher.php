<?php

/**
 * Legacy URL dispatcher — parses route-based URLs and delegates to
 * PSR-15 controllers.
 *
 * Copyright 2008-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('jonah', [
    'authentication' => 'none',
    'session_control' => 'readonly',
]);

use Horde\Http\RequestFactory;
use Horde\Http\StreamFactory;
use Horde\Http\Uri;
use Horde\Http\UriFactory;
use Horde\Http\Server\RequestBuilder;
use Horde\Http\Server\ResponseWriterWeb;
use Horde\Routes\Mapper;

$mapper = new Mapper();

require JONAH_BASE . '/config/routes.php';
if (file_exists(JONAH_BASE . '/config/routes.local.php')) {
    include JONAH_BASE . '/config/routes.local.php';
}

/*
 * Build PSR-7 request and extract the URL path to match against routes.
 * Strip the Jonah webroot prefix so the mapper sees paths relative to the app.
 *
 * Note: the configured webroot may be either a bare path ("/apps/jonah") or a
 * fully qualified URL ("https://example.org/apps/jonah"). Parse it through
 * Uri::getPath() so str_starts_with() compares path-against-path either way.
 */
$request = (new RequestBuilder(
    new RequestFactory(),
    new StreamFactory(),
    new UriFactory(),
))->withGlobalVariables()->build();

$webrootConfig = (string) $GLOBALS['registry']->get('webroot', 'jonah');
$webroot = rtrim((new Uri($webrootConfig))->getPath(), '/');
$path = $request->getUri()->getPath();
if ($webroot !== '' && str_starts_with($path, $webroot)) {
    $path = substr($path, strlen($webroot));
}
if ($path === '' || $path === false) {
    $path = '/';
}

$result = $mapper->match($path);

if (!$result) {
    $urlGenerator = $GLOBALS['injector']->getInstance(
        Horde\Jonah\Service\UrlGenerator::class,
    );
    $GLOBALS['notification']->push(_("Page not found."), 'horde.error');
    header('Location: ' . $urlGenerator->absoluteUrlFor('ChannelList'));
    exit;
}

switch ($result['controller']) {
    case 'admin':
        // Not yet implemented
        exit;

    default:
        /*
         * If the route matched a PSR-15 controller class, instantiate and run it.
         * The route's 'controller' value is the FQCN set via withController().
         */
        $controllerClass = $result['controller'];
        if (class_exists($controllerClass)) {
            /*
             * Expose matched route parameters (e.g. channel_id, id) to the
             * controller as the 'route' request attribute. Without this, every
             * controller would see an empty $request->getAttribute('route', [])
             * and redirect to ChannelList with "No channel requested."
             */
            $request = $request->withAttribute('route', $result);
            $controller = $GLOBALS['injector']->getInstance($controllerClass);
            $response = $controller->handle($request);
            (new ResponseWriterWeb())->writeResponse($response);
        } else {
            $urlGenerator = $GLOBALS['injector']->getInstance(
                Horde\Jonah\Service\UrlGenerator::class,
            );
            $GLOBALS['notification']->push(
                sprintf(_("Unknown controller: %s"), $controllerClass),
                'horde.error',
            );
            header('Location: ' . $urlGenerator->absoluteUrlFor('ChannelList'));
            exit;
        }
        break;
}
