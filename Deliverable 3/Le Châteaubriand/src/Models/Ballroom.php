<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class Ballroom
{
    public function __construct(
        public int    $ballroomId,
        public string $roomName,
        public int $maxCapacity,
        public int  $minCapacity,
        public int $sizeSqFt,
        public string $picturesPath,
        public string $arrangementPath,
        public bool $hasBar
    ) {}

    public static function fromBean(object $bean): self
    {
        return new self(
            ballroomId: (int)    $bean->id,
            roomName: (string) $bean->roomName,
            minCapacity: (int)    $bean->minCapacity,
            maxCapacity: (int)    $bean->maxCapacity,
            sizeSqFt: (int)    $bean->sizeSqFt,
            picturesPath: (string)  $bean->picturesPath,
            arrangementPath: (string) $bean->arrangementPath,
            hasBar: (bool)   $bean->hasBar,
        );
    }
}
