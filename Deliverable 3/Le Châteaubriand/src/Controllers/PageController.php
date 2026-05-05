<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class PageController
{
    public function __construct(
        private Environment $twig,
        private string $basePath
    ) {}

    public function showLandingPage(Request $request, Response $response): Response
    {
        $html = $this->twig->render('landing_page.html.twig', [
            'step' => '/',
            'base_path' => $this->basePath,
            'app_lang' => $_SESSION['lang'] ?? 'en'
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function showFaq(Request $request, Response $response): Response
    {
        $html = $this->twig->render('faq.html.twig', [
            'base_path' => $this->basePath,
            'app_lang' => $_SESSION['lang'] ?? 'en'
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function reviews(Request $request, Response $response):Response{
        $html = $this->twig->render('reviews.html.twig', [
            'base_path' => $this->basePath,
            'app_lang' => $_SESSION['lang'] ?? 'en'
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}
