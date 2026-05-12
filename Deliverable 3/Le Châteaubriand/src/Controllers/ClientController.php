<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RedBeanPHP\R;
use Twig\Environment;

class ClientController
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

    private function getClientByClientId(int $clientId): ?array
    {
        $client = R::getRow(
            'SELECT * FROM client WHERE clientId = ?',
            [$clientId]
        );

        return $client ?: null;
    }

    // GET /clients
    // List all clients, with optional name search.
    public function index(Request $request, Response $response): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $params = $request->getQueryParams();
        $search = trim((string) ($params['search'] ?? ''));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $clients = R::getAll(
                'SELECT * FROM client
                 WHERE firstName LIKE ? OR lastName LIKE ? OR email LIKE ?
                 ORDER BY lastName, firstName',
                [$like, $like, $like]
            );
        } else {
            $clients = R::getAll('SELECT * FROM client ORDER BY lastName, firstName');
        }

        $html = $this->twig->render('clients_index.html.twig', [
            'clients'   => $clients,
            'search'    => $search,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // GET /clients/{id}
    // Client detail — profile + all their events.
    public function show(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $clientId = (int) $args['id'];
        $client = $this->getClientByClientId($clientId);

        if (!$client) {
            $response->getBody()->write('Client not found.');
            return $response->withStatus(404);
        }

        $events = R::getAll(
            'SELECT * FROM v_event_summary WHERE clientId = ? ORDER BY eventDate DESC',
            [$clientId]
        );

        $html = $this->twig->render('client_details.html.twig', [
            'client'    => $client,
            'events'    => $events,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // GET /clients/{id}/edit
    // Show edit form for a client.
    public function edit(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $clientId = (int) $args['id'];
        $client = $this->getClientByClientId($clientId);

        if (!$client) {
            $response->getBody()->write('Client not found.');
            return $response->withStatus(404);
        }

        $html = $this->twig->render('client_edit.html.twig', [
            'client'    => $client,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // POST /clients/{id}/edit
    // Save edits to a client record.
    public function update(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $clientId = (int) $args['id'];
        $client = $this->getClientByClientId($clientId);

        if (!$client) {
            $response->getBody()->write('Client not found.');
            return $response->withStatus(404);
        }

        $data = (array) $request->getParsedBody();

        $firstName = trim((string) ($data['firstName'] ?? $client['firstName']));
        $lastName = trim((string) ($data['lastName'] ?? $client['lastName']));
        $email = trim((string) ($data['email'] ?? $client['email']));
        $phoneNumber = trim((string) ($data['phoneNumber'] ?? $client['phoneNumber']));

        R::exec(
            'UPDATE client
             SET firstName = ?, lastName = ?, email = ?, phoneNumber = ?
             WHERE clientId = ?',
            [$firstName, $lastName, $email, $phoneNumber, $clientId]
        );

        return $response
            ->withHeader('Location', $this->basePath . '/clients/' . $clientId)
            ->withStatus(302);
    }

    // POST /clients/{id}/delete
    // Delete a client and cascade their events/payments.
    public function delete(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $clientId = (int) $args['id'];
        $client = $this->getClientByClientId($clientId);

        if (!$client) {
            $response->getBody()->write('Client not found.');
            return $response->withStatus(404);
        }

        $events = R::getAll('SELECT eventId FROM event WHERE clientId = ?', [$clientId]);

        foreach ($events as $event) {
            $eventId = (int) $event['eventId'];
            R::exec('DELETE FROM eventService WHERE eventId = ?', [$eventId]);
            R::exec('DELETE FROM payment WHERE eventId = ?', [$eventId]);
        }

        R::exec('DELETE FROM event WHERE clientId = ?', [$clientId]);
        R::exec('DELETE FROM client WHERE clientId = ?', [$clientId]);

        return $response
            ->withHeader('Location', $this->basePath . '/clients')
            ->withStatus(302);
    }
}
