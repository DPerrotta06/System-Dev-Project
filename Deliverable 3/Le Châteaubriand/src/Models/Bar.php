<?php

namespace App\Models;

use DateTime;
use RedBeanPHP\R;

class Bar {
    public function __construct(
        public int $barId,
        public string $barType,
        public ?float $pricePerPerson,
        public ?DateTime $openTime,
        public ?DateTime $closeTime
    )
    {
        throw new \Exception('Not implemented');
    }

    public static function fromBean(object $bean){
        return new self(
            barId: (int) $bean->barId,
            barType: (string) $bean->barType,
            pricePerPerson: isset($bean->pricePerPerson) ? (float) $bean->pricePerPerson : null,
            openTime: isset($bean->openTime) ? new DateTime($bean->openTime) : null,
            closeTime: isset($bean->closeTime) ? new DateTime($bean->closeTime) : null,
        );
    }

    //Map Bar object to bean
    public function toBean(): object
    {
        return (object)[
            'barId'          => $this->barId,
            'type'           => $this->barType,
            'pricePerPerson' => $this->pricePerPerson,
            'openTime'       => $this->openTime?->format('Y-m-d H:i:s'),
            'closeTime'      => $this->closeTime?->format('Y-m-d H:i:s'),
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