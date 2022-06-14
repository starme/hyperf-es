<?php
declare(strict_types=1);
namespace Starme\HyperfEs\Eloquent\Concerns;


trait HasMeta
{

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $metas = [];

    /**
     * @return mixed
     */
    public function getMeta($name)
    {
        return $this->metas[$name] ?? null;
    }

    /**
     * @param array $meta
     */
    public function setMeta(string $name, $value): void
    {
        $this->metas[$name] = $value;
    }

    /**
     * @param array $metas
     */
    public function setMetas(array $metas): void
    {
        foreach ($metas as $k=>$m) {
            if ($k == '_source') {
                continue;
            }
            $this->metas[$k] = $m;
        }
    }

    /**
     * @param array
     */
    public function getMetas(): array
    {
        return $this->metas;
    }
}
