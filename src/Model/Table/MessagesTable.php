<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class MessagesTable extends Table
{
    /**
     * 初期化処理
     *
     * - messages テーブルをセット
     * - 主キーを id に設定
     * - Timestamp ビヘイビアを追加
     * - Conversations との belongsTo 関連を定義
     * - Users（sender_id 経由）との belongsTo 関連を定義
     * - Sender エイリアスを Users に紐付け、送信者を明確に参照可能にする
     *
     * @param array<string, mixed> $config テーブル設定オプション
     * @return void
     */
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
