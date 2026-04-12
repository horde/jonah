<?php

/**
 * This class provides the Jonah configuration for the test script.
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Jonah
 * @coversNothing
 */
class Jonah_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = [
        'gettext'  =>  [
            'descrip' => 'Gettext Support',
            'error' => 'Jonah will not run without gettext support. Compile php <code>--with-gettext</code> before continuing.',
        ],
        'xml'  =>  [
            'descrip' => 'XML Support',
            'error' => 'Without XML support, Jonah WILL NOT WORK. You must fix this before going any further.',
        ],
    ];

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = [];

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = [];

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = [];

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests() {}

    /**
     * Available sub-test types.
     *
     * @return array  Map of type key => display name.
     */
    public function appTestTypes()
    {
        return [
            'routes' => 'Routes',
        ];
    }

    /**
     * Run a specific test type.
     *
     * @param string $type  The test type key.
     *
     * @return string  HTML output.
     */
    public function appTestType($type)
    {
        if ($type === 'routes') {
            return $this->_routesTest();
        }

        return '';
    }

}
