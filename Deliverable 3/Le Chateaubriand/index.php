<?php

declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);
session_start();


use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Middleware\AuthMiddleware;
use App\Middleware\SessionTimeoutMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use RedBeanPHP\R;
use Slim\Factory\AppFactory;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Twig\TwigFilter;
use App\Controllers\BookingController;
use App\Controllers\EventController;
use App\Controllers\ClientController;
use App\Controllers\MenuController;
use App\Controllers\FloorPlanningController;
use App\Services\OTPService as ServicesOTPService;

require __DIR__ . '/vendor/autoload.php';


//DATABASE ──────────────────────────────────────────────────────────────
$dbPath = __DIR__ . '/var/chateaubriand3.db';
R::setup('sqlite:' . $dbPath);
R::exec('PRAGMA foreign_keys = OFF;');
R::freeze(false);


//TEMPLATE ENGINE ───────────────────────────────────────────────────────
$loader = new FilesystemLoader(__DIR__ . '/src/Templates');
$twig = new Environment($loader, ['cache' => false, 'auto_reload' => true,]);


//I18N — symfony/translation ───────────────────────────────────────────
$translator = new Translator('en'); //english
$translator->addLoader('array', new ArrayLoader);
$translator->addResource('array', require __DIR__ . '/translations/messages.en.php', 'en');
$translator->addResource('array', require __DIR__ . '/translations/messages.fr.php', 'fr');
$twig->addFunction(new TwigFunction('trans', function (string $key, array $params = []) use ($translator) {
    $locale = $_SESSION['lang'] ?? 'en';
    return $translator->trans($key, $params, null, $locale);
}));
$twig->addFilter(new TwigFilter('trans', function (string $key, array $params = []) use ($translator) {
    $locale = $_SESSION['lang'] ?? 'en';
    return $translator->trans($key, $params, null, $locale);
}));


//DEPENDENCY INJECTION CONTAINER ───────────────────────────────────────
$basePath = '';
$container = new \DI\Container();
$container->set(Environment::class, $twig);
$container->set(AuthController::class, fn() => new AuthController(
    $twig, // the Twig environment created above
    new ServicesOTPService(), // a new OtpService instance
    $basePath // the $basePath string
));

// Register other controllers in the container
$container->set(AdminController::class, fn() => new AdminController($twig, $basePath));
$container->set(BookingController::class, fn() => new BookingController($twig, $basePath));
$container->set(ClientController::class, fn() => new ClientController($twig, $basePath));
$container->set(EventController::class, fn() => new EventController($twig, $basePath));
$container->set(FloorPlanningController::class, fn() => new FloorPlanningController($twig, $basePath));
$container->set(MenuController::class, fn() => new MenuController($twig, $basePath));
$container->set(PageController::class, fn() => new PageController($twig, $basePath));


//APPLICATION ───────────────────────────────────────────────────────────
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->setBasePath($basePath);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);


//MIDDLEWARE ────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/var/app.log';

$loggerMiddleware = function (Request $request, RequestHandler $handler) use ($logFile) {
    $start = microtime(true);
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    $response = $handler->handle($request);
    $status = $response->getStatusCode();
    $elapsed = round((microtime(true) - $start) * 1000);
    $line = sprintf(
        "[%s] %-6s %-25s → %d  (%dms)\n",
        date('Y-m-d H:i:s'),
        $method,
        $path,
        $status,
        $elapsed
    );
    file_put_contents($logFile, $line, FILE_APPEND);
    return $response;
};
$app->add($loggerMiddleware);
$app->add(new MaintenanceMiddleware(
    flagFile: __DIR__ . '/var/maintenance.flag',
    responseFactory: $app->getResponseFactory()
));
$app->add(new SecurityHeadersMiddleware());


//HTML ROUTES ───────────────────────────────────────────────────────────
$app->get('', function ($req, $res) use ($basePath) {
    return $res->withHeader('Location', $basePath . '/')->withStatus(302);
});

//PUBLIC ROUTES THAT ACCESSIBLE TO ANYONE
$app->get('/', [PageController::class, 'showLandingPage']);
$app->get('/faq', [PageController::class, 'showFaq']);
$app->get('/client-form', [BookingController::class, 'showClientForm']);
$app->post('/table_plan', [BookingController::class, 'goToTablePlanning']);
$app->post('/table_plan/submit', [BookingController::class, 'submitFloorPlan']);

// Public booking routes
$app->get('/booking', [BookingController::class, 'showForm']);
$app->post('/booking', [BookingController::class, 'submit']);
$app->get('/booking/confirmation/{id}', [BookingController::class, 'confirmation']);

// Event, client, menu and floor-planning (ADMIN) THESE ARE ALL PROTECTED BY THE MIDDLEWARE TO PREVENT ANYONE EXCEPT THE ADMINS FROM MANIPULATING AND ACCESSING THE DATA
// grouped the admin routes for better organization and security 
$app->group('', function ($group) {

    $group->get('/admin', [AdminController::class, 'dashboard']);
    $group->get('/calendar', [AdminController::class, 'calendar']);
    $group->get('/events', [EventController::class, 'index']);
    $group->get('/events/{id}', [EventController::class, 'show']);
    $group->post('/events/{id}/status', [EventController::class, 'updateStatus']);
    $group->post('/events/{id}/delete', [EventController::class, 'delete']);
    $group->get('/clients', [ClientController::class, 'index']);
    $group->get('/clients/{id}', [ClientController::class, 'show']);
    $group->get('/clients/{id}/edit', [ClientController::class, 'edit']);
    $group->post('/clients/{id}/edit', [ClientController::class, 'update']);
    $group->post('/clients/{id}/delete', [ClientController::class, 'delete']);
    $group->get('/menus', [MenuController::class, 'index']);
    $group->get('/menus/{id}', [MenuController::class, 'show']);
    $group->get('/menus/{id}/edit', [MenuController::class, 'edit']);
    $group->post('/menus/{id}/edit', [MenuController::class, 'update']);
    $group->get('/floor-planning', [FloorPlanningController::class, 'index']);
    $group->get('/floor-planning/{id}', [FloorPlanningController::class, 'show']);
    $group->get('/floor-planning/{id}/edit', [FloorPlanningController::class, 'edit']);
    $group->post('/floor-planning/{id}/edit', [FloorPlanningController::class, 'update']);
})->add(new SessionTimeoutMiddleware($basePath))
    ->add(new AuthMiddleware(
        responseFactory: $app->getResponseFactory(),
        basePath: $basePath
    ));

//GOOGLE REVIEWS ROUTE
$app->get('/reviews', function (Request $request, Response $response) use ($twig, $basePath) {
    $html = $twig->render('reviews.html.twig', ['base_path' => $basePath, 'app_lang' => $_SESSION['lang'] ?? 'en']);
    $response->getBody()->write($html);
    return $response;
});


/* ───────── LANGUAGE SWITCH ───────── */
$app->get('/lang/{locale}', function (Request $request, Response $response, array $args) use ($basePath) {

    $allowed = ['en', 'fr'];

    if (in_array($args['locale'], $allowed)) {
        $_SESSION['lang'] = $args['locale'];
    }

    $referer = $request->getHeaderLine('Referer');
    $redirect = $referer ?: $basePath . '/';

    return $response
        ->withHeader('Location', $redirect)
        ->withStatus(302);
});


//AUTH ROUTES FOR LOGGING IN AND OUT ───────────────────────────────────────────────────────────
$app->get('/auth', [AuthController::class, 'showForm']);
$app->post('/auth/request', [AuthController::class, 'requestOtp']);
$app->get('/auth/verify', [AuthController::class, 'showVerify']);
$app->post('/auth/verify',  [AuthController::class, 'verifyOtp']);
$app->post('/auth/logout',  [AuthController::class, 'logout']);


//OTP ──────────────────────────────────────────────────────────────────
$app->get('/otp', function (Request $request, Response $response) {
    $tfa = new \RobThree\Auth\TwoFactorAuth(
        new \RobThree\Auth\Providers\Qr\BaconQrCodeProvider(
            4,
            '#ffffff',
            '#000000',
            'svg'
        ),
        'Le Châteaubriand'
    );
    $secret = $tfa->createSecret();
    $code = $tfa->getCode($secret);   // current 30-second TOTP code
    $out = "Secret : $secret\n"
        . "Current code : $code\n"
        . "verify(correct) : " . ($tfa->verifyCode($secret, $code)    ? 'true ✓' : 'false ✗') . "\n"
        . "verify('000000') : " . ($tfa->verifyCode($secret, '000000') ? 'true ✓' : 'false ✗') . "\n";
    $response->getBody()->write('<pre style="font-size:1.2rem;padding:2rem">' . htmlspecialchars($out) . '</pre>');
    return $response;
});


//RUN ──────────────────────────────────────────────────────────────────
$app->run();
