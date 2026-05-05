<?php

namespace App\Models;

use DateTime;

class Admin{
    public function __construct(
        public int $adminId,
        public string $username,
        public string $email,
        public string $passwordHash,
        public string $twoFactorCode,
        public DateTime $codeExpiration 
    )
    {
        throw new \Exception('Not implemented');
    }
    public static function fromBean(object $bean){
        return new self(
            adminId: (int) $bean-> id,
            email: (string) $bean-> string,
            passwordHash: (string) $bean-> string,
            twoFactorCode: (string) $bean-> string,
            codeExpiration: new DateTime($bean->eventTime),
        );
    }

    //Map Admin object to bean
    public function toBean(): object
    {
        return (object)[
            'id'         => $this->adminId,
            'email'  => $this->email,
            'passwordHash'   => $this->passwordHash,
            'twoFactorCode'      => $this->twoFactorCode,
            'codeExpiration'=> $this->codeExpiration,
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

    //Admin functions
    public function login(string $email, string $password): bool{
        $admin = R::findOne('admin', 'email = ?', [$email]);

        if (!$admin) {
            return false;
        }

        if (!password_verify($password, $admin->password_hash)) {
            return false;
        }

        if ($admin->role !== 'admin') {
            return false;
        }

        $_SESSION['admin_id'] = $admin->id;
        $_SESSION['admin_name'] = $admin->name;

        return true;
    }

    public function logout() {
        session_start();
        
        // Clear all session data
        $_SESSION = [];
        
        // Optional: delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        header('Location: login.php');
        exit;
    }

    public function makeContract(): void{

    }

    private function generateInvoice(): void{

    }

}

