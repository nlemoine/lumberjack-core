<?php

namespace Rareloop\Lumberjack\Router;

use League\Route\Router as LeagueRouter;

class Router extends LeagueRouter {

    public function generate($name, $arguments = [], $relative = false) {
        $route = $this->getNamedRoute($name);
        return user_trailingslashit($route->getPath($arguments));
    }
}
