<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ConversationsTable extends Table
{
    /**
     * 初期化処理
     *
     * - conversations テーブルをセット
     * - 主キーを id に設定
     * - Timestamp ビヘイビアを追加
     * - User1/User2 との belongsTo 関連を定義
     * - Messages との hasMany 関連を定義
     *
     * @param array<string, mixed> $config テーブル設定オプション
     * @return void
     */
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
