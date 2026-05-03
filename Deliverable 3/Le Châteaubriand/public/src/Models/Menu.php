<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Menu
{
    public function __construct(
        public int    $menuId,
        public string $name,
        public double $pricePerPerson,
        public array $foodList
    ) {}

    //Map bean to Menu object
    public static function fromBean(object $bean): self
    {
        return new self(
            menuId:      (int)    $bean->id,
            name:        (string) $bean->name,
            pricePerPerson: (float)  $bean->pricePerPerson,
            foodList:   (array)  $bean->foodList,
        );
    }

    //Map Menu object to bean
    public function toBean(): object
    {
        return (object)[
            'id'         => $this->menuId,
            'name'       => $this->name,
            'pricePerPerson' => $this->pricePerPerson,
            'foodList'   => $this->foodList,
        ];
    }

    public function getAllItems(): array
    {
        return $this->foodList;
    }

    public function getTotalPrice(int $guestCount): double
    {
        return $this->pricePerPerson * $guestCount;
    }

}
