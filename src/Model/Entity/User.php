<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'sns_links' => true,
    ];

    protected $_hidden = ['password'];

    protected function _setPassword(string $password): ?string
    {
        return (new DefaultPasswordHasher)->hash($password);
    }
}

