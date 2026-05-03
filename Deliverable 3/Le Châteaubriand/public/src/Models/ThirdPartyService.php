<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class ThirdPartyService
{
    public function __construct(
        public ?int    $serviceId,
        public string $serviceName,
        public string $serviceEmail,
        public string $servicePhoneNumber,
        public string $description
    ) {}

    //Map bean to ThirdPartyService object
    public static function fromBean(object $bean): self
    {
        return new self(
            serviceId:      (int)    $bean->id,
            serviceName:        (string) $bean->serviceName,
            serviceEmail:       (string) $bean->serviceEmail,
            servicePhoneNumber: (string) $bean->servicePhoneNumber,
            description:        (string) $bean->description,
        );
    }

    //Map ThirdPartyService object to bean
    public function toBean(): object
    {
        return (object)[
            'serviceId'         => $this->serviceId,
            'serviceName'       => $this->serviceName,
            'serviceEmail' => $this->serviceEmail,
            'servicePhoneNumber' => $this->servicePhoneNumber,
            'description' => $this->description,
        ];
    }

    public function getAllServices(): array
    {
        
    }
}
