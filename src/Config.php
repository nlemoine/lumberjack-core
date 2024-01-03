<?php

namespace Rareloop\Lumberjack;

use Illuminate\Support\Arr;

class Config
{
    private array $data = [];

    public function __construct(
        private ?string $path = null
    ) {
    }

    public function set(string $key, $value): self
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    public function get(string $key, $default = null)
    {
        $this->loadData($key);
        return Arr::get($this->data, $key, $default);
    }

    public function has(string $key)
    {
        $this->loadData($key);
        return Arr::has($this->data, $key);
    }

    private function loadData(string $file)
    {
        $filename = \explode('.', $file)[0];
        if (isset($this->data[$filename])) {
            return;
        }
        $filepath = \sprintf('%s/%s.php', $this->path, $filename);
        try {
            $configData = include $filepath;
            $this->data[$filename] = $configData;
        } catch (\Throwable $th) {
            $this->data[$filename] = [];
        }
    }
}
