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

        $html = $this->twig->render('booking/client_form.html.twig', [
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

            $html = $this->twig->render('booking/client_form.html.twig', [
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
            $client->firstName   = trim($data['firstname']);
            $client->lastName    = trim($data['lastname']);
            $client->email       = trim($data['email']);
            $client->phoneNumber = trim($data['phone']);
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
        if (empty(trim($data['firstname'] ?? '')))   $errors['firstname']   = 'First name is required.';
        if (empty(trim($data['lastname']  ?? '')))   $errors['lastname']    = 'Last name is required.';
        if (empty(trim($data['email']     ?? '')))   $errors['email']       = 'Email is required.';
        if (empty(trim($data['phone'] ?? ''))) $errors['phone'] = 'Phone number is required.';
        if (empty($data['eventDate']))               $errors['event_date']   = 'Please select a date.';
        if (empty($data['eventTime']))               $errors['event_time']   = 'Please select a time.';
        if (empty($data['guestCount']))              $errors['guest_count']  = 'Guest count is required.';
        if (empty(trim($data['eventType'] ?? '')))   $errors['event_type']   = 'Event type is required.';
        if(!empty($data['email']) && FILTER_VALIDATE_EMAIL){
            $errors['email'] = 'Please enter a valid email address.';
        }
        if(!empty($data['eventDate'])){
            $currentDate = \DateTime::createFromFormat('Y-m-d', $data['eventDate']);
            $validDate = $currentDate && $currentDate->format('Y-m-d') === $data['eventDate'];
            if (!$validDate) {
                $errors['eventDate'] = 'Please enter a valid date.';
            } elseif ($currentDate < new \DateTime('today')) {
                $errors['eventDate'] = 'Event date cannot be in the past.';
            }
        }
        return $errors;
    }

    private function calculateTotal(array $data, int $eventId): float
    {
        $total = 0.0;
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

    public function showClientForm(Request $request, Response $response): Response
    {
        $foodItems = R::getAll('
        SELECT fi.itemName, fi.extraPrice, fc.categoryName, m.menuName
        FROM fooditem fi
        JOIN foodcategory fc ON fi.itemCategory = fc.categoryId
        JOIN menufooditem mfi ON fi.itemId = mfi.itemId
        JOIN menu m ON mfi.menuId = m.menuId
        ORDER BY m.menuId, fc.categoryName, fi.itemName
    ');
        $menus = ['main' => [], 'buffet' => [], 'midnight' => []];
        foreach ($foodItems as $item) {
            $menuType = match (strtolower(trim($item['menuName']))) {
                'main menu' => 'main',
                'buffet' => 'buffet',
                'midnight table' => 'midnight',
                default => null,
            };
            if ($menuType) {
                $menus[$menuType][$item['categoryName']][] = $item['itemName'];
            }
        }
        $html = $this->twig->render('client_form.html.twig', [
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
            'menus'     => $menus,
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function goToTablePlanning(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        // Get form data
        $numberOfGuests = (int) ($data['number_of_guests'] ?? 1);
        $eventType = $data['event_type'] ?? 'Other';
        // Determine hall based on guest count
        if ($numberOfGuests > 125) {
            $hall = 'grand_salon';
        } elseif ($numberOfGuests > 40) {
            $hall = 'royal';
        } else {
            $hall = 'princess';
        }
        // Calculate approximate table quantity (assuming 10 guests per table)
        $tableQuantity = (int) ceil($numberOfGuests / 10);
        // Store data in session for use in floor planning
        $_SESSION['floor_planning_data'] = [
            'firstname' => $data['firstname'] ?? '',
            'lastname' => $data['lastname'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'event_type' => $eventType,
            'event_date' => $data['event_date'] ?? '',
            'number_of_guests' => $numberOfGuests,
            'notes' => $data['notes'] ?? '',
            'hall' => $hall,
            'table_quantity' => $tableQuantity,
            'guest_quantity' => $numberOfGuests,
        ];
        //render the next page
        $html = $this->twig->render('floor_planning.html.twig', [
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
            'hall' => $hall,
            'table_quantity' => $tableQuantity,
            'guest_quantity' => $numberOfGuests,
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function submitFloorPlan(Request $request, Response $response): Response
    {
        $data    = (array) $request->getParsedBody();
        $session = $_SESSION['floor_planning_data'] ?? null;

        if (!$session) {
            return $response
                ->withHeader('Location', $this->basePath . '/client-form')
                ->withStatus(302);
        }

        //Create or find client
        $existing = R::findOne('client', 'email = ?', [trim($session['email'])]);
        if ($existing) {
            $client = $existing;
        } else {
            $client              = R::dispense('client');
            $client->firstName   = trim($session['firstname']);
            $client->lastName    = trim($session['lastname']);
            $client->email       = trim($session['email']);
            $client->phoneNumber = trim($session['phone']);
            R::store($client);
        }

        //Resolve ballroomId from hall slug
        $hallSlugMap = [
            'royal'       => 'Royal Hall',
            'grand_salon' => 'Grand Salon',
            'princess'    => 'Princess',
        ];
        $roomName   = $hallSlugMap[$session['hall']] ?? 'Princess';
        $ballroom   = R::findOne('ballroom', 'roomName = ?', [$roomName]);
        $ballroomId = $ballroom ? (int) $ballroom->ballroomId : 1;

        //Decode base64 image and store as binary (LONGBLOB)
        $base64Image    = $data['floor_plan_image'] ?? '';
        $imageData      = null;
        if ($base64Image) {
            // Strip the data:image/png;base64, prefix
            $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64Image));
        }

        //Create event
        $event                  = R::dispense('event');
        $event->clientId        = $client->id;
        $event->ballroomId      = $ballroomId;
        $event->eventDate       = $session['event_date'];
        $event->eventTime       = $session['event_time'] ?? '00:00';
        $event->guestCount      = (int) $session['number_of_guests'];
        $event->eventType       = $session['event_type'];
        $event->description     = trim($session['notes'] ?? '');
        $event->status          = 'Pending';
        $event->floorArrangement = $imageData;
        $eventId = R::store($event);

        //Menu selections from session
        $menuSelections = $session['menu_selections'] ?? [];
        if (!empty($menuSelections)) {
            foreach ($menuSelections as $itemName) {
                $link          = R::dispense('eventmenuitem');
                $link->eventId = $eventId;
                $link->itemName = $itemName;
                R::store($link);
            }
        }

        //Payment placeholder
        $payment                  = R::dispense('payment');
        $payment->eventId         = $eventId;
        $payment->totalPrice      = 0.00;
        $payment->depositRequired = 0.00;
        $payment->amountPaid      = 0.00;
        $payment->paymentPlan     = 'Full';
        $payment->paymentMethod   = '';
        $payment->nextPaymentDue  = $session['event_date'];
        R::store($payment);

        //Clean session
        unset($_SESSION['floor_planning_data']);

        return $response
            ->withHeader('Location', $this->basePath . '/?booked=1')
            ->withStatus(302);
    }

    public function showLandingPage(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $html = $this->twig->render('landing_page.html.twig', [
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
            'booked'    => isset($params['booked'])
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}
