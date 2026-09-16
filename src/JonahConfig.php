<?php

declare(strict_types=1);
/**
 * Jonah configuration class
 *
 * Provides access to the Jonah configuration settings.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(JonahConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Jonah;

use Horde\Core\Config\State;
use Horde\Injector\Attribute\Factory;

#[Factory(factory: JonahConfigFactory::class, method: 'create')]
class JonahConfig extends State {}
