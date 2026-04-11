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
 * Typed view for the single story page.
 *
 * Used by Story\ViewController.
 * Template: stories/layout/view
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class StoryView extends Horde_View
{
    /** @var array Story data array. */
    public array $story = [];

    /** @var string Pre-rendered tag cloud HTML. */
    public string $tagcloud = '';

    /** @var ?string Share URL (null if sharing disabled). */
    public ?string $shareUrl = null;

    /** @var ?array Comments array with 'threads' and 'comments' keys. */
    public ?array $comments = null;
}
