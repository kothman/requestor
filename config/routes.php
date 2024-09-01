<?php
namespace Kothman\Requestor\Controller;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require_once __DIR__.'/../vendor/autoload.php';

$routes = new RouteCollection();
$routes->add('dashboard',
             new Route('/', ['_controller' => [
                 'class' => DashboardController::class, 'action' => 'index']])
);
return $routes;
