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
            clientId:   (int)    $bean->id,
            firstName:  (string) $bean->firstName,
            lastName:   (string) $bean->lastName,
            email:      (string) $bean->email,
            phoneNumber:(string) $bean->phoneNumber,
        );
    }

    //Map Client object to bean
    public function toBean(): object
    {
        return (object)[
            'id'         => $this->clientId,
            'firstName'  => $this->firstName,
            'lastName'   => $this->lastName,
            'email'      => $this->email,
            'phoneNumber'=> $this->phoneNumber,
        ];
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
        R::trash('client', $clientId);
    }

    //add getEvents()

}