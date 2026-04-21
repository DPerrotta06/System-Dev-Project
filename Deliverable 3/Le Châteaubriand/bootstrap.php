<?php
require __DIR__ . '/vendor/autoload.php';
use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container = new Container();
AppFactory::setContainer($container);
$app->addErrorMiddleWare(true, true, true);
$app->addBodyParsingMiddleware();
$app->get('/test', function ($request, $response) {
    $response->getBody()->write('Slim is set!');
    return $response;
});
$app->run();
?>