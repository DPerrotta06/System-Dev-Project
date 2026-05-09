<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RedBeanPHP\R;
use Twig\Environment;

class AdminController
{
    public function __construct(
        private Environment $twig,
        private string $basePath,
    ) {}

    // GET /admin 
    // Main dashboard: stats + upcoming events + pending queue.
    public function dashboard(Request $request, Response $response): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        // Stats bar
        $stats = [
            'pending' => R::getCell("SELECT COUNT(*) FROM event WHERE status = 'Pending'"),
            'confirmedMonth' => R::getCell(
                "SELECT COUNT(*) FROM event
                  WHERE status = 'Confirmed'
                    AND strftime('%Y-%m', eventDate) = strftime('%Y-%m', 'now')"
            ),
            'totalClients' => R::getCell("SELECT COUNT(*) FROM client"),
            'totalEvents' => R::getCell("SELECT COUNT(*) FROM event"),
        ];

        //  Upcoming confirmed events (next 10) 
        $upcoming = R::getAll(
            "SELECT * FROM v_event_summary
              WHERE status = 'Confirmed'
                AND eventDate >= DATE('now')
              ORDER BY eventDate ASC
              LIMIT 10"
        );

        //  Pending bookings needing a decision
        $pending = R::getAll(
            "SELECT * FROM v_event_summary
              WHERE status = 'Pending'
              ORDER BY eventDate ASC"
        );

        $html = $this->twig->render('admin_dashboard.html.twig', [
            'stats'     => $stats,
            'upcoming'  => $upcoming,
            'pending'   => $pending,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    //  GET /admin/calendar 
    // Monthly calendar view of all events.
    public function calendar(Request $request, Response $response): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $params = $request->getQueryParams();

        // Default to current year/month
        $year  = (int) ($params['year']  ?? date('Y'));
        $month = (int) ($params['month'] ?? date('m'));

        // Clamp month to valid range
        if ($month < 1) {
            $month = 12;
            $year--;
        }
        if ($month > 12) {
            $month = 1;
            $year++;
        }

        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from)); // last day of the month

        $events = R::getAll(
            "SELECT eventId, eventDate, eventTime, eventType, clientName,
                    ballroom, guestCount, status
               FROM v_event_summary
              WHERE eventDate BETWEEN ? AND ?
              ORDER BY eventDate ASC, eventTime ASC",
            [$from, $to]
        );

        // Group events by date for easy rendering in the template
        $byDate = [];
        foreach ($events as $event) {
            $byDate[$event['eventDate']][] = $event;
        }

        // Previous and next month for navigation links
        $prevMonth = $month === 1  ? 12 : $month - 1;
        $prevYear  = $month === 1  ? $year - 1 : $year;
        $nextMonth = $month === 12 ? 1  : $month + 1;
        $nextYear  = $month === 12 ? $year + 1 : $year;

        $html = $this->twig->render('admin/calendar.html.twig', [
            'byDate'    => $byDate,
            'year'      => $year,
            'month'     => $month,
            'monthName' => date('F', mktime(0, 0, 0, $month, 1, $year)),
            'daysInMonth' => (int) date('t', strtotime($from)),
            'firstDayOfWeek' => (int) date('N', strtotime($from)), // 1=Mon, 7=Sun
            'prevYear'  => $prevYear,
            'prevMonth' => $prevMonth,
            'nextYear'  => $nextYear,
            'nextMonth' => $nextMonth,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    //  GET /admin/payments 
    // Payment overview — all events with outstanding balances.
    public function payments(Request $request, Response $response): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $payments = R::getAll(
            "SELECT * FROM v_event_summary
              WHERE totalPrice IS NOT NULL
              ORDER BY nextPaymentDue ASC, eventDate ASC"
        );

        // Separate fully paid from outstanding
        $outstanding = array_filter($payments, fn($p) => ($p['amountLeft'] ?? 0) > 0);
        $paid        = array_filter($payments, fn($p) => ($p['amountLeft'] ?? 0) <= 0);

        $html = $this->twig->render('admin/payments.html.twig', [
            'outstanding' => array_values($outstanding),
            'paid'        => array_values($paid),
            'base_path'   => $this->basePath,
            'app_lang'    => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}
