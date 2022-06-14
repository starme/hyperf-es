<?php
namespace Starme\HyperfEs\Schema\Concerns;


use Hyperf\Utils\Fluent;
use Starme\HyperfEs\Schema\ColumnDefinition;

trait Columns
{

    /**
     * Create a new string column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function string(string $column): ColumnDefinition
    {
        return $this->addColumn('keyword', $column);
    }

    /**
     * Create a new text column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function tinyInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('byte', $column);
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function smallInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('short', $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn('integer', $column);
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('long', $column);
    }

    /**
     * Create a new float column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function float(string $column): ColumnDefinition
    {
        return $this->addColumn('float', $column);
    }

    /**
     * Create a new double column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function double(string $column): ColumnDefinition
    {
        return $this->addColumn('double', $column);
    }

    /**
     * Create a new boolean column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create a new date column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn('date', $column);
    }

    /**
     * Create a new binary column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function binary(string $column): ColumnDefinition
    {
        return $this->addColumn('binary', $column);
    }

    /**
     * Create a new array column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function array(string $column): ColumnDefinition
    {
        return $this->addColumn('array', $column);
    }

    /**
     * Create a new object column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function object(string $column): ColumnDefinition
    {
        return $this->addColumn('object', $column);
    }

    /**
     * Create a new object column on the table.
     *
     * @param string $column
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function alias(string $column): ColumnDefinition
    {
        return $this->addColumn('alias', $column);
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param string $type
     * @param string $name
     * @param array $parameters
     * @return \Starme\HyperfEs\Schema\ColumnDefinition
     */
    public function addColumn(string $type, string $column, array $parameters = []): ColumnDefinition
    {
        $column = new ColumnDefinition(array_merge(compact('type', 'column'), $parameters));

        $this->addCommand('columns', $column);
        return $column;
    }

}