<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ConversationsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('conversations');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('User1', [
            'className' => 'Users',
            'foreignKey' => 'user1_id',
        ]);
        $this->belongsTo('User2', [
            'className' => 'Users',
            'foreignKey' => 'user2_id',
        ]);
        $this->hasMany('Messages', [
            'foreignKey' => 'conversation_id',
            'dependent' => true,
        ]);
    }
}
