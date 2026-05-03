<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class OpenBar
{
    public function calculateTotal(Event $event): double
    {
        return $this->pricePerPerson * $event->guestQuantity;
    }

}
