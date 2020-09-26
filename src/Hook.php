<?php

namespace Hooks;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Hook implements ArrayAccess, Arrayable
{
    protected $name;
    protected string $description = 'This is a hook.';
    protected $version;

    /**
     * @var false
     */
    protected bool $enabled = false;

    protected $latest = null;

    protected $composerJson;

    protected Filesystem $filesystem;

    /**
     * @var string[]
     *
     * @psalm-var array{0: string, 1: string}
     */
    protected static array $jsonParameters = ['description', 'enabled'];

    public function __construct($data)
    {
        $this->filesystem = new Filesystem();

        $this->update($data);

        $this->loadJson();
    }

    public function getProviders()
    {
        return $this->getComposerHookKey('providers', []);
    }

    /**
     * @param array|null $default
     */
    public function getComposerHookKey(string $key, ?array $default = null)
    {
        if (is_null($this->composerJson)) {
            $this->loadComposerJson();
        }

        if (!isset($this->composerJson['extra'])) {
            return $default;
        }

        if (!isset($this->composerJson['extra']['hook'])) {
            return $default;
        }

        if (!isset($this->composerJson['extra']['hook'][$key])) {
            return $default;
        }

        return $this->composerJson['extra']['hook'][$key];
    }

    public function getAliases()
    {
        return $this->getComposerHookKey('aliases', []);
    }

    public function loadComposerJson(): void
    {
        $this->composerJson = json_decode($this->getComposerJsonFile(), true);
    }

    public function getPath(): string
    {
        if ($this->isLocal()) {
            return base_path('hooks/'.$this->name);
        }

        return base_path('vendor/'.$this->name);
    }

    public function getComposerJsonFile()
    {
        return $this->filesystem->get($this->getPath().'/composer.json');
    }

    public function setLatest($latest): void
    {
        $this->latest = $latest;
    }

    public function loadJson($path = null): void
    {
        if (is_null($path)) {
            if ($this->isLocal()) {
                $path = base_path("hooks/{$this->name}/hook.json");
            } else {
                $path = base_path("vendor/{$this->name}/hook.json");
            }
        }

        $this->mergeWithJson($path);
    }

    public function update(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function outdated(): bool
    {
        if (is_null($this->latest)) {
            $this->latest = app('hooks')->outdated($this->name);
        }

        return $this->latest != $this->version;
    }

    public function mergeWithJson($path): void
    {
        if ($this->filesystem->exists($path)) {
            $data = json_decode($this->filesystem->get($path), true);

            $this->update(
                collect($data)->only(static::$jsonParameters)->all()
            );
        }
    }

    /**
     * @param array-key $key
     */
    public function setAttribute($key, $value)
    {
        $method = Str::camel('set_'.$key.'_attribute');

        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        $this->$key = $value;
    }

    public function getAttribute($key)
    {
        $method = Str::camel('get_'.$key.'_attribute');

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->$key;
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __set($key, $value)
    {
        return $this->setAttribute($key, $value);
    }

    /**
     * @return (bool|mixed)[]
     *
     * @psalm-return array{name: mixed, description: mixed, version: mixed, enabled: bool}
     */
    public function toArray()
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'version'     => $this->version,
            'enabled'     => (bool) $this->enabled,
        ];
    }

    public function __toArray()
    {
        return $this->toArray();
    }

    public function isLocal()
    {
        return $this->filesystem->isDirectory(base_path("hooks/{$this->name}"));
    }
}
