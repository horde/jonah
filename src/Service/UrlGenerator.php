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

use Horde\Routes\Mapper;
use Horde\Routes\Utils;

/**
 * Injectable URL generator wrapping Horde\Routes\Utils.
 *
 * Provides named-route URL generation for Jonah controllers, replacing
 * direct Horde::url() static calls.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class UrlGenerator
{
    private Utils $utils;

    public function __construct(
        private readonly Mapper $mapper,
        private readonly string $webroot,
        private readonly string $hordeJsUri = '',
        private readonly string $hordeJsFs = '',
        private readonly string $hordeThemesUri = '',
        private readonly string $jonahThemesUri = '',
    ) {
        $this->mapper->environ['SCRIPT_NAME'] = rtrim($webroot, '/');
        $this->utils = new Utils($this->mapper);
    }

    /**
     * Generate a relative URL for a named route.
     *
     * Extra params not in the route path become query string parameters.
     *
     * @param string $routeName  The route name from config/routes.php.
     * @param array  $params     Route parameters and/or extra query params.
     *
     * @return string  The generated URL.
     */
    public function urlFor(string $routeName, array $params = []): string
    {
        return $this->utils->urlFor($routeName, $params);
    }

    /**
     * Generate a fully qualified (absolute) URL for a named route.
     *
     * @param string $routeName  The route name from config/routes.php.
     * @param array  $params     Route parameters and/or extra query params.
     *
     * @return string  The generated URL with scheme and host.
     */
    public function absoluteUrlFor(string $routeName, array $params = []): string
    {
        $params['qualified'] = true;

        return $this->utils->urlFor($routeName, $params);
    }

    /**
     * Horde JS asset URI (e.g. for syntax highlighter scripts).
     */
    public function getHordeJsUri(): string
    {
        return $this->hordeJsUri;
    }

    /**
     * Horde JS filesystem path (e.g. for stylesheets).
     */
    public function getHordeJsFs(): string
    {
        return $this->hordeJsFs;
    }

    /**
     * Horde themes URI.
     */
    public function getHordeThemesUri(): string
    {
        return $this->hordeThemesUri;
    }

    /**
     * Jonah themes URI.
     */
    public function getJonahThemesUri(): string
    {
        return $this->jonahThemesUri;
    }
}
