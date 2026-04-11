<?php

/**
 * Legacy entry point for sharing stories via email — delegates to ShareController.
 *
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
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
use Horde\Jonah\Controller\Story\ShareController;

$request = (new RequestBuilder(
    new RequestFactory(),
    new StreamFactory(),
    new UriFactory(),
))->withGlobalVariables()->build();

$controller = $GLOBALS['injector']->getInstance(ShareController::class);
$response = $controller->handle($request);

(new ResponseWriterWeb())->writeResponse($response);
