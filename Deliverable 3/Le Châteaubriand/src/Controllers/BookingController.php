<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RedBeanPHP\R;
use Twig\Environment;

class BookingController
{
    public function __construct(
        private Environment $twig,
        private string $basePath,
    ) {}

    // GET /booking 
    // show the booking form to a client.
    public function showForm(Request $request, Response $response): Response
    {
        $ballrooms = R::getAll('SELECT ballroomId, roomName, minCapacity, maxCapacity FROM ballroom');
        $menus     = R::getAll('SELECT menuId, menuName, pricePerPerson FROM menu');
        $bars      = R::getAll('SELECT barId, barType, pricePerPerson FROM bar');
        $services  = R::getAll('SELECT serviceId, serviceName, serviceType FROM services');

        $html = $this->twig->render('booking/form.html.twig', [
            'ballrooms'  => $ballrooms,
            'menus'      => $menus,
            'bars'       => $bars,
            'services'   => $services,
            'base_path'  => $this->basePath,
            'app_lang'   => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // POST /booking 
    // handle booking form submission. Creates client, event,
    // payment, and any eventService links in one transaction.
    public function submit(Request $request, Response $response): Response
    {
        $data   = (array) $request->getParsedBody();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $ballrooms = R::getAll('SELECT ballroomId, roomName, minCapacity, maxCapacity FROM ballroom');
            $menus     = R::getAll('SELECT menuId, menuName, pricePerPerson FROM menu');
            $bars      = R::getAll('SELECT barId, barType, pricePerPerson FROM bar');
            $services  = R::getAll('SELECT serviceId, serviceName, serviceType FROM services');

            $html = $this->twig->render('booking/form.html.twig', [
                'errors'     => $errors,
                'old'        => $data,
                'ballrooms'  => $ballrooms,
                'menus'      => $menus,
                'bars'       => $bars,
                'services'   => $services,
                'base_path'  => $this->basePath,
                'app_lang'   => $_SESSION['lang'] ?? 'en',
            ]);
            $response->getBody()->write($html);
            return $response->withStatus(422);
        }

        // Create or retrieve the client 
        $existing = R::findOne('client', 'email = ?', [trim($data['email'])]);
        if ($existing) {
            $client = $existing;
        } else {
            $client              = R::dispense('client');
            $client->firstName   = trim($data['firstName']);
            $client->lastName    = trim($data['lastName']);
            $client->email       = trim($data['email']);
            $client->phoneNumber = trim($data['phoneNumber']);
            R::store($client);
        }

        // Create the event 
        $event              = R::dispense('event');
        $event->clientId    = $client->id;
        $event->ballroomId  = (int) $data['ballroomId'];
        $event->menuId      = !empty($data['menuId'])  ? (int) $data['menuId']  : null;
        $event->barId       = !empty($data['barId'])   ? (int) $data['barId']   : null;
        $event->eventDate   = $data['eventDate'];
        $event->eventTime   = $data['eventTime'];
        $event->guestCount  = (int) $data['guestCount'];
        $event->eventType   = trim($data['eventType']);
        $event->description = trim($data['description'] ?? '');
        $event->status      = 'Pending';
        $eventId = R::store($event);

        // Link any third-party services
        $serviceIds = $data['serviceIds'] ?? [];
        foreach ((array) $serviceIds as $sid) {
            $es            = R::dispense('eventService');
            $es->eventId   = $eventId;
            $es->serviceId = (int) $sid;
            R::store($es);
        }

        // Create a payment record (totals calculated app-side)
        $totalPrice = $this->calculateTotal($data, $eventId);

        $payment                  = R::dispense('payment');
        $payment->eventId         = $eventId;
        $payment->totalPrice      = $totalPrice;
        $payment->depositRequired = round($totalPrice * 0.10, 2); // 10% deposit
        $payment->amountPaid      = 0.00;
        $payment->paymentPlan     = trim($data['paymentPlan'] ?? 'Full');
        $payment->paymentMethod   = trim($data['paymentMethod'] ?? '');
        $payment->nextPaymentDue  = $data['eventDate']; // placeholder
        R::store($payment);

        return $response
            ->withHeader('Location', $this->basePath . '/booking/confirmation/' . $eventId)
            ->withStatus(302);
    }

    // GET /booking/confirmation/{id}
    // confirmation page shown after a successful submission.
    public function confirmation(Request $request, Response $response, array $args): Response
    {
        $event = R::getRow(
            'SELECT * FROM v_event_summary WHERE eventId = ?',
            [(int) $args['id']]
        );

        if (!$event) {
            $response->getBody()->write('Booking not found.');
            return $response->withStatus(404);
        }

        $html = $this->twig->render('booking/confirmation.html.twig', [
            'event'     => $event,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // Private helpers

    private function validate(array $data): array
    {
        $errors = [];

        if (empty(trim($data['firstName'] ?? '')))   $errors['firstName']   = 'First name is required.';
        if (empty(trim($data['lastName']  ?? '')))   $errors['lastName']    = 'Last name is required.';
        if (empty(trim($data['email']     ?? '')))   $errors['email']       = 'Email is required.';
        if (empty(trim($data['phoneNumber'] ?? ''))) $errors['phoneNumber'] = 'Phone number is required.';
        if (empty($data['ballroomId']))              $errors['ballroomId']  = 'Please select a ballroom.';
        if (empty($data['eventDate']))               $errors['eventDate']   = 'Please select a date.';
        if (empty($data['eventTime']))               $errors['eventTime']   = 'Please select a time.';
        if (empty($data['guestCount']))              $errors['guestCount']  = 'Guest count is required.';
        if (empty(trim($data['eventType'] ?? '')))   $errors['eventType']   = 'Event type is required.';

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (!empty($data['eventDate']) && strtotime($data['eventDate']) < strtotime('today')) {
            $errors['eventDate'] = 'Event date must be in the future.';
        }

        return $errors;
    }

    private function calculateTotal(array $data, int $eventId): float
    {
        $total      = 0.0;
        $guestCount = (int) ($data['guestCount'] ?? 0);

        if (!empty($data['menuId'])) {
            $menu   = R::load('menu', (int) $data['menuId']);
            $total += (float) $menu->pricePerPerson * $guestCount;
        }

        if (!empty($data['barId'])) {
            $bar    = R::load('bar', (int) $data['barId']);
            $total += (float) $bar->pricePerPerson * $guestCount;
        }

        return round($total, 2);
    }
}