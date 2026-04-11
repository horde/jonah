<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit;

use Horde\Jonah\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
class RouterTest extends TestCase
{
    public function testRouterCanBeInstantiated(): void
    {
        $router = new Router();
        $this->assertInstanceOf(Router::class, $router);
    }
}
