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

namespace Horde\Jonah\Service;

/**
 * Injectable facade for filesystem and URI paths.
 *
 * Replaces JONAH_TEMPLATES constant and centralises Horde registry
 * path lookups (JS assets, theme URIs) behind a narrow, testable
 * interface.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class FilesystemPathHelper
{
    public function __construct(
        private readonly string $jonahBase,
        private readonly string $hordeJsUri,
        private readonly string $hordeJsFs,
        private readonly string $hordeThemesUri,
        private readonly string $jonahThemesUri,
    ) {}

    /**
     * Resolve a template directory path.
     *
     * @param string $subdirectory  Optional subdirectory under templates/.
     *
     * @return string  Absolute filesystem path.
     */
    public function getTemplatePath(string $subdirectory = ''): string
    {
        $base = $this->jonahBase . '/templates';

        return $subdirectory !== '' ? $base . '/' . $subdirectory : $base;
    }

    /**
     * Resolve multiple template directory paths (for Horde_View multi-path).
     *
     * @param string ...$subdirectories  Subdirectories under templates/.
     *
     * @return string[]  Absolute filesystem paths.
     */
    public function getTemplatePaths(string ...$subdirectories): array
    {
        return array_map(
            fn (string $sub): string => $this->getTemplatePath($sub),
            $subdirectories,
        );
    }

    /**
     * Horde JS asset URI (web-readable).
     */
    public function getHordeJsUri(): string
    {
        return $this->hordeJsUri;
    }

    /**
     * Horde JS filesystem path.
     */
    public function getHordeJsFs(): string
    {
        return $this->hordeJsFs;
    }

    /**
     * Horde themes URI (web-readable).
     */
    public function getHordeThemesUri(): string
    {
        return $this->hordeThemesUri;
    }

    /**
     * Jonah themes URI (web-readable).
     */
    public function getJonahThemesUri(): string
    {
        return $this->jonahThemesUri;
    }
}
