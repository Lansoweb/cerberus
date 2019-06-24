<?php

declare(strict_types=1);

namespace Los\Cerberus;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

class CerberusFactory
{
    public function __invoke(ContainerInterface $container) : CerberusInterface
    {
        $config = $container->get('config');
        $maxFailures = (int) ($config['cerberus']['max_failures'] ?? 5);
        $timeout = (int) ($config['cerberus']['timeout'] ?? 60);

        return new Cerberus($container->get(CacheInterface::class), $maxFailures, $timeout);
    }
}
