<?php
namespace Kothman\Requestor\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class AbstractController
{
    public function __construct(
        protected Request $request,
        protected \Twig\Environment $twig,
        protected Session $session,
    )
    {

    }
}
