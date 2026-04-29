<?php

namespace App\Models;

use DateTime;

class Bar {
    public function __construct(
        public int $barId,
        public string $type,
        public ?float $pricePerPerson,
        public ?DateTime $barOpenTime,
        public ?DateTime $barCloseTime
    )
    {
        throw new \Exception('Not implemented');
    }

    public static function fromBean(object $bean){
        return new self(
            barId: (int) $bean->barId,
            type: (string) $bean->type,
            pricePerPerson: isset($bean->pricePerPerson) ? (float) $bean->pricePerPerson : null,
            barOpenTime: isset($bean->barOpenTime) ? new DateTime($bean->barOpenTime) : null,
            barCloseTime: isset($bean->barCloseTime) ? new DateTime($bean->barCloseTime) : null,
        );
    }

    //Map Bar object to bean
    public function toBean(): object
    {
        return (object)[
            'barId'          => $this->barId,
            'type'           => $this->type,
            'pricePerPerson' => $this->pricePerPerson,
            'barOpenTime'    => $this->barOpenTime?->format('Y-m-d H:i:s'),
            'barCloseTime'   => $this->barCloseTime?->format('Y-m-d H:i:s'),
        ];
    }

    //CRUD operations
    public function createBar(): void
    {
        $bean = R::dispense('bar');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getBar(int $barId): ?self
    {
        $bean = R::load('bar', $barId);
        if ($bean->barId === 0) {
            return null; 
        }
        return self::fromBean($bean);
    }

    public function updateBar(): ?self
    {
        $bean = R::load('bar', $this->barId);
        if ($bean->barId === 0) {
            throw new \Exception("Bar with ID {$this->barId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deleteBar(int $barId): void
    {
        R::trash('bar', $barId);
    }
}