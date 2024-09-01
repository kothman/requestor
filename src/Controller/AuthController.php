<?php

namespace Kothman\Requestor\Controller;

require_once __DIR__.'/../../vendor/autoload.php';

class AuthController extends AbstractController {
    protected bool $authenticated = false;
    
    
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function handleAuthentication(): Response {

    }

    
    
}
