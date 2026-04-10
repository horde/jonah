<?php

/**
 * Legacy entry point — delegates to the PSR-15 Channel\DeleteController.
 *
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah');

use Horde\Http\RequestFactory;
use Horde\Http\StreamFactory;
use Horde\Http\UriFactory;
use Horde\Http\Server\RequestBuilder;
use Horde\Http\Server\ResponseWriterWeb;
use Horde\Jonah\Controller\Channel\DeleteController;

$request = (new RequestBuilder(
    new RequestFactory(),
    new StreamFactory(),
    new UriFactory(),
))->withGlobalVariables()->build();

$controller = $GLOBALS['injector']->getInstance(DeleteController::class);
$response = $controller->handle($request);

(new ResponseWriterWeb())->writeResponse($response);
