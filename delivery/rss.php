<?php

/**
 * Copyright 2003-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
require_once __DIR__ . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', [
    'authentication' => 'none',
    'session_control' => 'readonly',
]);

$driver = $GLOBALS['injector']->getInstance('Jonah_Driver');

// See if the criteria has already been loaded by the index page
$criteria = Horde_Util::nonInputVar('criteria');
if (!$criteria) {
    $criteria = [
        'channel_id' => Horde_Util::getFormData('channel_id'),
        'feed_type' => basename(Horde_Util::getFormData('type')),
        'limit' => 10,
    ];
    if ($tag_id = Horde_Util::getFormData('tag_id')) {
        $criteria['tags'] = array_reduce(
            $injector->getInstance('Jonah_Tagger')
                ->getTags(explode(':', $tag_id)),
            'array_merge',
            []
        );
    }
    if ($tag = Horde_Util::getFormData('tag')) {
        $criteria['tags'] = explode(':', $tag);
    }
}

// Default to RSS2
if (empty($criteria['feed_type'])) {
    $criteria['feed_type'] = 'rss2';
}
// Only published stories
$criteria['published'] = true;

// Fetch the channel info and the story list and check they are both valid.
// Do a simple exit in case of errors.
try {
    $channel = $driver->getChannel($criteria['channel_id']);
} catch (Exception $e) {
    Horde::log($e, 'ERR');
    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested feed (' . htmlspecialchars($criteria['channel_id']) . ') was not found on this server.</p>
</body></html>';
    exit;
}

// Fetch stories
try {
    $stories = $driver->getStories($criteria);
} catch (Exception $e) {
    Horde::log($e, 'ERR');
    $stories = [];
}

// Build the template
$view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/delivery']);
$view->jonah = 'Jonah ' . $registry->getVersion() . ' (http://www.horde.org/jonah/)';
$view->xsl = Horde_Themes::getFeedXsl();
if (!empty($criteria['tag_id'])) {
    $view->channel_name = sprintf(_("Stories tagged with %s in %s"), implode(',', $criteria['tags']), htmlspecialchars($channel['channel_name']));
} else {
    $view->channel_name = htmlspecialchars($channel['channel_name']);
}
$view->channel_desc = htmlspecialchars($channel['channel_desc']);
$view->channel_updated = htmlspecialchars(date('r', $channel['channel_updated']));
$view->channel_official = htmlspecialchars($channel['channel_official']);
$view->channel_rss = htmlspecialchars(Horde::url('delivery/rss.php', true, -1)->add(['type' => 'rss', 'channel_id' => $channel['channel_id']]));
$view->channel_rss2 = htmlspecialchars(Horde::url('delivery/rss.php', true, -1)->add(['type' => 'rss2', 'channel_id' => $channel['channel_id']]));
foreach ($stories as &$story) {
    $story['title'] = htmlspecialchars($story['title']);
    $story['description'] = htmlspecialchars($story['description']);
    $story['permalink'] = htmlspecialchars($story['permalink']);
    $story['storylink'] = htmlspecialchars($driver->getStoryLink($channel, $story));
    $story['published'] = htmlspecialchars(date('r', $story['published']));
    $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($story['author']);
    if ($name = $identity->getValue('fullname')) {
        $story['author'] = htmlspecialchars($name);
    } else {
        $story['author'] = htmlspecialchars($story['author']);
    }
    if (!empty($story['body_type']) && $story['body_type'] == 'text') {
        $story['body'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($story['body'], 'text2html', ['parselevel' => Horde_Text_Filter_Text2html::MICRO]);
    }
}
$view->stories = $stories;

$browser->downloadHeaders($channel['channel_name'] . '.rss', 'text/xml', true);
$tpl = $criteria['feed_type'];
if (!empty($channel['channel_full_feed'])) {
    $tpl .= '_full';
}
echo $view->render($tpl . '.xml');
