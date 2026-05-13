<?php

namespace App\Models;

use DateTime;
use RedBeanPHP\R;

class Event {
    public function __construct(
        public int $eventId,
        public int $clientId,
        public int $ballroomId,
        public ?int $menuId,
        public ?int $barId,
        public DateTime $eventDate,
        public int $guestCount,
        public string $description,
        public string $status,
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
            guestCount: (int) $bean->guestCount,
            description: (string) $bean->description,
            status: (string) $bean->status,
            eventTime: new DateTime($bean->eventTime),
            eventType: (string) $bean->eventType,
        );
    }

    //Map Admin object to bean
    public function toBean(): object
{
    $bean = R::dispense('event'); // assumes your table is 'event'

    $bean->eventId          = $this->eventId;
    $bean->clientId         = $this->clientId;         // FK to client
    $bean->ballroomId       = $this->ballroomId;
    $bean->menuId           = $this->menuId;
    $bean->barId            = $this->barId;
    $bean->eventDate        = $this->eventDate->format('Y-m-d');
    $bean->guestCount       = $this->guestCount;
    $bean->description      = $this->description;
    $bean->status           = $this->status;
    $bean->eventTime        = $this->eventTime->format('H:i:s');
    $bean->eventType        = $this->eventType;

    return $bean;
}

//CRUD operations
    public function createEvent(): void
    {
        $bean = R::dispense('event');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getEvent(int $eventId): ?self
    {
        $bean = R::load('event', $eventId);
        if ($bean->id === 0) {
            return null; 
        }
        return self::fromBean($bean);
    }

    public function updateEvent(): ?self
    {
        $bean = R::load('event', $this->eventId);
        if ($bean->id === 0) {
            throw new \Exception("Event with ID {$this->eventId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deleteEvent(int $eventId): void
    {
        R::trash('event', $eventId);
    }

}