<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private string $basePath,
    ) {}


    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            return $handler->handle($request);
        } else {
            return $this->responseFactory->createResponse(302)
                ->withHeader('Location', $this->basePath . '/auth')
                ->withStatus(302);
        }
    }
}
