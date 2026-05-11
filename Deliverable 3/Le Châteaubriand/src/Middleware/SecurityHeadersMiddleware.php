<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    private array $headers;
    public function __construct(array $extra = [])
    {
        $this->headers = array_merge([
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:",
        ], $extra);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }
}
