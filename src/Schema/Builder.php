<?php
namespace Starme\HyperfEs\Schema;


use Closure;
use Starme\HyperfEs\Connection;
use Starme\HyperfEs\Exceptions\IndexNotFound;
use Starme\HyperfEs\Exceptions\QueryException;

class Builder
{
    use Concerns\Alias;

    /**
     * The database connection instance.
     *
     * @var \Starme\HyperfEs\Connection
     */
    protected $connection;

    /**
     * The schema grammar instance.
     *
     * @var \Starme\HyperfEs\Schema\Grammar
     */
    protected $grammar;

    /**
     * The Blueprint resolver callback.
     *
     * @var \Closure
     */
    protected $resolver;

    /**
     * Create a new database Schema manager.
     *
     * @param \Starme\HyperfEs\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * Create a new table on the schema.
     *
     * @param string $table
     * @param \Closure $callback
     * @return array
     */
    public function create(string $table, Closure $callback): array
    {
        $body = $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($callback) {
            $blueprint->index();

            $callback($blueprint);
        }));

        $index = $body['index'];
        $body = array_diff_key($body, compact('index'));
        return $this->connection->index('create', compact('index', 'body'));
    }

    /**
     * Create a new table on the schema.
     *
     * @param string $table
     * @return bool
     */
    public function exists(string $table): bool
    {
        $body = $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->index();
        }));
        return $this->connection->index('exists', $body);
    }

    /**
     * Drop a table on the schema.
     *
     * @param string $table
     * @return array
     */
    public function drop(string $table): array
    {
        $body = $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->index();
        }));

        return $this->connection->index('delete', $body);
    }

    /**
     * @deprecated Supported of later.
     *
     * @param string $table
     * @param string $target
     * @return mixed
     * @throws QueryException
     */
    public function cloneIndex(string $table, string $target)
    {
        $body = $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($target) {
            $blueprint->cloneIndex($target);
        }));

        return $this->connection->index('clone', $body);
    }

    /**
     * Drop a table on the schema.
     *
     * @param string $table
     * @return array
     */
    public function dropIfExists(string $table): array
    {
        if ($this->exists($table)) {
            return $this->drop($table);
        }
        return [];
    }

    /**
     * Execute the blueprint to build / modify the table.
     *
     * @param \Starme\HyperfEs\Schema\Blueprint $blueprint
     * @return array
     */
    protected function build(Blueprint $blueprint): array
    {
        return $blueprint->build($this->grammar);
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param string $table
     * @param  \Closure|null  $callback
     * @return \Starme\HyperfEs\Schema\Blueprint
     */
    protected function createBlueprint(string $table, Closure $callback = null): Blueprint
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback, $prefix);
        }

        return new Blueprint($table, $callback, $prefix);
    }

    /**
     * Get the database connection instance.
     *
     * @return \Starme\HyperfEs\Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Set the database connection instance.
     *
     * @param \Starme\HyperfEs\Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection): Builder
    {
        $this->connection = $connection;

        return $this;
    }

}