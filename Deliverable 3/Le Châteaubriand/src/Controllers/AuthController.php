<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\OtpService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;


/*
Routes handled:
 *   GET  /auth          → showForm()
 *   POST /auth/request  → requestOtp()
 *   GET  /auth/verify   → showVerify()
 *   POST /auth/verify   → verifyOtp()
 *   POST /auth/logout   → logout()
*/

class AuthController
{
    public function __construct(
        private Environment $twig,
        private OtpService $otpService,
        private string $basePath,
    ) {}

    public function showForm(Request $request, Response $response): Response
    {
        $html = $this->twig->render('auth.html.twig', [
            'step' => 'login',
            'base_path' => $this->basePath,
            'app_lang' => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function requestOtp(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        if ($username === '' || $password === '') { // Missing username or password
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $admin = \RedBeanPHP\R::findOne('admin', 'email = ?', [$username]); // Find admin by email
        if (!$admin || !password_verify($password, $admin->password_hash)) { // No admin found with that email or invalid password
            return $response
                ->withHeader('Location', $this->basePath . '/auth')
                ->withStatus(302);
        }

        /*var_dump($admin);
        var_dump($admin->export());
        var_dump(isset($admin['passwordHash']));
        var_dump($admin['passwordHash']);
        die();*/

        $_SESSION['admin_id'] = $admin->id;
        $_SESSION['totp_secret'] = $admin->totp_secret;
        $secret = $this->otpService->createSecret();
        $qrCode = $this->otpService->getQrCode($username, $secret);

        $html = $this->twig->render('auth.html.twig', [
            'step' => 'otp_display',
            'qr_code' => $qrCode,
            'base_path' => $this->basePath,
            'app_lang' => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function showVerify(Request $request, Response $response): Response
    {
        $html = $this->twig->render('auth.html.twig', [
            'step' => 'verify',
            'base_path' => $this->basePath,
            'app_lang' => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function verifyOtp(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $code = trim($data['code'] ?? '');
        if ($code === '') {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }
        $secret = $_SESSION['totp_secret'] ?? '';
        if ($this->otpService->verify($secret, $code)) {
            $_SESSION['authenticated'] = true;
            return $response->withHeader('Location', $this->basePath . '/admin')->withStatus(302);
        } else {
            $html = $this->twig->render('auth.html.twig', [
                'step' => 'verify',
                'error' => 'auth.error_invalid',
                'base_path' => $this->basePath,
                'app_lang' => $_SESSION['lang'] ?? 'en',
            ]);
            $response->getBody()->write($html);
        }
        return $response;
    }

    public function logout(Request $request, Response $response): Response
    {
        session_destroy();
        return $response
            ->withHeader('Location', $this->basePath . '/auth')
            ->withStatus(302);
    }
}
