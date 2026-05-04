<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class MaintenanceMiddleware
{
    public function __construct(private string $flagFile, private ResponseFactoryInterface $responseFactory) {}
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (file_exists($this->flagFile)) {
            $response = $this->responseFactory->createResponse(503);
            $html = <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Down for Maintenance</title>
                    <style>
                        body { font-family: sans-serif; text-align: center; padding: 4rem; background: #fffbeb; }
                        h1   { color: #92400e; }
                        p    { color: #78350f; }
                    </style>
                </head>
                <body>
                    <h1>🔧 Down for Maintenance</h1>
                    <p>We'll be back shortly. Thanks for your patience.</p>
                </body>
                </html>
                HTML;
            $response->getBody()->write($html);
            return $response->withHeader('Content-Type', 'text/html');
        }
        return $handler->handle($request);
    }
}
