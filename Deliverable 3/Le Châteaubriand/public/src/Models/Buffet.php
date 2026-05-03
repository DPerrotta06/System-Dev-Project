<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Buffet
{
    public function __construct(
        public array $foodCategories;
    ) {}

    //Map bean to Menu object
    public static function fromBean(object $bean): self
    {
        return new self(
            foodCategories: (array) $bean->foodCategories,
        );
    }

    //Map Menu object to bean   
    public function toBean(): object
    {
        return (object)[
            'foodCategories' => $this->foodCategories,
        ];
    }

    //Map Menu object to bean
    public function toBean(): object
    {
        return (object)[
            'foodCategories' => $this->foodCategories,
        ];
    }


}
