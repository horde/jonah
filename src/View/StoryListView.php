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
 * Typed view for the story list page.
 *
 * Used by Story\ListController and TagSearchController.
 * Template: stories/index
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class StoryListView extends Horde_View
{
    /** @var array Story data arrays with raw data + permission flags. */
    public array $stories = [];

    /** @var bool Whether to show the read count column. */
    public bool $read = false;

    /** @var bool Whether to show the comments column. */
    public bool $comments = false;
}
