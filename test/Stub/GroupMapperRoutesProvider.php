<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Stub;

use Horde\Core\Uri\RoutesProvider;
use Horde\Routes\GroupMapper;

/**
 * Test stub: wraps a GroupMapper as a RoutesProvider for unit tests.
 */
class GroupMapperRoutesProvider implements RoutesProvider
{
    public function __construct(
        private readonly GroupMapper $mapper,
    ) {}

    public function generateNamedPath(string $routeName, array $params = []): ?string
    {
        $route = $this->mapper->getRouteNames()[$routeName] ?? null;
        if ($route === null) {
            return null;
        }
        return $route->generate($params);
    }
}
