<?php

namespace App\Models;

use DateTime;

class Service {
    public function __construct(
        public int $serviceId,
        public string $serviceName,
        public string $serviceEmail,
        public string $servicePhoneNumber,
        public string $serviceDescription,
        public string $serviceType
    )
    {
        throw new \Exception('Not implemented');
    }

    public static function fromBean(object $bean){
        return new self(
            serviceId: (int) $bean->serviceId,
            serviceName: (string) $bean->serviceName,
            serviceEmail: (string) $bean->serviceEmail,
            servicePhoneNumber: (string) $bean->servicePhoneNumber,
            serviceDescription: (string) $bean->serviceDescription,
            serviceType: (string) $bean->serviceType,
        );
    }

    //Map Service object to bean
    public function toBean(): object
    {
        return (object)[
            'serviceId'          => $this->serviceId,
            'serviceName'        => $this->serviceName,
            'serviceEmail'       => $this->serviceEmail,
            'servicePhoneNumber' => $this->servicePhoneNumber,
            'serviceDescription' => $this->serviceDescription,
            'serviceType'        => $this->serviceType,
        ];
    }

    //CRUD operations
    public function createService(): void
    {
        $bean = R::dispense('service');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getService(int $serviceId): ?self
    {
        $bean = R::load('service', $serviceId);
        if ($bean->serviceId === 0) {
            return null; 
        }
        return self::fromBean($bean);
    }

    public function updateService(): ?self
    {
        $bean = R::load('service', $this->serviceId);
        if ($bean->serviceId === 0) {
            throw new \Exception("Service with ID {$this->serviceId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deleteService(int $serviceId): void
    {
        R::trash('service', $serviceId);
    }
}