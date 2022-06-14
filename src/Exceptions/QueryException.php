<?php
namespace Starme\HyperfEs\Exceptions;

use Throwable;

class QueryException extends \Exception
{
    /**
     * The SQL for the query.
     *
     * @var string
     */
    protected $method;

    /**
     * The bindings for the query.
     *
     * @var array
     */
    protected $queries;

    /**
     * Create a new query exception instance.
     *
     * @param string $method
     * @param array $queries
     * @param \Throwable $previous
     * @return void
     */
    public function __construct(string $method, array $queries, Throwable $previous)
    {
        parent::__construct('', 0, $previous);

        $this->method = $method;
        $this->queries = $queries;
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($queries, $previous);
    }

    /**
     * Format the SQL error message.
     *
     * @param array $queries
     * @param \Throwable $previous
     * @return string
     */
    protected function formatMessage(array $queries, Throwable $previous): string
    {
        return $previous->getMessage().' (Queries: '.json_encode($queries, JSON_UNESCAPED_UNICODE).')';
    }

    /**
     * Get the SQL for the query.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the bindings for the query.
     *
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
