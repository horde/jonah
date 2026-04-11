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

use Horde_View_Helper_Base;

/**
 * View helper for generating complete <a> links and icon links.
 *
 * Replaces the Horde::link() . $content . '</a>' pattern with
 * self-contained helpers that return complete HTML elements.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class JonahLink extends Horde_View_Helper_Base
{
    /**
     * Generate a complete <a> element.
     *
     * @param string $url         The href value.
     * @param string $content     Inner HTML content (will NOT be escaped).
     * @param string $title       Optional title attribute.
     * @param array  $attributes  Extra HTML attributes.
     *
     * @return string  Complete <a>...</a> element.
     */
    public function jonahLink(
        string $url,
        string $content,
        string $title = '',
        array $attributes = [],
    ): string {
        $attrs = 'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
        if ($title !== '') {
            $attrs .= ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"';
        }
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<a ' . $attrs . '>' . $content . '</a>';
    }

    /**
     * Generate a link wrapping a theme image icon.
     *
     * Calls jonahImage() for the inner <img> tag.
     *
     * @param string $url       The href value.
     * @param string $imageSrc  Image filename (resolved via jonahImage()).
     * @param string $title     Title attribute for both <a> and <img> alt.
     *
     * @return string  Complete <a><img .../></a> element.
     */
    public function jonahIconLink(
        string $url,
        string $imageSrc,
        string $title = '',
    ): string {
        return $this->jonahLink(
            $url,
            $this->jonahImage($imageSrc, $title),
            $title,
        );
    }
}
