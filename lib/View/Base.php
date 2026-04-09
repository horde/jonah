<?php

/*
 * Jonah_View:: class wraps display or the various channel and story views.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
abstract class Jonah_View_Base
{
    /**
     * Values to include in the view's scope
     *
     * @var array
     */
    protected $_params;

    /**
     * Const'r
     *
     * @param array $params  View parameters
     */
    public function __construct($params = [])
    {
        $this->_params = $params;
    }

    protected function _exit($message)
    {
        extract($this->_params, EXTR_REFS);
        $notification->push(sprintf(_("Error fetching story: %s"), $message), 'horde.error');
        $GLOBALS['page_output']->header();
        $notification->notify(['listeners' => 'status']);
        $GLOBALS['page_output']->footer();
        exit;
    }

    /**
     * Validate that all required parameters are present before extract().
     *
     * @param array $keys  Required parameter keys.
     *
     * @throws InvalidArgumentException
     */
    protected function _requireParams(array $keys): void
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->_params)) {
                throw new InvalidArgumentException(
                    sprintf('Missing required view parameter: %s', $key)
                );
            }
        }
    }

    /**
     * Render this view.
     */
    abstract public function run();

}
