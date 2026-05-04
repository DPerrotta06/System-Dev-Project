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

    // GET /clients 
    // list all clients, with optional name search.
    public function index(Request $request, Response $response): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $params = $request->getQueryParams();
        $search = trim($params['search'] ?? '');

        if ($search !== '') {
            $like    = '%' . $search . '%';
            $clients = R::getAll(
                'SELECT * FROM client
                  WHERE firstName LIKE ? OR lastName LIKE ? OR email LIKE ?
                  ORDER BY lastName, firstName',
                [$like, $like, $like]
            );
        } else {
            $clients = R::getAll('SELECT * FROM client ORDER BY lastName, firstName');
        }

        $html = $this->twig->render('clients/index.html.twig', [
            'clients'   => $clients,
            'search'    => $search,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // GET /clients/{id} 
    // client detail — profile + all their events + payment info.
    public function show(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $client = R::load('client', (int) $args['id']);
        if (!$client->id) {
            $response->getBody()->write('Client not found.');
            return $response->withStatus(404);
        }

        $events = R::getAll(
            'SELECT * FROM v_event_summary WHERE clientId = ? ORDER BY eventDate DESC',
            [(int) $args['id']]
        );

        $html = $this->twig->render('clients_show.html.twig', [
            'client'    => $client->export(),
            'events'    => $events,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // GET /clients/{id}/edit 
    // show edit form for a client.
    public function edit(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $client = R::load('client', (int) $args['id']);
        if (!$client->id) {
            $response->getBody()->write('Client not found.');
            return $response->withStatus(404);
        }

        $html = $this->twig->render('clients/edit.html.twig', [
            'client'    => $client->export(),
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // POST /clients/{id}/edit 
    // save edits to a client record.
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $data   = (array) $request->getParsedBody();
        $client = R::load('client', (int) $args['id']);

        if (!$client->id) {
            $response->getBody()->write('Client not found.');
            return $response->withStatus(404);
        }

        $client->firstName   = trim($data['firstName']   ?? $client->firstName);
        $client->lastName    = trim($data['lastName']    ?? $client->lastName);
        $client->email       = trim($data['email']       ?? $client->email);
        $client->phoneNumber = trim($data['phoneNumber'] ?? $client->phoneNumber);

        R::store($client);

        return $response
            ->withHeader('Location', $this->basePath . '/clients/' . $args['id'])
            ->withStatus(302);
    }

    //  POST /clients/{id}/delete 
    // delete a client and cascade their events/payments.
    public function delete(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $id     = (int) $args['id'];
        $events = R::getAll('SELECT eventId FROM event WHERE clientId = ?', [$id]);

        foreach ($events as $event) {
            $eid = (int) $event['eventId'];
            R::exec('DELETE FROM eventService WHERE eventId = ?', [$eid]);
            R::exec('DELETE FROM payment     WHERE eventId = ?', [$eid]);
        }

        R::exec('DELETE FROM event  WHERE clientId = ?', [$id]);

        $client = R::load('client', $id);
        if ($client->id) {
            R::trash($client);
        }

        return $response
            ->withHeader('Location', $this->basePath . '/clients')
            ->withStatus(302);
    }

    public function showClientForm(Request $request, Response $response): Response
    {
        $html = $this->twig->render('client_form.html.twig', [
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}
