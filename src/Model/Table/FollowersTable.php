<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class FollowersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('followers');
        $this->setPrimaryKey(['follower_id', 'followee_id']);

        $this->belongsTo('Users', [
            'foreignKey' => 'follower_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Followees', [
            'className' => 'Users',
            'foreignKey' => 'followee_id',
            'joinType' => 'INNER',
        ]);
    }
}
