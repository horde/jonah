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
class Jonah_View_DeliveryHtml extends Jonah_View_Base
{
    /**
     * $registry
     * $notification
     * $conf
     * $criteria
     *
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);
        /**
         * ARCHITECTURE VIOLATION: Using deprecated Horde::loadConfiguration()
         * @deprecated Use $registry->loadConfigFile() instead
         * @see Horde_Deprecated::loadConfiguration()
         */
        $templates = Horde::loadConfiguration('templates.php', 'templates', 'jonah');

        /* Get requested channel. */
        try {
            $channel = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannel($criteria['feed']);
        } catch (Exception $e) {
            Horde::log($e, 'ERR');
            $notification->push(_("Invalid channel."), 'horde.error');
            Horde::url('delivery/index.php', true)->redirect();
            exit;
        }

        $title = sprintf(_("HTML Delivery for \"%s\""), $channel['channel_name']);

        $options = [];
        foreach ($templates as $key => $info) {
            $options[] = '<option value="' . $key . '"' . ($key == $criteria['format'] ? ' selected="selected"' : '') . '>' . $info['name'] . '</option>';
        }

        $view = new Horde_View(['templatePath' => JONAH_TEMPLATES . '/delivery']);
        $view->url = Horde::selfUrl();
        $view->session = Horde_Util::formInput();
        $view->channel_id = $criteria['feed'];
        $view->channel_name = $channel['channel_name'];
        $view->format = $criteria['format'];
        $view->options = $options;

        // @TODO: This is ugly. storage driver shouldn't be rendering any display
        // refactor this to use individual views possibly with a choice of different templates
        $view->stories = $GLOBALS['injector']->getInstance('Jonah_Driver')->renderChannel($criteria['feed'], $criteria['format']);

        // Buffer the notifications and send to the template
        Horde::startBuffer();
        $GLOBALS['notification']->notify(['listeners' => 'status']);
        $view->notify = Horde::endBuffer();

        $GLOBALS['page_output']->header([
            'title' => $title,
        ]);
        echo $view->render('html');
        $GLOBALS['page_output']->footer();
    }

}
