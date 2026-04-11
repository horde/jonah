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

namespace Horde\Jonah\View\Helper;

use Horde\Jonah\Service\UrlGenerator;
use Horde_View_Helper_Base;

/**
 * View helper for named-route URL generation.
 *
 * Exposes $this->jonahUrl() and $this->jonahAbsoluteUrl() in templates.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class JonahUrl extends Horde_View_Helper_Base
{
    public function __construct(
        $view,
        private readonly UrlGenerator $urlGenerator,
    ) {
        parent::__construct($view);
    }

    /**
     * Generate a relative URL for a named route.
     */
    public function jonahUrl(string $routeName, array $params = []): string
    {
        return $this->urlGenerator->urlFor($routeName, $params);
    }

    /**
     * Generate an absolute URL for a named route.
     */
    public function jonahAbsoluteUrl(string $routeName, array $params = []): string
    {
        return $this->urlGenerator->absoluteUrlFor($routeName, $params);
    }
}
