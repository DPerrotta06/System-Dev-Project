<?php
/*This class is very useful for filling out the calendar template with the different dates and times of client events*/

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RedBeanPHP\R;
use Twig\Environment;

class EventController
{
    //constructor with Twig and base path for URL generation
    public function __construct(
        private Environment $twig,
        private string $basePath,
    ) {}

    // GET /events 
    // list all events, optionally filtered by status.
    public function index(Request $request, Response $response): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) { //check authentication
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302); //redirect to auth if not authenticated
        }
        $params = $request->getQueryParams(); //get query parameters
        $status = $params['status'] ?? null; //get status filter if provided
        if ($status) {
            $events = R::getAll( //fetch events filtered by status
                'SELECT * FROM v_event_summary WHERE status = ? ORDER BY eventDate ASC',
                [$status]
            );
        } else {
            $events = R::getAll('SELECT * FROM v_event_summary ORDER BY eventDate ASC');
        }
        $html = $this->twig->render('event_index.html.twig', [
            'events'     => $events,
            'status'     => $status,
            'base_path'  => $this->basePath,
            'app_lang'   => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // GET /events/{id} 
    // full detail view for a single event.
    public function show(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }
        $event = R::getRow(
            'SELECT * FROM v_event_summary WHERE eventId = ?',
            [(int) $args['id']]
        );
        if (!$event) {
            $response->getBody()->write('Event not found.');
            return $response->withStatus(404);
        }
        $services = R::getAll(
            'SELECT * FROM v_event_services WHERE eventId = ?',
            [(int) $args['id']]
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

    //  POST /events/{id}/status 
    // update event status (Confirmed, Cancelled, etc.).
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }
        $data   = (array) $request->getParsedBody();
        $status = trim($data['status'] ?? '');
        $allowed = ['Pending', 'Confirmed', 'Cancelled', 'Completed', 'Declined'];
        if (!in_array($status, $allowed)) {
            return $response->withHeader('Location', $this->basePath . '/events')->withStatus(302);
        }
        $event = R::load('event', (int) $args['id']);
        if ($event->id) {
            $event->status = $status;
            R::store($event);
        }
        return $response
            ->withHeader('Location', $this->basePath . '/events/' . $args['id'])
            ->withStatus(302);
    }


    //  POST /events/{id}/delete 
    // update event status (Confirmed, Cancelled, etc.).
    public function delete(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }
        $id = (int) $args['id'];
        R::exec('DELETE FROM eventService WHERE eventId = ?', [$id]);
        R::exec('DELETE FROM payment     WHERE eventId = ?', [$id]);
        $event = R::load('event', $id);
        if ($event->id) {
            R::trash($event);
        }
        return $response
            ->withHeader('Location', $this->basePath . '/events')
            ->withStatus(302);
    }
}
