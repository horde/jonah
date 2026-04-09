<?php

/**
 * Script to handle requests for html delivery of stories.
 *
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', [
    'authentication' => 'none',
    'session_control' => 'readonly',
]);
$jonah = Horde_Registry::appInit('jonah');
/**
 * ARCHITECTURE VIOLATION: Using deprecated Horde::loadConfiguration()
 * @deprecated Use $registry->loadConfigFile() instead
 * @see Horde_Deprecated::loadConfiguration()
 */
$templates = Horde::loadConfiguration('templates.php', 'templates', 'jonah');

/* Get the id and format of the feed to display. */
$criteria = Horde_Util::nonInputVar('criteria');
if (empty($criteria['channel_format'])) {
    // Select the default channel format
    $criteria['channel_format'] = key($templates);
}

$options = [];
foreach ($templates as $key => $info) {
    $options[] = '<option value="' . $key . '"' . ($key == $criteria['channel_format'] ? ' selected="selected"' : '') . '>' . $info['name'] . '</option>';
}

if (empty($criteria['channel_id']) && !empty($criteria['feed'])) {
    $criteria['channel_id'] = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannelId($criteria['feed']);
}

if (empty($criteria['channel_id'])) {
    $notification->push(_("No valid feed name or ID requested."), 'horde.error');
} else {
    $stories = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStories($criteria);
}

if (!empty($stories)) {
    // @TODO: Implement proper story output
}

$view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/delivery']);
$view->url = Horde::selfUrl();
$view->session = Horde_Util::formInput();
$view->channel_id = $criteria['channel_id'] ?? '';
$driver = $GLOBALS['injector']->getInstance('Jonah_Driver');
try {
    $channel = $driver->getChannel($criteria['channel_id']);
    $view->channel_name = $channel['channel_name'];
} catch (Exception $e) {
    $view->channel_name = '';
}
$view->format = $criteria['channel_format'];
$view->options = $options;
try {
    $view->stories = $driver->renderChannel($criteria['channel_id'], $criteria['channel_format']);
} catch (Exception $e) {
    $view->stories = '';
}

// Buffer the notifications and send to the template
Horde::startBuffer();
$GLOBALS['notification']->notify(['listeners' => 'status']);
$view->notify = Horde::endBuffer();

$page_output->header();
echo $view->render('html');
$page_output->footer();
