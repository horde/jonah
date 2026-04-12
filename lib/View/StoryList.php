<?php

use Horde\Util\Util;

/**
 * Turba_View_StoryList:: A view to handle displaying a list of stories in a
 * channel.
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
class Jonah_View_StoryList extends Jonah_View_Base
{
    /**
     * expects
     *   $registry
     *   $notification
     *   $prefs
     *   $conf
     *   $channel_id
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        try {
            $channel = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannel($channel_id);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Invalid channel requested. %s"), $e->getMessage()), 'horde.error');
            Horde::url('channels/index.php', true)->redirect();
            exit;
        }
        if (!Jonah::checkPermissions('channels', Horde_Perms::EDIT, [$channel_id])) {
            $notification->push(_("You are not authorised for this action."), 'horde.warning');
            throw new Horde_Exception_AuthenticationFailure();
        }

        /* Check if a URL has been passed. */
        if ($url = Horde::verifySignedUrl(Util::getFormData('url'))) {
            $url = new Horde_Url($url);
        } else {
            $url = null;
        }

        try {
            $stories = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStories(['channel_id' => $channel_id]);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Invalid channel requested. %s"), $e->getMessage()), 'horde.error');
            Horde::url('channels/index.php', true)->redirect();
            exit;
        }

        /* Do some state tests. */
        if (empty($stories)) {
            $notification->push(_("No available stories."), 'horde.warning');
        }
        if (!empty($url)) {
            $url->redirect();
        }

        /* Build story specific fields. */
        foreach ($stories as $key => $story) {
            /* published is the publication/release date, updated is the last change date. */
            if (!empty($stories[$key]['published'])) {
                $dateFormat = $prefs->getValue('date_format') . ', ' . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M%p');
                $stories[$key]['published_date'] = (new Horde_Date($stories[$key]['published']))->strftime($dateFormat);
            } else {
                $stories[$key]['published_date'] = '';
            }

            /* Default to no links. */
            $stories[$key]['pdf_link'] = '';
            $stories[$key]['edit_link'] = '';
            $stories[$key]['delete_link'] = '';
            $stories[$key]['view_link'] = Horde::link($GLOBALS['injector']->getInstance('Jonah_Driver')->getStoryLink($channel, $story), $story['description']) . htmlspecialchars($story['title']) . '</a>';

            /* PDF link. */
            $url = Horde::url('stories/pdf.php')->add(['id' => $story['id'], 'channel_id' => $channel_id]);
            $stories[$key]['pdf_link'] = $url->link(['title' => _("PDF version")]) . Horde_Themes_Image::tag('mime/pdf.png') . '</a>';

            /* Edit story link. */
            $url = Horde::url('stories/edit.php')->add(['id' => $story['id'], 'channel_id' => $channel_id]);
            $stories[$key]['edit_link'] = $url->link(['title' => _("Edit story")]) . Horde_Themes_Image::tag('edit.png') . '</a>';

            /* Delete story link. */
            if (Jonah::checkPermissions('channels', Horde_Perms::DELETE, [$channel_id])) {
                $url = Horde::url('stories/delete.php')->add(['id' => $story['id'], 'channel_id' => $channel_id]);
                $stories[$key]['delete_link'] = $url->link(['title' => _("Delete story")]) . Horde_Themes_Image::tag('delete.png') . '</a>';
            }

            /* Comment counter. */
            if (!empty($conf['comments']['allow'])
                && $registry->hasMethod('forums/numMessages')) {
                try {
                    $stories[$key]['comments'] = $registry->call('forums/numMessages', [$stories[$key]['id'], 'jonah']);
                } catch (Exception $e) {
                    Horde::log($e, 'ERR');
                }
            }

        }

        /* Render page */
        $title = $channel['channel_name'];
        $view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/stories']);
        $view->stories = $stories;
        $view->read = true;
        $view->comments = !empty($conf['comments']['allow']) && $registry->hasMethod('forums/numMessages');

        $GLOBALS['page_output']->header([
            'title' => $title,
        ]);
        $notification->notify(['listeners' => 'status']);
        echo $view->render('index');
        $GLOBALS['page_output']->footer();
    }

}
