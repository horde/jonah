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

use Horde\Jonah\Service\FilesystemPathHelper;
use Horde_View_Helper_Base;

/**
 * View helper for generating <img> tags with theme-aware src resolution.
 *
 * Replaces Horde_Themes_Image::tag() in templates.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class JonahImage extends Horde_View_Helper_Base
{
    public function __construct(
        $view,
        private readonly FilesystemPathHelper $paths,
    ) {
        parent::__construct($view);
    }

    /**
     * Generate an <img> tag for a theme image.
     *
     * @param string $src         Image filename relative to themes directory.
     * @param string $alt         Alt text.
     * @param array  $attributes  Extra HTML attributes.
     *
     * @return string  Complete <img .../> element.
     */
    public function jonahImage(
        string $src,
        string $alt = '',
        array $attributes = [],
    ): string {
        $resolvedSrc = rtrim($this->paths->getHordeThemesUri(), '/') . '/' . ltrim($src, '/');

        $attrs = 'src="' . htmlspecialchars($resolvedSrc, ENT_QUOTES, 'UTF-8') . '"';
        $attrs .= ' alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<img ' . $attrs . ' />';
    }
}
