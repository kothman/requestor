<?php
// bootstrap.php
namespace Kothman\Requestor;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/* Inlude Composer libraries */
require_once __DIR__.'/../vendor/autoload.php';

/* Log all errors, warnings, info, etc */
error_reporting(E_ALL);

/* Local .env variables */
$env = (new Dotenv())->load(__DIR__.'/../.env');
$isDevMode = isset($_ENV['DEBUG']) && !empty($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true';

/* Display errors in debug mode */
if ($isDevMode) {
    ini_set('display_errors', 'On');
    Debug::enable();
}

/* Make sure the timezone is set correctly */
date_default_timezone_set('America/Detroit');

/* Setup the user session */
$storage = new NativeSessionStorage([
    'cookie_secure' => 'auto',
    'cookie_samesite' => Cookie::SAMESITE_LAX,
]);
$session = new Session($storage);
$session->start();

// create the Request object
$request = Request::createFromGlobals();
// creates a RequestStack object using the current request
$requestStack = new RequestStack();
$requestStack->push($request);


/* Create a simple "default" Doctrine ORM configuration for Attributes */
$config = ORMSetup::createAttributeMetadataConfiguration(
    /* Where the models are stored */
    paths: [__DIR__.'/../src/Entity'],
    /* Enable dev mode as long as .env DEBUG === true */
    isDevMode: $isDevMode,
    
);

// configuring the database connection
$dsnParser = new DsnParser();
$connection = DriverManager::getConnection($dsnParser->parse($_ENV['DATABASE_URL']), $config);

// obtaining the entity manager
$entityManager = new EntityManager($connection, $config);

// configure CSRF protection for the FormFactory
$csrfGenerator = new UriSafeTokenGenerator();
$csrfStorage = new SessionTokenStorage($requestStack);
$csrfManager = new CsrfTokenManager($csrfGenerator, $csrfStorage);

$formFactory = Forms::createFormFactoryBuilder()
    ->addExtension(new HttpFoundationExtension())
    ->addExtension(new CsrfExtension($csrfManager))
    ->getFormFactory();

// configure the form component and twig extension
$defaultFormTheme = 'form_div_layout.html.twig';
// setup the twig template manager
$cache = $isDevMode ? false : __DIR__.'/../cache';
$loader = new \Twig\Loader\FilesystemLoader([
    __DIR__.'/../resources/templates',
    __DIR__.'/../vendor/symfony/twig-bridge/Resources/views/Form'
]);
$twig = new \Twig\Environment($loader, [
    'cache' => $cache,
    'debug' => $isDevMode,
    'strict_variables' => $isDevMode
]);
// setup twig extensions
$formEngine = new TwigRendererEngine([$defaultFormTheme], $twig);
$twig->addRuntimeLoader(new \Twig\RuntimeLoader\FactoryRuntimeLoader([
    FormRenderer::class => function () use ($formEngine, $csrfManager): FormRenderer {
        return new FormRenderer($formEngine, $csrfManager);
    },
]));
// add the FormExtension to twig
$twig->addExtension(new FormExtension());
if ($isDevMode)
    $twig->addExtension(new \Twig\Extension\DebugExtension());
// setup the Router - all routes should be defined in config/routes.php
$routes = require_once __DIR__.'/../config/routes.php';

$app = new App($twig, $entityManager, $routes, $session, $request);
$app->run();

/* Save and close the session */
$session->save();
