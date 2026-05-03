<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class MidnightTable
{
    public function __construct(
        public ?double $sweetsPrice,
        public ?double $sandwichesPrice,
        public ?double $savouryPrice,
    ) {}

    //Map bean to MidnightTable object
    public static function fromBean(object $bean): self
    {
        return new self(
            sweetsPrice:   (float)  $bean->sweetsPrice,
            sandwichesPrice: (float)  $bean->sandwichesPrice,
            savouryPrice:  (float)  $bean->savouryPrice,
        );
    }

    //Map MidnightTable object to bean
    public function toBean(): object
    {
        return (object)[
            'sweetsPrice'   => $this->sweetsPrice,
            'sandwichesPrice' => $this->sandwichesPrice,
            'savouryPrice'  => $this->savouryPrice,
        ];
    }

    public function calculateExtraItems(): ?double
    {
        return ($this->sweetsPrice ?? 0) + ($this->sandwichesPrice ?? 0) + ($this->savouryPrice ?? 0);
    }

}
