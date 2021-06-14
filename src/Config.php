<?php

namespace Rareloop\Lumberjack;

use Illuminate\Support\Arr;

class Config
{
    private $data = [];

    public function __construct(string $path = null)
    {
        if ($path) {
            $this->load($path);
        }
    }

    public function set(string $key, $value): self
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    public function get(string $key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    public function has(string $key)
    {
        return Arr::has($this->data, $key);
    }

    public function load(string $path): self
    {
        $files = \glob($path . '/*.php');

        foreach ($files as $file) {
            $configData = include $file;

            $this->data[\pathinfo($file)['filename']] = $configData;
        }

        return $this;
    }
}
