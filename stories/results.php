<?php

/**
 * Legacy entry point for tag search results — delegates to TagSearchController.
 *
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah');

use Horde\Http\RequestFactory;
use Horde\Http\StreamFactory;
use Horde\Http\UriFactory;
use Horde\Http\Server\RequestBuilder;
use Horde\Http\Server\ResponseWriterWeb;
use Horde\Jonah\Controller\TagSearchController;

$request = (new RequestBuilder(
    new RequestFactory(),
    new StreamFactory(),
    new UriFactory(),
))->withGlobalVariables()->build();

$controller = $GLOBALS['injector']->getInstance(TagSearchController::class);
$response = $controller->handle($request);

(new ResponseWriterWeb())->writeResponse($response);
