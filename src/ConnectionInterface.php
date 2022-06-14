<?php
namespace Starme\HyperfEs;

use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Starme\HyperfEs\Query\Builder as QueryBuilder;
use Starme\HyperfEs\Query\Grammar as QueryGrammar;
use Starme\HyperfEs\Schema\Builder as SchemaBuilder;

interface ConnectionInterface
{

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar();

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultSchemaGrammar();

    /**
     * Set the es client to the default implementation.
     *
     * @return void
     */
    public function useDefaultClient();

    /**
     * Get a schema builder instance for the connection.
     */
//    public function getSchemaBuilder()
//    {
//        if (is_null($this->schemaGrammar)) {
//            $this->useDefaultSchemaGrammar();
//        }
//
//        return new SchemaBuilder($this);
//    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @param $table
     * @return \Starme\Elasticsearch\Query\Builder
     */
    public function table($table): QueryBuilder;

    /**
     * Get a new query builder instance.
     *
     * @return \Starme\Elasticsearch\Query\Builder
     */
    public function query(): QueryBuilder;

    /**
     * Run a select statement against the elasticsearch.
     *
     */
    public function select(array $params);

    /**
     * Run a insert statement against the elasticsearch.
     *
     */
    public function insert(array $params);

    /**
     * Run a update statement against the elasticsearch.
     *
     */
    public function update(array $params, $by_query=false);

    /**
     * Run a delete statement against the elasticsearch.
     *
     */
    public function delete(array $params);


    public function getQueryGrammar(): QueryGrammar;

}