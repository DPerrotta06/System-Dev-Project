<?php

namespace App\Models;

use RedBeanPHP\R;

class Menu {
    public function __construct(
        public int $menuId,
        public string $menuName,
        public float $pricePerPerson
    )
    {
        throw new \Exception('Not implemented');
    }

    public static function fromBean(object $bean){
        return new self(
            menuId: (int) $bean->menuId,
            menuName: (string) $bean->menuName,
            pricePerPerson: (float) $bean->pricePerPerson,
        );
    }

    // Map Menu object to bean
    public function toBean(): object
    {
        return (object)[
            'menuId'         => $this->menuId,
            'menuName'       => $this->menuName,
            'pricePerPerson' => $this->pricePerPerson,
        ];
    }

    // CRUD operations
    public function createMenu(): void
    {
        $bean = R::dispense('menu');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getMenu(int $menuId): ?self
    {
        $bean = R::load('menu', $menuId);
        if ($bean->menuId === 0) {
            return null;
        }
        return self::fromBean($bean);
    }

    public function updateMenu(): ?self
    {
        $bean = R::load('menu', $this->menuId);
        if ($bean->menuId === 0) {
            throw new \Exception("Menu with ID {$this->menuId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deleteMenu(int $menuId): void
    {
        R::trash('menu', $menuId);
    }

    public static function getAllMenus(): array
    {
        $beans = R::findAll('menu');
        $result = [];
        foreach ($beans as $bean) {
            $result[] = self::fromBean($bean);
        }
        return $result;
    }
}
