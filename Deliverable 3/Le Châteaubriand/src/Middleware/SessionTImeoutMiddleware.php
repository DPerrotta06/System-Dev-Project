<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionTimeoutMiddleware
{
    private int $timeout = 1800; //30 minutes in seconds

    public function __construct(private string $basePath) {}

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if(isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $last = $_SESSION['last_activity'] ?? null;

            if($last && (time() - $last) > $this->timeout) {
                // timed out - destroy session and redirect to login
                session_destroy();
                $response = new \Slim\Psr7\Response();
                return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
            }

            //update last activity time
            $_SESSION['last_activity'] = time();
        }
        return $handler->handle($request);
    }
}