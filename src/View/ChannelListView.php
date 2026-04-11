<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\View;

use Horde_View;

/**
 * Typed view for the channel list page.
 *
 * Used by Channel\ListController.
 * Template: view/channellist
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class ChannelListView extends Horde_View
{
    /** @var array|false Channel data arrays with permission flags. */
    public array|false $channels = false;
}
