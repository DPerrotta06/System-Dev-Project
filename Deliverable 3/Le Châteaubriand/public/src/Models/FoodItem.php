<?php

namespace App\Models;

use DateTime;

class FoodItem {
    public function __construct(
        public int $itemId,
        public string $itemName,
        public int $itemCategory,
        public float $itemPrice,
        public ?float $extraPrice
    )
    {
        throw new \Exception('Not implemented');
    }

    public static function fromBean(object $bean){
        return new self(
            itemId: (int) $bean->itemId,
            itemName: (string) $bean->itemName,
            itemCategory: (int) $bean->itemCategory,
            itemPrice: (float) $bean->itemPrice,
            extraPrice: isset($bean->extraPrice) ? (float) $bean->extraPrice : null,
        );
    }

    //Map FoodItem object to bean
    public function toBean(): object
    {
        return (object)[
            'itemId'      => $this->itemId,
            'itemName'    => $this->itemName,
            'itemCategory'=> $this->itemCategory,
            'itemPrice'   => $this->itemPrice,
            'extraPrice'  => $this->extraPrice,
        ];
    }

    //CRUD operations
    public function createFoodItem(): void
    {
        $bean = R::dispense('fooditem');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getFoodItem(int $itemId): ?self
    {
        $bean = R::load('fooditem', $itemId);
        if ($bean->itemId === 0) {
            return null; 
        }
        return self::fromBean($bean);
    }

    public function updateFoodItem(): ?self
    {
        $bean = R::load('fooditem', $this->itemId);
        if ($bean->itemId === 0) {
            throw new \Exception("FoodItem with ID {$this->itemId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deleteFoodItem(int $itemId): void
    {
        R::trash('fooditem', $itemId);
    }
}