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
 * Typed view for the HTML feed delivery page.
 *
 * Used by Feed\HtmlController.
 * Template: delivery/html
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class FeedHtmlView extends Horde_View
{
    /** @var string Form action URL. */
    public string $url = '';

    /** @var string Session token hidden input HTML. */
    public string $session = '';

    /** @var string Current channel ID. */
    public string $channel_id = '';

    /** @var string Selected format key. */
    public string $format = '';

    /** @var array HTML <option> strings. */
    public array $options = [];

    /** @var string Channel display name. */
    public string $channel_name = '';

    /** @var string Pre-rendered story content. */
    public string $stories = '';
}
