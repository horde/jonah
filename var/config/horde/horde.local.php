<?php

if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', '/home/i567442/php/git/horde/jonah/vendor/horde/horde');
}
if (!defined('HORDE_CONFIG_BASE')) {
    define('HORDE_CONFIG_BASE', '/home/i567442/php/git/horde/jonah/var/config');
}
ini_set('include_path', '/home/i567442/php/git/horde/jonah/vendor/horde/autoloader/lib:/home/i567442/php/git/horde/jonah/vendor/horde/form/lib/:' . ini_get('include_path'));
require_once('/home/i567442/php/git/horde/jonah/vendor/horde/core/lib/Horde/Core/Nosql.php');
require_once('/home/i567442/php/git/horde/jonah/vendor/autoload.php');
if (!defined('HORDE_TEMPLATES')) {
    define('HORDE_TEMPLATES', '/home/i567442/php/git/horde/jonah/vendor/horde/horde/templates');
}
