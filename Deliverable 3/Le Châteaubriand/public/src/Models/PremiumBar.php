<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class PremiumBar
{
    public function __construct(
        public array $drinkList,
    ) {}

    //Map bean to PremiumBar object
    public static function fromBean(object $bean): self
    {
        return new self(
            drinkList:   (array)  $bean->drinkList,
        );
    }

    //Map PremiumBar object to bean
    public function toBean(): object
    {
        return (object)[
            'drinkList'   => $this->drinkList,
        ];
    }

}
