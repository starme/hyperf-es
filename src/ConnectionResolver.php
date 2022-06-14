<?php

namespace Starme\HyperfEs;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Utils\Arr;
use InvalidArgumentException;
use Hyperf\Di\Annotation\Inject;

class ConnectionResolver implements ConnectionResolverInterface
{
    /**
     * @inject
     *
     * @var ContainerInterface
     */
    protected $app;

    /**
     * All of the registered connections.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The default connection name.
     *
     * @var string
     */
    protected $default = 'default';

    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     * @return \Starme\HyperfEs\ConnectionInterface
     */
    public function connection($name = null): ConnectionInterface
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Add a connection to the resolver.
     *
     * @param string $name
     * @param \Starme\HyperfEs\ConnectionInterface $connection
     * @return void
     */
    public function addConnection(string $name, ConnectionInterface $connection)
    {
        $this->connections[$name] = $connection;
    }

    /**
     * Check if a connection has been registered.
     *
     * @param string $name
     * @return bool
     */
    public function hasConnection(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
     *
     * @param  string|null  $name
     * @return void
     */
    public function disconnect($name = null)
    {
        if ($this->hasConnection($name = $name ?: $this->getDefaultConnection())) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Reconnect to the given database.
     *
     * @param  string|null  $name
     * @return \Starme\HyperfEs\ConnectionInterface
     */
    public function reconnect($name = null): ConnectionInterface
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshConnections($name);
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param string $name
     * @return \Starme\HyperfEs\Connection
     */
    protected function refreshConnections(string $name): Connection
    {
        $fresh = $this->makeConnection($name);

        return $this->connections[$name]->setClient($fresh->getClient());
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return $this->default;
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultConnection(string $name)
    {
        $this->default = $name;
    }

    /**
     * Make the database connection instance.
     *
     * @param string $name
     * @return \Starme\HyperfEs\Connection
     */
    protected function makeConnection(string $name): Connection
    {
        $config = $this->configuration($name);

        $connection = new Connection(
            $config,
            $this->app->make(LoggerFactory::class)->get($config['logger'])
        );

        return $connection->setEvents($this->app->make(EventDispatcherInterface::class));
    }

    /**
     * Get the configuration for a connection.
     *
     * @param string $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration(string $name): array
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $config = $this->app->get(ConfigInterface::class);
        $connections = $config->get('es.connections');

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return array_merge($config, compact('name'));
    }

    /**
     * Prepare the database connection instance.
     *
     * @param \Starme\HyperfEs\Connection $connection
     * @param string $type
     * @return \Starme\HyperfEs\Connection
     */
    protected function configure(Connection $connection, string $type): Connection
    {
        return $connection;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
