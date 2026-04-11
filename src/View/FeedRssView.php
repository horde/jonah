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
 * Typed view for RSS feed delivery.
 *
 * Used by Feed\RssController.
 * Templates: delivery/rss*.xml
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class FeedRssView extends Horde_View
{
    /** @var string Generator string (app name + version). */
    public string $jonah = '';

    /** @var string XSL stylesheet URL. */
    public string $xsl = '';

    /** @var string Channel name (pre-escaped). */
    public string $channel_name = '';

    /** @var string Channel description (pre-escaped). */
    public string $channel_desc = '';

    /** @var string Channel updated date (pre-escaped). */
    public string $channel_updated = '';

    /** @var string Channel official URL (pre-escaped). */
    public string $channel_official = '';

    /** @var string RSS feed URL (pre-escaped). */
    public string $channel_rss = '';

    /** @var string RSS 2.0 feed URL (pre-escaped). */
    public string $channel_rss2 = '';

    /** @var array Story data arrays (pre-escaped). */
    public array $stories = [];
}
