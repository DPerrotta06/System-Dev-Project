<?php

namespace App\Models;

use DateTime;

class Payment {
    public function __construct(
        public int $paymentId,
        public int $eventId,
        public float $amountPaid,
        public DateTime $nextPaymentDue,
        public string $paymentMethod,
        public string $paymentPlan,
        public float $depositRequired,
        public float $totalPrice
    )
    {
        throw new \Exception('Not implemented');
    }

    public static function fromBean(object $bean){
        return new self(
            paymentId: (int) $bean->paymentId,
            eventId: (int) $bean->eventId,
            amountPaid: (float) $bean->amountPaid,
            paymentDate: new DateTime($bean->paymentDate),
            amountLeft: (float) $bean->amountLeft,
            paymentMethod: (string) $bean->paymentMethod,
            paymentPlan: (string) $bean->paymentPlan,
            depositRequired: (float) $bean->depositRequired,
            totalPrice: (float) $bean->totalPrice,
        );
    }

    //Map Payment object to bean
    public function toBean(): object
    {
        return (object)[
            'paymentId'     => $this->paymentId,
            'eventId'       => $this->eventId,
            'amountPaid'    => $this->amountPaid,
            'nextPaymentDue' => $this->nextPaymentDue->format('Y-m-d'),
            'amountLeft'    => $this->amountLeft,
            'paymentMethod' => $this->paymentMethod,
            'paymentPlan'   => $this->paymentPlan,
            'depositRequired' => $this->depositRequired,
            'totalPrice'    => $this->totalPrice,
        ];
    }

    //CRUD operations
    public function createPayment(): void
    {
        $bean = R::dispense('payment');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getPayment(int $paymentId): ?self
    {
        $bean = R::load('payment', $paymentId);
        if ($bean->paymentId === 0) {
            return null; 
        }
        return self::fromBean($bean);
    }

    public function updatePayment(): ?self
    {
        $bean = R::load('payment', $this->paymentId);
        if ($bean->paymentId === 0) {
            throw new \Exception("Payment with ID {$this->paymentId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deletePayment(int $paymentId): void
    {
        R::trash('payment', $paymentId);
    }
}