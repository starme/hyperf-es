<?php
namespace Starme\HyperfEs\Schema\Concerns;

use Closure;
use Hyperf\Utils\Fluent;

trait Template
{
    /**
     *
     * @param string $table
     * @param string $alias
     * @return void
     */
    public function existsTemplate(string $table, string $alias)
    {
        $body = $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($alias) {
            $blueprint->existsTemplate($alias);
        }));

        $this->connection->template('exists', $body);
    }

    /**
     *
     * @param string $table
     * @param int $weight
     * @param \Closure $callback
     * @return void
     */
    public function createTemplate(string $table, Closure $callback)
    {
        $this->alterTemplate($table, $callback);
    }

    /**
     *
     * @param string $table
     * @param \Closure $callback
     * @return void
     */
    public function alterTemplate(string $table, Closure $callback)
    {
        $body = $this->build(tap($this->createBlueprint($table), function (Blueprint $blueprint) use ($callback) {
            $blueprint->putTemplate();

            $callback($blueprint);
        }));

        $name = $body['name'];
        $body = array_diff_key($body, compact('name'));
        dd($this->connection->template('put', compact('name', 'body')));
    }

    /**
     *
     * @param string $table
     * @param string $alias
     * @return void
     */
    public function DropTemplate(string $table, string $alias)
    {
        $body = $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($alias) {
            $blueprint->DropTemplate($alias);
        }));

        $this->connection->template('delete', $body);
    }
}