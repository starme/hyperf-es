<?php
declare(strict_types=1);
namespace Starme\HyperfEs\Eloquent;

use Starme\HyperfEs\Query\Builder as QueryBuilder;
use Hyperf\Utils\Traits\ForwardsCalls;

class Builder
{
    use ForwardsCalls;

    /**
     * The base query builder instance.
     *
     * @var \Starme\HyperfEs\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \Starme\HyperfEs\Eloquent\Eloquent
     */
    protected $model;

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected $eagerLoad = [];

    /**
     * All of the globally registered builder macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * All of the locally registered builder macros.
     *
     * @var array
     */
    protected $localMacros = [];

    /**
     * A replacement for the typical delete function.
     *
     * @var \Closure
     */
    protected $onDelete;

    /**
     * The methods that should be returned from query builder.
     *
     * @var string[]
     */
    protected $passthru = [
        'average',
        'avg',
        'count',
        'dd',
        'doesntExist',
        'dump',
        'exists',
        'getBindings',
        'getConnection',
        'getGrammar',
        'insert',
        'insertGetId',
        'insertOrIgnore',
        'insertUsing',
        'max',
        'min',
        'raw',
        'sum',
        'toSql',
    ];

    /**
     * Applied global scopes.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Removed global scopes.
     *
     * @var array
     */
    protected $removedScopes = [];

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \Starme\HyperfEs\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Create and return an un-saved model instance.
     *
     * @param  array  $attributes
     * @return \Starme\HyperfEs\Eloquent\Eloquent|static
     */
    public function make(array $attributes = [])
    {
        return $this->newModelInstance($attributes);
    }

    /**
     * Determine if the model has a given scope.
     *
     * @param  string  $scope
     * @return bool
     */
    public function hasNamedScope($scope)
    {
        return $this->model && $this->model->hasNamedScope($scope);
    }

    /**
     * Apply the given named scope if possible.
     *
     * @param  string  $scope
     * @param  array  $parameters
     * @return mixed
     */
    public function callNamedScope($scope, array $parameters = [])
    {
        return $this->callScope(function (...$parameters) use ($scope) {
            return $this->model->callNamedScope($scope, $parameters);
        }, $parameters);
    }

    /**
     * Apply the scopes to the Eloquent builder instance and return it.
     *
     * @return static
     */
//    public function applyScopes()
//    {
//        if (! $this->scopes) {
//            return $this;
//        }
//
//        $builder = clone $this;
//
//        foreach ($this->scopes as $identifier => $scope) {
//            if (! isset($builder->scopes[$identifier])) {
//                continue;
//            }
//
//            $builder->callScope(function (self $builder) use ($scope) {
//                // If the scope is a Closure we will just go ahead and call the scope with the
//                // builder instance. The "callScope" method will properly group the clauses
//                // that are added to this query so "where" clauses maintain proper logic.
//                if ($scope instanceof Closure) {
//                    $scope($builder);
//                }
//
//                // If the scope is a scope object, we will call the apply method on this scope
//                // passing in the builder and the model instance. After we run all of these
//                // scopes we will return back the builder instance to the outside caller.
//                if ($scope instanceof Scope) {
//                    $scope->apply($builder, $this->getModel());
//                }
//            });
//        }
//
//        return $builder;
//    }

    /**
     * Apply the given scope on the current builder instance.
     *
     * @param  callable  $scope
     * @param  array  $parameters
     * @return mixed
     */
    protected function callScope(callable $scope, array $parameters = [])
    {
        array_unshift($parameters, $this);

        $query = $this->getQuery();

        // We will keep track of how many wheres are on the query before running the
        // scope so that we can properly group the added scope constraints in the
        // query as their own isolated nested where statement and avoid issues.
//        $originalWhereCount = is_null($query->wheres)
//            ? 0 : count($query->wheres);

        $result = $scope(...array_values($parameters)) ?? $this;

//        if (count((array) $query->wheres) > $originalWhereCount) {
//            $this->addNewWheresWithinGroup($query, $originalWhereCount);
//        }

        return $result;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return \Starme\HyperfEs\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
//        $builder = $this->applyScopes();

        $models = $this->getModels($columns);
        return $this->getModel()->newCollection($models);
    }

    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array|string  $columns
     * @return \Starme\HyperfEs\Eloquent\Eloquent[]|static[]
     */
    public function getModels($columns = ['*'])
    {
        return $this->model->hydrate(
            $this->query->get($columns)->all()
        )->all();
    }

    /**
     * Create a collection of models from plain arrays.
     *
     * @param  array  $items
     * @return \Starme\HyperfEs\Eloquent\Collection
     */
    public function hydrate(array $items)
    {
        $instance = $this->newModelInstance();

        return $instance->newCollection(array_map(function ($item) use ($items, $instance) {
            return $instance->newFromBuilder($item);
        }, $items));
    }

    /**
     * Create a new instance of the model being queried.
     *
     * @param  array  $attributes
     * @return \Starme\HyperfEs\Eloquent\Eloquent|\Starme\HyperfEs\Eloquent\Builder
     */
    public function newModelInstance($attributes = [])
    {
        return $this->model->newInstance($attributes)->setConnection(
            $this->query->getConnection()->getName()
        );
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \Starme\HyperfEs\Query\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param  \Starme\HyperfEs\Query\Builder  $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Starme\HyperfEs\Eloquent\Eloquent|static
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \Starme\HyperfEs\Eloquent\Eloquent  $model
     * @return $this
     */
    public function setModel(Eloquent $model)
    {
        $this->model = $model;

        $this->query->from($model->getTable());

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->hasNamedScope($method)) {
            return $this->callNamedScope($method, $parameters);
        }

        if (in_array($method, $this->passthru)) {
            return $this->getQuery()->{$method}(...$parameters);
        }

        $this->forwardCallTo($this->query, $method, $parameters);

        return $this;
    }
}
