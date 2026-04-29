<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Ballroom
{
    public function __construct(
        public int    $ballroomId,
        public string $name,
        public int $minCapacity,
        public int $maxCapacity,
        public int  $maxCapacity,
        public int $sizeSqFt,
        public array $pictures,
    ) {}

    public static function fromBean(object $bean): self
    {
        return new self(
            ballroomId:  (int)    $bean->id,
            name:        (string) $bean->name,
            minCapacity: (int)    $bean->minCapacity,
            maxCapacity: (int)    $bean->maxCapacity,
            sizeSqFt:   (int)    $bean->sizeSqFt,
            pictures:   (array)  $bean->pictures,
        );
    }

}