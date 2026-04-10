<?php

declare(strict_types=1);

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah;

/**
 * Story ordering options for channel queries.
 *
 * Replaces the legacy Jonah::ORDER_* constants.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
enum StoryOrder: int
{
    case Published = 0;
    case Read = 1;
    case Comments = 2;
}
