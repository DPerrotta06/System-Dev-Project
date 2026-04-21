<?php

namespace App\Models;

use DateTime;

class Payment {
    public function __construct(
        public int $paymentId,
        public int $eventId,
        public float $amountPaid,
        public DateTime $paymentDate,
        public float $amountLeft,
        public string $paymentMethod,
        public string $paymentPlan,
        public float $depositAmount,
        public float $depositPaid,
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
            depositAmount: (float) $bean->depositAmount,
            depositPaid: (float) $bean->depositPaid,
            totalPrice: (float) $bean->totalPrice,
        );
    }
}