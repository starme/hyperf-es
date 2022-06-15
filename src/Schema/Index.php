<?php

declare(strict_types=1);

namespace Starme\HyperfEs\Schema;

use Hyperf\Utils\ApplicationContext;

use Starme\HyperfEs\ConnectionResolverInterface;
use Starme\HyperfEs\ConnectionInterface;

class Index
{
    public static function __callStatic($name, $arguments)
    {
        $container = ApplicationContext::getContainer();
        $resolver = $container->get(ConnectionResolverInterface::class);
        $connection = $resolver->connection();
        return $connection->schema()->{$name}(...$arguments);
    }

    public function __call($name, $arguments)
    {
        return self::__callStatic($name, $arguments);
    }

    /**
     * Create a connection by ConnectionResolver.
     */
    public function connection(string $name = 'default'): ConnectionInterface
    {
        $container = ApplicationContext::getContainer();
        $resolver = $container->get(ConnectionResolverInterface::class);
        return $resolver->connection($name);
    }
}
