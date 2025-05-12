<?php

declare(strict_types=1);

namespace Horde\Jonah\Test\Unit;
use Horde\Jonah\Router;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class RouterTest extends TestCase
{
    // Bogus test to verify the tool chain. Do something more useful soon.
    public function testRouter():void
    {
        require_once(dirname(__DIR__, 2) . '/src/Router.php');
        $router = new Router;
        $this->assertInstanceOf(Router::class, $router);
    }
}
