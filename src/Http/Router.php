<?php

namespace Rareloop\Lumberjack\Http;

use Rareloop\Router\Route;
use Rareloop\Router\Router as RareRouter;

class Router extends RareRouter
{
    private $defaultControllerNamespace = 'App\Http\Controllers\\';

    /**
     * Map a router action to a set of Http verbs and a URI
     *
     * @param  callable|string $callback
     */
    public function map(array $verbs, string $uri, $callback): Route
    {
        if ($this->isControllerString($callback)) {
            $callback = $this->normaliseCallbackString($callback);
        }

        return parent::map($verbs, $uri, $callback);
    }

    /**
     * Is the provided callback action a Controller string
     *
     * @param  mixed  $callback
     * @return boolean
     */
    private function isControllerString($callback): bool
    {
        return \is_string($callback) && \strpos($callback, '@') !== false;
    }

    /**
     * Add the default namespace to the Controller classname if required
     */
    private function normaliseCallbackString(string $callback): string
    {
        @list($controller, $method) = \explode('@', $callback);

        if (\class_exists($this->defaultControllerNamespace . $controller)) {
            return $this->defaultControllerNamespace . $callback;
        }

        return $callback;
    }
}
