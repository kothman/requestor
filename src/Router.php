<?php
/**
 * src/Router.php
 *
 * Morgan Kothman <abkothman@gmail.com>
 *
 * Handles determining which Controller and View are used for a requested route
 */
namespace Kothman\Requestor;

class Router
{
    protected array $routes = [];
    
    public function match(string $pathname, string $controllerClassName): void
    {
        $this->routes[] = new Route($pathname, $controllerClassName);
    }

    public function setRoutesFromFile(string $filepath): void
    {
        $routes = require_once $filepath;
        foreach($routes as $r) {
            $this->match(pathname: $r[0], controllerClassName: $r[1]);
        }
    }
}
