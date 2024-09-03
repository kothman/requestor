<?php
namespace Kothman\Requestor;

use Kothman\Requestor\Security\OAuth2Authenticator;
use Kothman\Requestor\Controller\AbstractController as Controller;
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

    protected Response $response;
    protected Controller $controller;
    protected string $controllerAction;
    protected OAuth2Authenticator $authenticator;
    
    public function __construct(
        protected Environment $twig,
        protected EntityManager $entityManager,
        protected RouteCollection $routes,
        protected Session $session,
        protected Request $request,
    )
    {
        $this->response = new Response();
        $this->authenticator = new OAuth2Authenticator(
            $this->session, $this->request, $_ENV,
        );
    }

    public function run()
    {
        // Every request should first be authenticated.
        // The authenticator should only return a Response object if the user isn't completely authenticated
        $authenticatorResponse = $this->authenticator->authenticate();
        if ( null !== $authenticatorResponse) return $authenticatorResponse->send();
        
        // Matches the route with a controller and action, and saves data for request lifecycle
        $this->matchRouteToControllerFromRequest();
        // Gets the response data from dispatched controller
        $controllerResponse = $this->dispatchController();
        if (is_a($controllerResponse, Request::class) || is_subclass_of($controllerResponse, Response::class)) {
            $this->response = $controllerResponse;
        } else {
            $this->response->setContent( (string) $controllerResponse);
        }
        return $this->response->send();
    }

    protected function matchRouteToControllerFromRequest(): void
    {
        $context = (new RequestContext())->fromRequest($this->request);
        $matcher = new UrlMatcher($this->routes, $context);
        $parameters = $matcher->match($context->getPathInfo());
        
        $controllerInfo = $parameters['_controller'];
        
        $this->controller = new $controllerInfo['class']($this->request, $this->twig, $this->session);
        $this->action = $controllerInfo['action'];
    }

    protected function dispatchController()
    {
        return $this->controller->{$this->action}();
    }

}
