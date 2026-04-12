<?php

use Horde\Util\Util;

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Jonah
 */
require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('jonah', [
    'authentication' => 'none',
    'session_control' => 'readonly',
]);

$params = ['registry' => &$registry,
    'notification' => &$notification,
    'story_id' => Util::getFormData('id'),
    'browser' => &$browser,
    'channel_id' => Util::getFormData('channel_id')];
$view = new Jonah_View_StoryPdf($params);
$view->run();
