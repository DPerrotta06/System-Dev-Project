<?php

declare(strict_types=1);
session_start();

use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Middleware\AuthMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Services\OtpService;
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
use App\Controllers\BookingController;
use App\Controllers\EventController;
use App\Controllers\ClientController;
use App\Controllers\MenuController;
use App\Controllers\FloorPlanningController;
use App\Controllers\GoogleReviewsController;

require __DIR__ . '/vendor/autoload.php';


//DATABASE ──────────────────────────────────────────────────────────────
$dbPath = __DIR__ . '/var/chateaubriand.sql'; 
R::setup('sqlite', $dbPath);
R::exec('PRAGMA foreign_keys = ON;');       
R::freeze(false);


//TEMPLATE ENGINE ───────────────────────────────────────────────────────
$loader = new FilesystemLoader(__DIR__ . '/templates');
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


//DEPENDENCY INJECTION CONTAINER ───────────────────────────────────────
$basePath = '/Le Châteaubriand';
$container = new \DI\Container();
$container->set(Environment::class, $twig);
$container->set(AuthController::class, fn() => new AuthController(
    $twig, // the Twig environment created above
    new OtpService(), // a new OtpService instance
    $basePath // the $basePath string
));

// Register other controllers in the container
$container->set(EventController::class,        fn() => new EventController($twig, $basePath));
$container->set(ClientController::class,       fn() => new ClientController($twig, $basePath));
$container->set(BookingController::class,      fn() => new BookingController($twig, $basePath));
$container->set(MenuController::class,         fn() => new MenuController($twig, $basePath));
$container->set(FloorPlanningController::class,fn() => new FloorPlanningController($twig, $basePath));
$container->set(GoogleReviewsController::class,fn() => new GoogleReviewsController());


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
$app->get('/', [PageController::class, 'showLandingPage']);

// Public booking routes
$app->get('/booking', [BookingController::class, 'showForm']);
$app->post('/booking', [BookingController::class, 'submit']);
$app->get('/booking/confirmation/{id}', [BookingController::class, 'confirmation']);

// Event, client, menu and floor-planning (admin)
$app->get('/events', [EventController::class, 'index']);
$app->get('/events/{id}', [EventController::class, 'show']);
$app->post('/events/{id}/status', [EventController::class, 'updateStatus']);
$app->post('/events/{id}/delete', [EventController::class, 'delete']);

$app->get('/clients', [ClientController::class, 'index']);
$app->get('/clients/{id}', [ClientController::class, 'show']);
$app->get('/clients/{id}/edit', [ClientController::class, 'edit']);
$app->post('/clients/{id}/edit', [ClientController::class, 'update']);
$app->post('/clients/{id}/delete', [ClientController::class, 'delete']);

$app->get('/menus', [MenuController::class, 'index']);
$app->get('/menus/{id}', [MenuController::class, 'show']);
$app->get('/menus/{id}/edit', [MenuController::class, 'edit']);
$app->post('/menus/{id}/edit', [MenuController::class, 'update']);

$app->get('/floor-planning', [FloorPlanningController::class, 'index']);
$app->get('/floor-planning/{id}', [FloorPlanningController::class, 'show']);
$app->get('/floor-planning/{id}/edit', [FloorPlanningController::class, 'edit']);
$app->post('/floor-planning/{id}/edit', [FloorPlanningController::class, 'update']);

// Reviews: render via GoogleReviewsController helper
$app->get('/review', function (Request $request, Response $response) use ($twig, $basePath) {
    $reviewsCtrl = new GoogleReviewsController();
    $data = $reviewsCtrl->getReviews();
    $html = $twig->render('reviews.html.twig', ['reviews' => $data, 'base_path' => $basePath, 'app_lang' => $_SESSION['lang'] ?? 'en']);
    $response->getBody()->write($html);
    return $response;
});


//LANGUAGE ROUTE ────────────────────────────────────────────────────────
$app->get('/lang/{locale}', function (Request $request, Response $response, array $args) use ($basePath) {
    $allowed = ['en', 'fr'];
    if (in_array($args['locale'], $allowed)) {
        $_SESSION['lang'] = $args['locale'];
    }
    return $response->withHeader('Location', $basePath . '/')->withStatus(302); //REDIRECTION
});


//AUTH ROUTES ───────────────────────────────────────────────────────────
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
