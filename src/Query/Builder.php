<?php
namespace Starme\HyperfEs\Query;

use Closure;
use Exception;
use Hyperf\Di\Container;
use Hyperf\Paginator\LengthAwarePaginator;
use Hyperf\Paginator\Paginator;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Contracts\Arrayable;
use InvalidArgumentException;

class Builder
{
    /**
     * @var \Starme\HyperfEs\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Starme\HyperfEs\Query\Grammar
     */
    protected $grammar;

    /**
     * The columns that should be returned.
     *
     * @var array
     */
    public $columns;

    /**
     * Indicates if the query returns distinct results.
     *
     * @var string
     */
    public $distinct;

    /**
     * The index which the query is targeting.
     *
     * @var string
     */
    public $index;

    /**
     * @var mixed|string
     */
    public $type;

    /**
     * @var string
     */
    public $scroll;

    /**
     * @var string
     */
    public $scroll_id;

    /**
     * @var array
     */
    public $wheres;

    /**
     * @var array
     */
    public $orders = [];

    /**
     * @var int
     */
    public $offset;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var array
     */
    public $aggregate = [];

    /**
     * All of the available clause operators.
     *
     * @var string[]
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>', 'like'
    ];

    /**
     * @var bool
     */
    protected $paginate;

    /**
     * @var bool
     */
    public $refresh;

    /**
     * @var array
     */
    public $highlight;

    /**
     * @var bool
     */
    public $realTotal;

    /**
     * @var bool
     */
    public $logEnable = true;

    /**
     * Builder constructor.
     * @param $connection
     * @param $grammar
     */
    public function __construct($connection, $grammar)
    {
        $this->connection = $connection;
        $this->grammar = $grammar;
    }

    public function select($columns = ['*']): Builder
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    public function distinct($column): Builder
    {
        $this->distinct = $column;

        return $this;
    }

    public function from($name): Builder
    {
        if (str_contains($name, '.')) {
            // Table name support point syntax.
            // But only two
            [$this->index, $this->type] = explode('.', $name, '2');
        } else {
            //
            $this->index = $name;
        }

        return $this;
    }

    public function where($column, $operator = null, $value = null): Builder
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column) && func_num_args() === 1) {
            return $this->addArrayOfWheres($column);
        }

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof Closure && is_null($operator)) {
            return $this->whereNested($column);
        }

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        $type = 'Basic';
        $this->wheres[] = compact('type', 'column', 'operator', 'value');
        return $this;
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param string $operator
     * @return bool
     */
    protected function invalidOperator(string $operator): bool
    {
        return ! in_array(strtolower($operator), $this->operators, true);
    }

    /**
     * Add an array of where clauses to the query.
     *
     * @param array $column
     * @param string $method
     * @return $this
     */
    protected function addArrayOfWheres(array $column, $method = 'where'): Builder
    {
        foreach ($column as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                $this->{$method}(...array_values($value));
            } else {
                $this->$method($key, '=', $value);
            }
        }
        return $this;
    }

    /**
     * Create a new query instance for nested where condition.
     *
     * @return \Starme\HyperfEs\Query\Builder
     */
    public function forNestedWhere(): Builder
    {
        return $this->newQuery()->from($this->index);
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param \Closure $callback
     * @param string $boolean
     * @return $this
     */
    public function whereNested(Closure $callback, $boolean='and'): Builder
    {
        call_user_func($callback, $query = $this->forNestedWhere());

        if ($boolean == 'or' && count($query->wheres) == 1) {
            $where = $query->wheres[0];
            return $this->where($where['column'], strtolower($where['type']), $where['value']);
        }

        return $this->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param \Starme\HyperfEs\Query\Builder $query
     * @param $boolean
     * @return $this
     */
    public function addNestedWhereQuery(Builder $query, $boolean): Builder
    {
        if (count($query->wheres)) {
            $type = 'Nested';
            $this->wheres[] = compact('type', 'query', 'boolean');
        }
        return $this;
    }

    /**
     * Prepare the value and operator for a where clause.
     * @param $value
     * @param $operator
     * @param bool $useDefault
     * @return array
     */
    protected function prepareValueAndOperator($value, $operator, $useDefault = false): array
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * Prevents using Null values with invalid operators.
     *
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    protected function invalidOperatorAndValue(string $operator, $value): bool
    {
        return is_null($value) && in_array($operator, $this->operators) &&
            ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param \Closure $column
     * @return $this
     */
    public function orWhere(Closure $column): Builder
    {
//        [$value, $operator] = $this->prepareValueAndOperator(
//            $value, $operator, func_num_args() === 2
//        );

        if (! $column instanceof Closure) {
            throw new InvalidArgumentException("Or where must be closure");
        }
        return $this->whereNested($column, 'or');
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param string $column
     * @param $value
     * @param bool $not
     * @return $this
     */
    public function whereIn(string $column, $value, $not = false): Builder
    {
        $type = $not ? 'NotIn' : 'In';

        // Next, if the value is Arrayable we need to cast it to its raw array form so we
        // have the underlying array value instead of an Arrayable object which is not
        // able to be added as a binding, etc. We will then add to the wheres array.
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        $this->wheres[] = compact('type', 'column', 'value');

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
//    public function orWhereIn(string $column, $values): Builder
//    {
//        return $this->whereIn($column, $values, 'or');
//    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
    public function whereNotIn(string $column, $values): Builder
    {
        return $this->whereIn($column, $values, true);
    }

    /**
     * Alias to set the "where exists" of the query.
     *
     * @param string|array $columns
     * @return $this
     */
    public function whereNull($columns): Builder
    {
        return $this->whereNotExists($columns);
    }

    /**
     * Alias to set the "where not exists" of the query.
     *
     * @param string|array $columns
     * @return $this
     */
    public function whereNotNull($columns): Builder
    {
        return $this->whereExists($columns);
    }

    /**
     * Add a "where exists" clause to the query.
     *
     * @param string|array $columns
     * @param bool $not
     * @return $this
     */
    public function whereExists($columns, $not = false): Builder
    {
        $type = $not ? 'NotExists' : 'Exists';

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column');
        }

        return $this;
    }


    /**
     * Add a where not exists clause to the query.
     *
     * @param string|array $columns
     * @return $this
     */
    public function whereNotExists($columns): Builder
    {
        return $this->whereExists($columns, true);
    }


    /**
     * Add a where between statement to the query.
     *
     * @param string $column
     * @param array $value
     * @param bool $not
     * @return $this
     */
    public function whereBetween(string $column, array $value, $not = false): Builder
    {
        $type = $not ? 'NotBetween' : 'Between';

        $this->wheres[] = compact('type', 'column', 'value', 'not');

        return $this;
    }

    /**
     * Add a where not between statement to the query.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereNotBetween(string $column, array $values): Builder
    {
        return $this->whereBetween($column, $values, true);
    }

    /**
     * Add a where like statement to the query.
     *
     * @param string $column
     * @param string $value
     * @return $this
     */
    public function whereLike(string $column, string $value): Builder
    {
        $type = 'Like';

        $this->wheres[] = compact('type', 'column', 'value');

        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param string|array $column
     * @param string $direction
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function orderBy($column, $direction = 'asc'): Builder
    {
        if (is_array($column)) {
            $this->orders = array_merge($this->orders, $column);
            return $this;
        }

        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        $this->orders[] = [$column => $direction];
        return $this;
    }

    /**
     * Add a descending "order by" clause to the query.
     *
     * @param string $column
     * @return $this
     */
    public function orderByDesc(string $column): Builder
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param string $groups
     * @return $this
     */
    public function groupBy(...$groups): Builder
    {
        $this->setAggregate('terms', $groups);
        return $this;
    }

    /**
     * @param \Closure $callback
     * @return false|mixed
     */
    public function groupByNested(Closure $callback)
    {
        return call_user_func($callback, $this->forNestedWhere());
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array $groups
     * @return $this
     */
    public function groupByRaw(array $groups): Builder
    {
        if (!$callback || !is_callable($callback)) {
            return $this->groupBy($group);
        }
        $this->setAggregate('queries', [$group], [$group => $this->groupByNested($callback)]);
        return $this;
    }

    /**
     * Add a "group by" clause to the bulk.
     *
     * @param array $groups
     * @return $this
     */
    public function groupByBulk(array $groups): Builder
    {
        foreach ($groups as $name => $callback) {
            if (is_callable($callback)) {
                $groups[$name] = call_user_func($callback, $this->forNestedWhere());
                continue;
            }
            if (is_string($callback)) {
                $groups[$callback] = null;
            }
            unset($groups[$name]);
        }
        $this->setAggregate('bulk', array_keys($groups), $groups);
        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function skip(int $value): Builder
    {
        return $this->offset($value);
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function offset(int $value): Builder
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function take(int $value): Builder
    {
        return $this->limit($value);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function limit(int $value): Builder
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param int $page
     * @param int $perPage
     * @return $this
     */
    public function forPage(int $page, $perPage = 15): Builder
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return mixed|$this
     */
    public function when($value, callable $callback, $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is false.
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return mixed|$this
     */
    public function unless($value, callable $callback, $default = null)
    {
        if (! $value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return mixed|$this
     */
    public function whenExists($value, callable $callback, $default = null)
    {
        return $this->when($value ?? '', $callback, $default);
    }

    /**
     * Apply the callback's query changes if the given "value" is false.
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return mixed|$this
     */
    public function unlessExists($value, callable $callback, $default = null)
    {
        return $this->unless($value ?? '', $callback, $default);
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int|string  $id
     * @param  array  $columns
     * @return array|null
     */
    public function find($id, $columns = ['*']): ?array
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array|string  $columns
     * @return array|null
     */
    public function first($columns = ['*']): ?array
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return Collection
     */
    public function get($columns = ['*']): Collection
    {
        return collect($this->onceWithColumns(Arr::wrap($columns), function () {
            return $this->runSelect();
        }));
    }

    public function getRaw($columns = ['*']): array
    {
        return $this->onceWithColumnsRaw(Arr::wrap($columns), function () {
            return $this->runSelect();
        });
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return int
     */
    public function count(): int
    {
        $result = $this->runCount();
        return $result['count'];
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param string $scroll
     * @param string $scroll_id
     * @return \Starme\HyperfEs\Query\Builder
     */
    public function scroll(string $scroll, $scroll_id=''): Builder
    {
        $this->scroll = compact('scroll', 'scroll_id');
        return $this;
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param array $columns
     * @param callable $callback
     * @return \Illuminate\Support\Collection
     */
    protected function onceWithColumnsRaw(array $columns, callable $callback): array
    {
        if ($this->aggregate) {
            return $callback();
        }
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $response = $callback();

        if ($this->scroll) {
            $scroll_id = $response['_scroll_id'];
            if (empty($response['hits']['hits'])) {
                $this->clearScroll($scroll_id);
            }
        }
        return $response;
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param array $columns
     * @param callable $callback
     * @return Collection
     */
    protected function onceWithColumns(array $columns, callable $callback): Collection
    {
        if ($this->aggregate) {
            return $this->onceWithAggregate($callback());
        }
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $response = $callback();

        $total = $response['hits']['total']['value'] ?? 0;

        $result = collect($response['hits']['hits']);

        $this->columns = $original;

        if ($this->paginate) {
            return collect(compact('total', 'result'));
        }

        if ($this->scroll) {
            $scroll_id = $response['_scroll_id'];
            if (empty($result)) {
                $this->clearScroll($scroll_id);
            }
            return collect(compact('total', 'result', 'scroll_id'));
        }
        return $result;
    }

    public function clearScroll(string $scrollId = '')
    {
        $query["scroll_id"] = $scrollId;
        return $this->connection->clearScroll($query);
    }

    protected function onceWithAggregate($response): Collection
    {
        foreach ($response['aggregations'] as $name => $agg) {
            if (! is_array($agg)) {
                continue;
            }
            if (isset($agg['doc_count'])) {
                $results[$name] = $this->onceWithAggregate(['aggregations'=>$agg]);
                continue;
            }
            if (isset($agg['buckets'])) {
                $results[$name] = $agg['buckets'];
            }
        }
        return collect($results ?? []);
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect(): array
    {
        return $this->connection->select($this->grammar->compileSelect($this));
    }

    /**
     * Run the query as a "count" statement against the connection.
     *
     * @return array
     */
    protected function runCount(): array
    {
        return $this->connection->count($this->grammar->compileCount($this));
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return \Starme\HyperfEs\Query\Builder
     */
    public function newQuery(): Builder
    {
        return new static($this->connection, $this->grammar);
    }

    public function dd(): array
    {
        return $this->wheres;
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return \Hyperf\Paginator\LengthAwarePaginator
     * @throws \Hyperf\Di\Exception\NotFoundException
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $this->paginate = true;

        $results = $this->forPage($page, $perPage)->realTotal()->get($columns);

        $this->paginate = false;

        return $this->paginator($results['result'], $results['total'], $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Create a new length-aware paginator instance.
     *
     * @param Collection $items
     * @param int $total
     * @param int $perPage
     * @param int $currentPage
     * @param array $options
     * @return \Hyperf\Paginator\LengthAwarePaginator
     */
    protected function paginator(Collection $items, int $total, int $perPage, int $currentPage, array $options): LengthAwarePaginator
    {
        return new LengthAwarePaginator(...compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param  string|null  $key
     * @return Collection
     */
    public function pluck(string $column, $key = null): Collection
    {
        // First, we will need to select the results of the query accounting for the
        // given columns / key. Once we have the results, we will be able to take
        // the results and get the exact data that was requested for the query.
        $queryResult = $this->onceWithColumns(
            is_null($key) ? [$column] : [$column, $key],
            function () {
                return $this->runSelect();
            }
        );

        if (empty($queryResult)) {
            return collect();
        }

        return $this->pluckFromArrayColumn($queryResult, $column, $key);
    }

    /**
     * Retrieve column values from rows represented as arrays.
     *
     * @param array|Collection $queryResult
     * @param string $column
     * @param string|null $key
     * @return Collection
     */
    protected function pluckFromArrayColumn($queryResult, string $column, ?string $key): Collection
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row['_source'][$column];
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row['_source'][$key]] = $row['_source'][$column];
            }
        }

        return collect($results);
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * @param string $column
     * @param string $glue
     * @return string
     */
    public function implode(string $column, $glue = ''): string
    {
        return $this->pluck($column)->implode($glue);
    }

    public function refresh(bool $refresh=true): Builder
    {
        $this->refresh = $refresh;

        return $this;
    }

    public function logEnable(bool $enable = true): Builder
    {
        $this->logEnable = $enable;

        return $this;
    }

    public function realTotal($hits = true)
    {
        $this->realTotal = $hits;

        return $this;
    }

    public function highlight(array $fields, array $options)
    {
        $config = $this->connection->getConfig('highlight');
        $pre_tags = $post_tags = [];
        foreach ($config['tags'] as $tag) {
            $ret = preg_match('/(<.*?>)(<\/.*>)/', $tag, $matches);
            if ( ! $ret) {
                continue;
            }
            $pre_tags[] = $matches[1];
            $post_tags[] = $matches[2];
        }
        $this->highlight = array_filter(
            array_merge(compact('pre_tags', 'post_tags', 'fields'), $options)
        );

        return $this;
    }

    /**
     * Insert new records into the database.
     *
     * @param array $values
     * @return array
     */
    public function batchInsert(array $values): array
    {
        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        return $this->connection->bulk(
            $this->grammar->compileBatchInsert($this, $values), $this->logEnable
        );
    }

    /**
     * Insert new records into the database.
     *
     * @param array $values
     * @return bool
     */
    public function insert(array $values): bool
    {
        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        try {
            $this->insertGetVersion($values);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Insert new records into the database.
     *
     * @param array $values
     * @return array
     * @throws \Exception
     */
    public function insertGetVersion(array $values): array
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return [];
        }

        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        try {
            return $this->connection->insert(
                $this->grammar->compileInsert($this, $values), $this->logEnable
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }


    /**
     * Insert new records into the database.
     *
     * @param array $values
     * @return array
     */
    public function batchUpdate(array $values): array
    {
        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        return $this->connection->bulk(
            $this->grammar->compileBatchUpdate($this, $values), $this->logEnable
        );
    }

    /**
     * Update records in the database.
     *
     * @param array $values
     * @return array
     */
    public function update(array $values): array
    {
        $params = $this->grammar->compileUpdate($this, $values);
        $result = $this->connection->update($params, $this->wheres, $this->logEnable);
        return ['total'=>$result['total'], 'updated'=>$result['updated']];
    }

    /**
     * Delete records from the database.
     *
     * @param mixed $id
     * @return array
     */
    public function delete($id = null): array
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (! is_null($id)) {
            $this->where('id', '=', $id);
        }

        return $this->connection->delete(
            $this->grammar->compileDelete($this), $this->logEnable
        );
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function min(string $column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function max(string $column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function sum(string $column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param string $column
     * @return mixed
     */
    public function avg(string $column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Alias for the "avg" method.
     *
     * @param string $column
     * @return mixed
     */
    public function average(string $column)
    {
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param string $function
     * @param array $columns
     * @return mixed
     */
    public function aggregate(string $function, $columns = ['*'])
    {
        $results = $this->setAggregate($function, $columns)->get($columns);

        if (! $results->isEmpty()) {
            return array_change_key_case((array) $results[0])['aggregate'];
        }
        return;
    }

    /**
     * Set the aggregate property without running the query.
     *
     * @param string $function
     * @param array $columns
     * @param array $queries
     * @return $this
     */
    protected function setAggregate(string $function, array $columns, array $queries=[]): Builder
    {
        $aggregate = compact('function', 'columns', 'queries');
        $this->aggregate = array_merge_recursive($this->aggregate, $aggregate);
        $this->limit = 0;
        $this->offset = null;
        $this->columns = [];
        return $this;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
