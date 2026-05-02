<?php

declare(strict_types=1);

namespace App\Services;

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;

class OTPService
{
    private TwoFactorAuth $twoFactorAuth;
    public function __construct()
    {
        $this->twoFactorAuth = new TwoFactorAuth(new BaconQrCodeProvider(4, '#ffffff', '#000000', 'svg'), 'Le Châteaubriand');
    }

    public function generate(string $label): string
    {
        $secret = $this->twoFactorAuth->createSecret();
        $_SESSION['totp_secret'] = $secret;
        return $this->twoFactorAuth->getQRCodeImageAsDataUri($label, $secret);
    }

    public function verify(string $input): bool
    {
        $secret = $_SESSION['totp_secret'] ?? null;
        if (!$secret) {
            return false;
        }
        return $this->twoFactorAuth->verifyCode($secret, $input);
    }

    public function invalidate(): void
    {
        unset($_SESSION['totp_secret']);
    }
}
