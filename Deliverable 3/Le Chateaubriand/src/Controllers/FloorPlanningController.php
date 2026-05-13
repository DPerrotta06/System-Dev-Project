<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RedBeanPHP\R;
use Twig\Environment;

class FloorPlanningController
{
    public function __construct(
        private Environment $twig,
        private string $basePath,
    ) {}

    // GET /floor-planning 
    // list all ballrooms with their arrangement images.
    public function index(Request $request, Response $response): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $ballrooms = R::getAll('SELECT * FROM ballroom ORDER BY roomName');

        $html = $this->twig->render('floor-planning/index.html.twig', [
            'ballrooms' => $ballrooms,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // GET /floor-planning/{id} 
    // detail view for a single ballroom, arrangement + upcoming events.
    public function show(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $ballroom = R::load('ballroom', (int) $args['id']);
        if (!$ballroom->id) {
            $response->getBody()->write('Ballroom not found.');
            return $response->withStatus(404);
        }

        // Upcoming confirmed events in this ballroom.
        $events = R::getAll(
            "SELECT * FROM v_event_summary
              WHERE ballroomId = ?
                AND eventDate >= DATE('now')
                AND status = 'Confirmed'
              ORDER BY eventDate ASC",
            [(int) $args['id']]
        );

        $html = $this->twig->render('floor-planning/show.html.twig', [
            'ballroom'  => $ballroom->export(),
            'events'    => $events,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // GET /floor-planning/{id}/edit 
    // edit ballroom details and update image paths.
    public function edit(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $ballroom = R::load('ballroom', (int) $args['id']);
        if (!$ballroom->id) {
            $response->getBody()->write('Ballroom not found.');
            return $response->withStatus(404);
        }

        $html = $this->twig->render('floor-planning/edit.html.twig', [
            'ballroom'  => $ballroom->export(),
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // POST /floor-planning/{id}/edit 
    // save ballroom edits.
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $data     = (array) $request->getParsedBody();
        $ballroom = R::load('ballroom', (int) $args['id']);

        if (!$ballroom->id) {
            $response->getBody()->write('Ballroom not found.');
            return $response->withStatus(404);
        }

        $ballroom->roomName        = trim($data['roomName']        ?? $ballroom->roomName);
        $ballroom->minCapacity     = (int) ($data['minCapacity']   ?? $ballroom->minCapacity);
        $ballroom->maxCapacity     = (int) ($data['maxCapacity']   ?? $ballroom->maxCapacity);
        $ballroom->sizeSqFt        = (int) ($data['sizeSqFt']      ?? $ballroom->sizeSqFt);
        $ballroom->picturesPath    = trim($data['picturesPath']    ?? $ballroom->picturesPath);
        $ballroom->arrangementPath = trim($data['arrangementPath'] ?? $ballroom->arrangementPath);

        R::store($ballroom);

        return $response
            ->withHeader('Location', $this->basePath . '/floor-planning/' . $args['id'])
            ->withStatus(302);
    }
}