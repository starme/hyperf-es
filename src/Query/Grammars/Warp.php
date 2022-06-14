<?php
namespace Starme\HyperfEs\Query\Grammars;

trait Warp
{
    protected $tablePrefix;

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array $columns
     * @param bool $build_script
     * @return array
     */
    public function columnizeUpdate(array $columns, $build_script=true): array
    {
        $id = $columns['id'] ?? "";
        if ($id) {
            unset($columns['id']);
        }

        if ( ! $build_script) {
            $body['script'] = $this->compileScript($columns);
        }
        else{
            $body = $columns;
        }
        return compact('id', 'body');
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array $columns
     * @return array
     */
    public function columnizeInsert(array $body): array
    {
        $id = $body['id'] ?? "";
        return compact('id', 'body');
    }
    
    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array $columns
     * @param bool $build_script
     * @return array
     */
    public function columnize(array $columns, $build_script=true): array
    {
        $id = $columns['id'] ?? "";

        if (! $build_script) {
            $body['script'] = $this->compileScript($columns);
        } else {
            $body = array_map([$this, 'wrap'], $columns);
        }
        return compact('id', 'body');
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param string $table
     * @return string
     */
    public function wrapTable(string $table): string
    {
        return $this->wrap($this->tablePrefix.$table);
    }

    /**
     * Wrap a type in keyword identifiers.
     *
     * @param string|null $type
     * @return string
     */
    public function wrapType(?string $type): ?string
    {
        return $this->wrap($type);
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param mixed $value
     * @return mixed
     */
    public function wrap($value, $defaultAlias = '')
    {
        if (stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value);
        }

        if ($defaultAlias) {
            return [$value, $defaultAlias];
        }

        return $value;
    }

    /**
     * Wrap a value that has an alias.
     *
     * @param string $value
     * @return array
     */
    protected function wrapAliasedValue(string $value): array
    {
        return preg_split('/\s+as\s+/i', $value);
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get the grammar's table prefix.
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
     *
     * @param string $prefix
     * @return self
     */
    public function setTablePrefix(string $prefix)
    {
        $this->tablePrefix = $prefix;

        return $this;
    }
}
