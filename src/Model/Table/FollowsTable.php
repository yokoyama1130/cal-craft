<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class FollowsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('follows'); // DB上のテーブル名
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'follower_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('FollowedUsers', [
            'className' => 'Users',
            'foreignKey' => 'followed_id',
            'joinType' => 'INNER',
        ]);
    }
}
