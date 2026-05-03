<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Event
{
    public function __construct(
        public int    $eventId,
        public int    $clientId,
        public int $guestQuantity,
        public string $eventDescription,
        public double $totalPrice,
        public double $depositAmount,
        public bool $depositPaid,
        public bool $paymentPlan,
        public array $tableArrangements,
        public string $eventStatus,
    ) {}

    //Map bean to Event object
    public static function fromBean(object $bean): self
    {
        return new self(
            eventId:      (int)    $bean->id,
            clientId:     (int)    $bean->clientId,
            guestQuantity: (int)   $bean->guestQuantity,
            eventDescription: (string) $bean->eventDescription,
            totalPrice:   (double) $bean->totalPrice,
            depositAmount: (double) $bean->depositAmount,
            depositPaid:  (bool)   $bean->depositPaid,
            paymentPlan:  (bool)   $bean->paymentPlan,
            tableArrangements: (array) $bean->tableArrangements,
            eventStatus:  (string) $bean->eventStatus,
        );
    }

    //Map Event object to bean
    public function toBean(): object
    {
        return (object)[
            'id'         => $this->eventId,
            'clientId'   => $this->clientId,
            'guestQuantity' => $this->guestQuantity,
            'eventDescription' => $this->eventDescription,
            'totalPrice' => $this->totalPrice,
            'depositAmount' => $this->depositAmount,
            'depositPaid' => $this->depositPaid,
            'paymentPlan' => $this->paymentPlan,
            'tableArrangements' => $this->tableArrangements,
            'eventStatus' => $this->eventStatus,
        ];
    }
    
    public function calculateTotalPrice(): double
    {
        // Calculate total price based on event details  
        return $this->totalPrice;
    }

}
