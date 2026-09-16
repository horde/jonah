<?php

declare(strict_types=1);
/**
 * Jonah configuration class factory
 *
 * Creates instances of the JonahConfig class.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(JonahConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Jonah;

use Horde\Core\Config\ConfigLoader;
use Horde\Injector\Injector;

class JonahConfigFactory
{
    public function __construct(private Injector $injector) {}

    public function create(): JonahConfig
    {
        $state = $this->injector->get(ConfigLoader::class)->load('jonah');
        return new JonahConfig($state->toArray());
    }
}
