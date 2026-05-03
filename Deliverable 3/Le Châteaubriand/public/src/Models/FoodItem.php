<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class FoodItem
{
    public function __construct(
        public string $name,
        public string $category,
        public bool $isIncluded, 
        public ?double $extraPrice,
    ) {}

    //Map bean to FoodItem object
    public static function fromBean(object $bean): self
    {
        return new self(
            name:        (string) $bean->name,
            category:    (string) $bean->category,
            isIncluded:  (bool)   $bean->isIncluded,
            extraPrice:  (float|null) $bean->extraPrice,
        );
    }

    //Map FoodItem object to bean
    public function toBean(): object
    {
        return (object)[
            'name'         => $this->name,
            'category'     => $this->category,
            'isIncluded'   => $this->isIncluded,
            'extraPrice'   => $this->extraPrice,
        ];
    }

}
