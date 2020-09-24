<?php

namespace Hooks;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Exception;

class Composer
{
    protected $location;
    protected $filesystem;
    protected $items;
    protected $changed = false;

    public function __construct($location = null)
    {
        if (is_null($location)) {
            $location = base_path('composer.json');
        }

        $this->filesystem = app(Filesystem::class);
        $this->location = $location;

        $this->read();
    }

    /**
     * @param (array|bool|null|string)[] $info
     */
    public function addRepository($name, array $info)
    {
        if (!$this->items->has('repositories')) {
            $this->items['repositories'] = [];
        }

        $this->items->set('repositories.'.$name, $info);

        $this->changed = true;

        return $this;
    }

    /**
     * @param false $value
     */
    public function addConfig(string $key, bool $value)
    {
        if (!$this->items->has('config')) {
            $this->items['config'] = [];
        }

        $this->items->set('config.'.$key, $value);

        $this->changed = true;

        return $this;
    }

    public function set($key, $value)
    {
        $this->items->set($key, $value);

        $this->changed = true;

        return $this;
    }

    public function get($key, $default = null)
    {
        return $this->items->get($key, $default);
    }

    public function has($key)
    {
        return $this->items->has($key);
    }

    public function save()
    {
        if ($this->changed) {
            $this->filesystem->put($this->location, $this->encode($this->items->all()));

            $this->changed = false;
        }
    }

    protected function read()
    {
        if (!$composerFileContent = $this->decode(
            $this->filesystem->get($this->location)
        )
        ) {
            throw new Exception('Erro no arquivo composer.json');
        }
        $this->items = new Repository(
            $composerFileContent
        );

        $this->changed = false;
    }

    protected function decode($string)
    {
        return json_decode($string, true);
    }

    protected function encode(array $array)
    {
        return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
