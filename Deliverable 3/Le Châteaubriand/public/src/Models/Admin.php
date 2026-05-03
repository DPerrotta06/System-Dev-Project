<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Admin
{
    public function __construct(
        private int    $adminId,
        private string $username,
        private string $password,
        private string $twoFactorCode,
        private DateTime $factorCodeExpiration,
    ) {}

    //Map bean to Admin object
    public static function fromBean(object $bean): self
    {
        return new self(
            adminId:      (int)    $bean->id,
            username:     (string) $bean->username,
            password:     (string) $bean->password,
            twoFactorCode: (string) $bean->twoFactorCode,
            factorCodeExpiration: (DateTime) $bean->factorCodeExpiration,
        );
    }

    //Map Admin object to bean
    public function toBean(): object
    {
        return (object)[
            'id'           => $this->adminId,
            'username'     => $this->username,
            'password'       => $this->password,
            'twoFactorCode' => $this->twoFactorCode,
            'factorCodeExpiration' => $this->factorCodeExpiration,
        ];
    }

    public function login(string $username, string $password): bool
    {
        // Verify username and password against stored credentials
        return true; //placeholder
    }

    private function generate2FA(): string
    {
        // Generate a random 6-digit code
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->twoFactorCode = $code;
        $this->factorCodeExpiration = (new DateTime())->add(new DateInterval('PT5M')); // Code valid for 5 minutes
        return $code;
    }

    private function is2FACorrect(string $code): bool
    {
        // Check if the provided code matches and is not expired
        return $code === $this->twoFactorCode && new DateTime() < $this->factorCodeExpiration;
    }

    public function logout(): void
    {
        // Clear session or token data to log out the admin
    }

    public function viewAllClients(): ?array
    {
        // Retrieve and return a list of all clients from the database
        return []; //placeholder
    }

    public function filterClient(Client $criteria): ?array
    {
        // Filter clients based on criteria and return matching clients
        return []; //placeholder
    }

    public function searchClient(string $name): ?array
    {
        // Search for clients by name and return matching clients
        return []; //placeholder
    }

    public function viewCalendar(): void
    {
        // Display calendar with events and bookings
    }

    public function updateClient(int $clientId): Client
    {

    }

    public function deleteClient(int $clientId): bool
    {

    }

    public function makeContract(): void
    {

    }

    private function generateInvoice(): void
    {

    }

}
