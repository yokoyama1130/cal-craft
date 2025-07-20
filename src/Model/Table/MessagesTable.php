<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class MessagesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('messages');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Conversations');
        $this->belongsTo('Users', [
            'foreignKey' => 'sender_id',
        ]);

        // 送信者（Sender）を Users テーブルに紐づける
        $this->belongsTo('Sender', [
            'className' => 'Users',
            'foreignKey' => 'sender_id',
            'joinType' => 'INNER',
        ]);
    }
}
