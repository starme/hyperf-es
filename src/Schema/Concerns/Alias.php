<?php
namespace Starme\HyperfEs\Schema\Concerns;

use Starme\HyperfEs\Schema\Blueprint;

trait Alias
{
    /**
     *
     * @param string $table
     * @param string $alias
     * @return void
     */
    public function alias(string $table, string $alias)
    {
        return $this->_alias($table, $alias, 'put');
    }

    /**
     *
     * @param string $table
     * @param string $alias
     * @return void
     */
    public function existsAlias(string $table, string $alias)
    {
        return $this->_alias($table, $alias, 'exists');
    }

    /**
     * Drop alias of the index.
     *
     * @param string $table
     * @param string $alias
     * @return array
     */
    public function dropAlias(string $table, string $alias)
    {
        return $this->_alias($table, $alias, 'delete');
    }
   
    /**
     * Index under alias.
     *
     * @param string $alias
     * @return array
     */
    public function getAlias(string $alias): array
    {
        return array_keys($this->_alias('', $alias, 'get'));
    }

    /**
     * Get aliases of index name.
     *
     * @param string $table
     * @return array
     */
    public function getIndexAlias(string $table): array
    {
        $alias = $this->_alias($table, '', 'get');
        if (isset($alias[$table])) {
            return array_keys($alias[$table]['aliases']);
        }
        return [];
    }

     /**
     * Alias basic operator.
     *
     * @param  string $table
     * @param  string $alias
     * @param  string $action put|exists|delete
     * @return mixed
     */
    protected function _alias(string $table, string $alias, string $action)
    {
        $body = $this->build(tap($this->createBlueprint($table), function (Blueprint $blueprint) use ($alias) {
            $blueprint->alias($alias);
        }));

        return $this->connection->alias($action, $body);
    }


    /**
     * Toggle alias of old index to new index. (old->new)
     *
     * @param string $alias
     * @param string $old old index name.
     * @param string $new new index name.
     * @return array
     */
    public function toggleAlias(string $alias, string $old, string $new)
    {
        $blueprint = $this->createBlueprint($new);
        $alias = $blueprint->warpAlias($alias);
        if ($old) {
            $body['actions'][]['remove'] = [
                'index' => $old, 'alias' => $alias
            ];
        }
        
        $body['actions'][]['add'] = [
            'index' => $blueprint->getTable(), 'alias' => $alias
        ];

        return $this->connection->alias('toggle', compact('body'));
    }

}