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
use Horde\Http\UriFactory;
use Horde\Http\Server\RequestBuilder;
use Horde\Http\Server\ResponseWriterWeb;

$m = new Horde_Routes_Mapper();

require JONAH_BASE . '/config/routes.php';
if (file_exists(JONAH_BASE . '/config/routes.local.php')) {
    include JONAH_BASE . '/config/routes.local.php';
}

$httpRequest = new Horde_Controller_Request_Http();
$url = $httpRequest->getPath();
$result = $m->match('/' . $url);

if (!$result) {
    $GLOBALS['notification']->push(_("Page not found."), 'horde.error');
    Horde::url('channels/index.php', true)->redirect();
    exit;
}

/*
 * Build a PSR-7 request. The matched route result and query params are
 * available to the controller via query parameters and nonInputVar.
 */
$request = (new RequestBuilder(
    new RequestFactory(),
    new StreamFactory(),
    new UriFactory(),
))->withGlobalVariables()->build();

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
            $controller = $GLOBALS['injector']->getInstance($controllerClass);
            $response = $controller->handle($request);
            (new ResponseWriterWeb())->writeResponse($response);
        } else {
            $GLOBALS['notification']->push(
                sprintf(_("Unknown controller: %s"), $controllerClass),
                'horde.error',
            );
            Horde::url('channels/index.php', true)->redirect();
        }
        break;
}
