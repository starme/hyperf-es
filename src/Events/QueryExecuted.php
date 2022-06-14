<?php
namespace Starme\HyperfEs\Events;

class QueryExecuted
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var array
     */
    public $queries;

    /**
     * The number of milliseconds it took to execute the query.
     *
     * @var float
     */
    public $time;

    /**
     * The database connection instance.
     *
     * @var \Starme\HyperfEs\Connection
     */
    public $connection;

    /**
     * QueryExecuted constructor.
     * @param string $method
     * @param array $queries
     * @param float $time
     * @param $connection
     */
    public function __construct(string $method, array $queries, float $time, $connection)
    {
        $this->method = $method;
        $this->queries = $queries;
        $this->time = $time;
        $this->connection = $connection;
    }

}