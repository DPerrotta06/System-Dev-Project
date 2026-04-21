<?

namespace App\Models;

use DateTime;

class Admin{
    public function __construct(
        public int $id,
        public string $email,
        public string $password,
        public string $twoFactorCode,
        public DateTime $factorCodeExpiration 
    )
    {
        throw new \Exception('Not implemented');
    }
    public static function fromBean(object $bean){
        return new self(
            id: (int) $bean-> id,
            email: (string) $bean-> string,
            password: (string) $bean-> string,
            twoFactorCode: (string) $bean-> string,
            factorCodeExpiration: new DateTime($bean->eventTime),
        );
    }
}

