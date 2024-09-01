<?php
namespace Kothman\Requestor\Controller;

class DashboardController extends AbstractController
{

    
    public function index()
    {
        return $this->twig->render('dashboard.html', [ 'debug' => $_ENV['DEBUG'] ]);
    }
}
