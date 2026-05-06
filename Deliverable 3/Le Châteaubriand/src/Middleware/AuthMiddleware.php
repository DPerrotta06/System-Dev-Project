<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (!isset($_SESSION['admin'])) {
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/auth')->withStatus(302);
        }
        return $handler->handle($request);
    }
}
