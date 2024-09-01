<?php
namespace Kothman\Requestor;

use Twig\Environment;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Session\Session;

require_once __DIR__.'/../vendor/autoload.php';

class App {

    protected Request $request;
    protected Response $response;
    
    public function __construct(
        protected Environment $twig,
        protected EntityManager $entityManager,
        protected RouteCollection $routes,
        protected Session $session,
    )
    {
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
    }

    public function run()
    {
        $controllerResponse = $this->dispatchController();
        if (is_a($controllerResponse, Request::class) || is_subclass_of($controllerResponse, Response::class)) {
            $this->response = $controllerResponse;
        } else {
            $this->response->setContent( (string) $controllerResponse);
        }
        return $this->response->send();
        /*echo
            (new Controller\DashboardController(
                Request::createFromGlobals(),
                $this->twig))
                ->index();*/
    }

    protected function dispatchController()
    {
        
        $context = (new RequestContext())->fromRequest($this->request);
        $matcher = new UrlMatcher($this->routes, $context);
        $parameters = $matcher->match($context->getPathInfo());
        
        $controllerInfo = $parameters['_controller'];
        
        $controller = new $controllerInfo['class']($this->request, $this->twig, $this->session);
        $action = $controllerInfo['action'];
        
        return $controller->$action();
    }
}
