<?php

namespace Starme\HyperfEs\Schema;

use Closure;
use Hyperf\Utils\Fluent;


class Blueprint
{

    use Concerns\Columns;

    /**
     * The table the blueprint describes.
     *
     * @var string
     */
    protected $table;

    /**
     * The prefix of the table.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The columns that should be added to the table.
     *
     * @var \Starme\HyperfEs\Schema\ColumnDefinition[]
     */
    protected $columns = [];

    /**
     * The commands that should be run for the table.
     *
     * @var \Hyperf\Utils\Fluent[]
     */
    protected $commands = [];

    /**
     * The collation that should be used for the table.
     *
     * @var string
     */
    public $collation;

    /**
     * Create a new schema blueprint.
     *
     * @param string $table
     * @param  \Closure|null  $callback
     * @param  string  $prefix
     * @return void
     */
    public function __construct(string $table, Closure $callback = null, $prefix = '')
    {
        $this->prefix = $prefix;
        $this->table = $table;

        if (! is_null($callback)) {
            $callback($this);
        }
    }

    public function build($grammar): array
    {
        $statements = [];

        foreach ($this->commands as $command) {
            $method = 'compile' . ucfirst($command->name);

            if (method_exists($grammar, $method)) {
                if (! is_null($sql = $grammar->$method($this, $command))) {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }
        return $statements;
    }

    public function index()
    {
        return $this->addCommand('Index');
    }

    /**
     * Add create index command.
     *
     * @return \Hyperf\Utils\Fluent
     */
    public function createIndex()
    {
        return $this->addCommand('Index');
    }

    public function existsIndex()
    {
        return $this->addCommand('Index');
    }

    public function dropIndex()
    {
        return $this->addCommand('Index');
    }

    public function cloneIndex(string $target)
    {
        return $this->addCommand('CloneIndex', ['target'=>$target]);
    }

    /**
     * @return \Hyperf\Utils\Fluent
     */
    public function putTemplate(): Fluent
    {
        return $this->addCommand('PutTemplate');
    }

    /**
     * @param $name
     * @return \Hyperf\Utils\Fluent
     */
    public function alias($name): Fluent
    {
        return $this->addCommand('Alias', ['alias'=>$name]);
    }

    /**
     * @param $name
     * @return \Hyperf\Utils\Fluent
     */
    public function existsAlias($name): Fluent
    {
        return $this->addCommand('Alias', ['alias'=>$name]);
    }

    /**
     * @param $name
     * @return \Hyperf\Utils\Fluent
     */
    public function dropAlias($name): Fluent
    {
        return $this->addCommand('Alias', ['alias'=>$name]);
    }

    /**
     * @param $name
     * @return \Hyperf\Utils\Fluent
     */
    public function getAlias($name): Fluent
    {
        return $this->addCommand('GetAlias', ['alias'=>$name]);
    }

    /**
     * @return \Hyperf\Utils\Fluent
     */
    public function getIndexAlias(): Fluent
    {
        return $this->addCommand('Index');
    }

    public function order(int $number)
    {
        return $this->addCommand('TemplateOrder', ['order'=>$number]);
    }

    public function index_patterns(string $match)
    {
        return $this->addCommand('TemplateMatch', ['index_patterns'=>$match]);
    }

    /**
     * Specify shards number for the index.
     *
     * @param int $number
     * @return \Hyperf\Utils\Fluent
     */
    public function shards(int $number): Fluent
    {
        return $this->settingCommand('number_of_shards', $number);
    }

    /**
     * Specify shards number for the index.
     *
     * @param int $number
     * @return \Illuminate\Support\Fluent
     */
    public function replicas(int $number): Fluent
    {
        return $this->settingCommand('number_of_replicas', $number);
    }

    /**
     * Specify max result window for the index.
     *
     * @param int $number
     * @return \Hyperf\Utils\Fluent
     */
    public function results(int $number): Fluent
    {
        return $this->settingCommand('max_result_window', $number);
    }

    /**
     * Specify refresh interval for the index.
     *
     * @param int $number
     * @return \Hyperf\Utils\Fluent
     */
    public function refreshInterval(int $number): Fluent
    {
        return $this->settingCommand('refresh_interval', $number);
    }


    /**
     * Add a new setting command to the blueprint.
     *
     * @param string $type
     * @param  string|array  $value
     * @return \Hyperf\Utils\Fluent
     */
    public function settingCommand(string $type, $value): Fluent
    {
        return $this->addCommand(
            'setting', compact('type', 'value')
        );
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param string $name
     * @param array|object $command
     * @return \Hyperf\Utils\Fluent
     */
    protected function addCommand(string $name, $command=[]): Fluent
    {
        $this->commands[] = $command = $this->createCommand($name, $command);;
        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * @param string $name
     * @param array|object $command
     * @return \Hyperf\Utils\Fluent
     */
    protected function createCommand(string $name, $command = []): Fluent
    {
        if ($command instanceof Fluent) {
            return $command->name($name);
        }
        return new Fluent(array_merge(compact('name'), $command));
    }

    /**
     * Get the table the blueprint describes.
     *
     * @return string
     */
    public function getTable(): string
    {
        if ( ! $this->table) {
            return "";
        }
        return $this->prefix . $this->table;
    }

    /**
     * Warp the alias the blueprint describes.
     *
     * @return string
     */
    public function warpAlias($alias): string
    {
        if ( ! $alias) {
            return "";
        }
        return $this->prefix . $alias;
    }

    /**
     * Get the columns on the blueprint.
     *
     * @return \Starme\HyperfEs\Schema\ColumnDefinition[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return \Hyperf\Utils\Fluent[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

}
