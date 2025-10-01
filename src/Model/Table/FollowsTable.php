<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class FollowsTable extends Table
{
    /**
     * 初期化処理
     *
     * - follows テーブルをセット
     * - 主キーを id に設定
     * - follower_id に基づく Users との belongsTo 関連を定義
     * - followed_id に基づく FollowedUsers との belongsTo 関連を定義
     *
     * @param array<string, mixed> $config テーブル設定オプション
     * @return void
     */
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
