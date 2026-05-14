<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RedBeanPHP\R;
use Twig\Environment;

class EventController
{
    public function __construct(
        private Environment $twig,
        private string $basePath,
    ) {}

    private function requireAuth(Response $response): ?Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        return null;
    }

    private function getEventByEventId(int $eventId): ?array
    {
        $event = R::getRow(
            'SELECT * FROM event WHERE eventId = ?',
            [$eventId]
        );

        return $event ?: null;
    }

    // GET /events
    // List all events, optionally filtered by status.
    public function index(Request $request, Response $response): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $params = $request->getQueryParams();
        $status = trim((string) ($params['status'] ?? ''));

        if ($status !== '') {
            $events = R::getAll(
                'SELECT * FROM v_event_summary WHERE status = ? ORDER BY eventDate ASC',
                [$status]
            );
        } else {
            $events = R::getAll('SELECT * FROM v_event_summary ORDER BY eventDate ASC');
        }

        $html = $this->twig->render('event_index.html.twig', [
            'events'    => $events,
            'status'    => $status !== '' ? $status : null,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // GET /events/{id}
    // Full detail view for a single event.
    public function show(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $eventId = (int) $args['id'];

        $event = R::getRow(
            'SELECT * FROM v_event_summary WHERE eventId = ?',
            [$eventId]
        );

        if (!$event) {
            $response->getBody()->write('Event not found.');
            return $response->withStatus(404);
        }

        $services = R::getAll(
            'SELECT * FROM v_event_services WHERE eventId = ?',
            [$eventId]
        );

        $html = $this->twig->render('event_details.html.twig', [
            'event'     => $event,
            'services'  => $services,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // POST /events/{id}/status
    // Update event status using eventId, not RedBean's internal id.
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $eventId = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $status = trim((string) ($data['status'] ?? ''));
        $allowed = ['Pending', 'Confirmed', 'Cancelled', 'Completed', 'Declined'];

        if (!in_array($status, $allowed, true)) {
            return $response->withHeader('Location', $this->basePath . '/events/' . $eventId)->withStatus(302);
        }

        if (!$this->getEventByEventId($eventId)) {
            $response->getBody()->write('Event not found.');
            return $response->withStatus(404);
        }

        R::exec(
            'UPDATE event SET status = ? WHERE eventId = ?',
            [$status, $eventId]
        );

        return $response
            ->withHeader('Location', $this->basePath . '/events/' . $eventId)
            ->withStatus(302);
    }

    // POST /events/{id}/delete
    // Delete an event using eventId, not RedBean's internal id.
    public function delete(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $eventId = (int) $args['id'];

        if (!$this->getEventByEventId($eventId)) {
            $response->getBody()->write('Event not found.');
            return $response->withStatus(404);
        }

        R::exec('DELETE FROM eventService WHERE eventId = ?', [$eventId]);
        R::exec('DELETE FROM payment WHERE eventId = ?', [$eventId]);
        R::exec('DELETE FROM event WHERE eventId = ?', [$eventId]);

        return $response
            ->withHeader('Location', $this->basePath . '/events')
            ->withStatus(302);
    }
}
