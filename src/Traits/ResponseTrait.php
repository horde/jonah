<?php

declare(strict_types=1);

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Traits;

use Horde\Horde\Traits\HtmlResponseTrait;
use Horde\Horde\Traits\RedirectResponseTrait;

/**
 * Shared response helpers for Jonah PSR-15 controllers.
 *
 * Composes Core's HtmlResponseTrait and RedirectResponseTrait for standard
 * response building, and adds renderChrome() for legacy Horde_PageOutput
 * chrome wrapping.
 *
 * Expects the using class to have properties:
 *   - Horde_Notification_Handler $notification
 *   - Horde_PageOutput           $pageOutput
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
trait ResponseTrait
{
    use HtmlResponseTrait;
    use RedirectResponseTrait;

    /**
     * Render page content inside the Horde chrome (topbar, header, footer).
     *
     * The callable $renderBody is expected to echo its output.
     */
    private function renderChrome(string $title, callable $renderBody): string
    {
        ob_start();
        $this->pageOutput->header(['title' => $title]);
        $this->notification->notify(['listeners' => 'status']);
        $renderBody();
        $this->pageOutput->footer();

        return ob_get_clean();
    }
}
