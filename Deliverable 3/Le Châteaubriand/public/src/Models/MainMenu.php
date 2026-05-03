<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;



class MainMenu
{
    public function __construct(
        public FoodItem $soup,
        public FoodItem $pasta,
        public FoodItem $mainCourse,
        public FoodItem $dessert,
    ) {}

    //Map bean to MainMenu object
    public static function fromBean(object $bean): self
    {
        return new self(
            soup:        (FoodItem) $bean->soup,
            pasta:       (FoodItem) $bean->pasta,
            mainCourse:  (FoodItem) $bean->mainCourse,
            dessert:     (FoodItem) $bean->dessert,
        );
    }

    //Map MainMenu object to bean
    public function toBean(): object
    {
        return (object)[
            'soup'        => $this->soup,
            'pasta'       => $this->pasta,
            'mainCourse'  => $this->mainCourse,
            'dessert'     => $this->dessert,
        ];
    }

}
