<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Client
{
    public function __construct(
        public int    $clientId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
    ) {}

    //Map bean to Client object
    public static function fromBean(object $bean): self
    {
        return new self(
            clientId: (int)    $bean->id,
            firstName: (string) $bean->firstName,
            lastName: (string) $bean->lastName,
            email: (string) $bean->email,
            phoneNumber: (string) $bean->phoneNumber,
        );
    }

    //Map Client object to bean
    public function toBean(): object
    {
        $bean = R::dispense('client');
        $bean->id = $this->clientId;
        $bean->firstName = $this->firstName;
        $bean->lastName = $this->lastName;
        $bean->email = $this->email;
        $bean->phoneNumber = $this->phoneNumber;
        return $bean;
    }

    // CRUD operations
    public function createClient(): void
    {
        $bean = R::dispense('client');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getClient(int $clientId): ?self
    {
        $bean = R::load('client', $clientId);
        if ($bean->id === 0) {
            return null;
        }
        return self::fromBean($bean);
    }

    //search function that supports live Ajax search
    public static function search(string $query): array
    {
        $beans = R::find(
            'client',
            ' firstName LIKE ? OR lastName LIKE ? OR email LIKE ? OR phoneNumber LIKE ? ',
            ["%$query%", "%$query%", "%$query%", "%$query%"]
        );

        return array_map(fn($bean) => self::fromBean($bean), $beans);
    }

    //Search client by name
    public static function getClientByName(string $name): ?self
    {
        $bean = R::findOne('client', ' firstName LIKE ? OR lastName LIKE ? ', ["%name%", "%name%"]);
        return $bean ? self::fromBean($bean) : null;
    }

    public function updateClient(): ?self
    {
        $bean = R::load('client', $this->clientId);
        if ($bean->id === 0) {
            throw new \Exception("Client with ID {$this->clientId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deleteClient(int $clientId): void
    {
        $bean = R::load('client', $clientId);
        if ($bean->id !== 0) {
            R::trash($bean);
        }
    }

    //return all clients from the database
    public static function getAllClients(): array{
        $beans = R::findAll('client');
        return array_map(fn($bean) => self::fromBean($bean), $beans);
    }

    //returns JSON friendly arrays
    public function toArray(): array
{
    return [
        'id' => $this->clientId,
        'firstName' => $this->firstName,
        'lastName' => $this->lastName,
        'email' => $this->email,
        'phone' => $this->phoneNumber,
    ];
}
}
