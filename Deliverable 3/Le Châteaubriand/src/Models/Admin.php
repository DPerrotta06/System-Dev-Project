<?php

namespace App\Models;

use RedBeanPHP\R;

class Admin
{
    public function __construct(
        public int $adminId,
        public string $email,
        public string $passwordHash,
    ) {}

    public static function fromBean(object $bean)
    {
        return new self(
            adminId: (int) $bean->id,
            email: (string) $bean->string,
            passwordHash: (string) $bean->string
        );
    }

    //Map Admin object to bean
    public function toBean(): object
    {
        return (object)[
            'id'         => $this->adminId,
            'email'  => $this->email,
            'passwordHash'   => $this->passwordHash,
        ];
    }

    //CRUD operations
    public function createAdmin(): void
    {
        $bean = R::dispense('admin');
        $bean->import($this->toBean());
        R::store($bean);
    }

    public static function getAdmin(int $adminId): ?self
    {
        $bean = R::load('admin', $adminId);
        if ($bean->id === 0) {
            return null;
        }
        return self::fromBean($bean);
    }

    public function updateAdmin(): ?self
    {
        $bean = R::load('admin', $this->adminId);
        if ($bean->id === 0) {
            throw new \Exception("Admin with ID {$this->adminId} not found.");
        }
        $bean->import($this->toBean());
        R::store($bean);
        return $this;
    }

    public static function deleteAdmin(int $adminId): void
    {
        R::trash('admin', $adminId);
    }
}
