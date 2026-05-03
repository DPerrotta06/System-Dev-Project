<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Catering
{
    public function __construct(
        public array $foodList;
    ) {}

    //Map bean to Menu object
    public static function fromBean(object $bean): self
    {
        return new self(
            foodList: (array) $bean->foodList,
        );
    }

    //Map Menu object to bean   
    public function toBean(): object
    {
        return (object)[
            'foodList' => $this->foodList,
        ];
    }

    //Map Menu object to bean
    public function toBean(): object
    {
        return (object)[
            'foodList' => $this->foodList  ,
        ];
    }


}
