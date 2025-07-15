<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Follow extends Entity
{
    protected $_accessible = [
        'follower_id' => true,
        'followed_id' => true,
        'created' => true,
        'modified' => true,
    ];
}
