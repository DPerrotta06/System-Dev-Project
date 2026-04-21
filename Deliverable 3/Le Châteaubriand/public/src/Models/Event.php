<?php

namespace App\Models;

use DateTime;

class Event {
    public function __construct(
        public int $eventId,
        public int $clientId,
        public int $ballroomId,
        public ?int $menuId,
        public ?int $barId,
        public DateTime $eventDate,
        public int $guestQuantity,
        public string $eventDescription,
        public string $eventStatus,
        public DateTime $eventTime,
        public string $eventType
    )
    {
        throw new \Exception('Not implemented');
    }
    public static function fromBean(object $bean){
        return new self(
            eventId: (int) $bean->eventId,
            clientId: (int) $bean->clientId,
            ballroomId: (int) $bean->ballroomId,
            menuId: $bean->menuId ? (int) $bean->menuId : null,
            barId: $bean->barId ? (int) $bean->barId : null,
            eventDate: new DateTime($bean->eventDate),
            guestQuantity: (int) $bean->guestQuantity,
            eventDescription: (string) $bean->eventDescription,
            eventStatus: (string) $bean->eventStatus,
            eventTime: new DateTime($bean->eventTime),
            eventType: (string) $bean->eventType,
        );
    }
}