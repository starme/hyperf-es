<?php
namespace Starme\HyperfEs\Exceptions;

use Throwable;

class IndexNotFound extends \Exception
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
     * @var string
     */
    protected $index;

    /**
     * Create a new query exception instance.
     *
     * @param string $method
     * @param string $index
     * @param \Throwable $previous
     * @return void
     */
    public function __construct(string $method, string $index)
    {
        parent::__construct('', 404);

        $this->method = $method;
        $this->index = $index;
        $this->code = 404;
        $this->message = "no such index [{$index}].";
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
    public function getIndex(): array
    {
        return $this->index;
    }
}
