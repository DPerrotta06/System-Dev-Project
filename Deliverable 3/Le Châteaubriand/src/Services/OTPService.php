<?php

declare(strict_types=1);

namespace App\Services;

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;;

class OTPService
{
    private TwoFactorAuth $twoFactorAuth;
    public function __construct()
    {
        $this->twoFactorAuth = new TwoFactorAuth(new EndroidQrCodeProvider(), 'Le Châteaubriand');
    }

     public function createSecret(): string
    {
        return $this->twoFactorAuth->createSecret();
    }

    public function getQrCode(string $label, string $secret): string
    {
        return $this->twoFactorAuth->getQRCodeImageAsDataUri($label, $secret);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->twoFactorAuth->verifyCode($secret, $code);
    }
}
