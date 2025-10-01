<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Notifications Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\PortfoliosTable&\Cake\ORM\Association\BelongsTo $Portfolios
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $SenderUsers
 */
class NotificationsTable extends Table
{
    /**
     * 初期化処理
     *
     * - notifications テーブルをセット
     * - 主キーを id に設定
     * - Users（通知の受け手）、SenderUsers（通知の送り手）、Portfolios（通知対象）との関連を定義
     * - Timestamp ビヘイビアを追加
     *
     * @param array<string, mixed> $config テーブル設定オプション
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('notifications');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        // 通知の受け手
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        // 通知の対象（ポートフォリオ）
        $this->belongsTo('Portfolios', [
            'foreignKey' => 'portfolio_id',
            'joinType' => 'LEFT',
        ]);

        // 通知の送り手
        $this->belongsTo('SenderUsers', [
            'className' => 'Users',
            'foreignKey' => 'sender_id',
            'joinType' => 'LEFT',
        ]);

        // 自動で created / modified を扱う
        $this->addBehavior('Timestamp');
    }

    /**
     * バリデーションルールを定義
     *
     * - user_id: 必須
     * - sender_id: 任意
     * - portfolio_id: 任意
     * - type: 文字列（最大50文字）、必須
     * - is_read: 真偽値、必須
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator 修正済みバリデータ
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('user_id')
            ->notEmptyString('user_id', 'ユーザーIDは必須です');

        $validator
            ->integer('sender_id')
            ->allowEmptyString('sender_id');

        $validator
            ->integer('portfolio_id')
            ->allowEmptyString('portfolio_id');

        $validator
            ->scalar('type')
            ->maxLength('type', 50)
            ->notEmptyString('type', '通知タイプは必須です');

        $validator
            ->boolean('is_read')
            ->notEmptyString('is_read', '既読状態は必須です');

        return $validator;
    }

    /**
     * アプリケーションルール（整合性チェック）を定義
     *
     * - user_id が Users に存在すること
     * - sender_id が SenderUsers に存在すること（nullable 可）
     * - portfolio_id が Portfolios に存在すること（nullable 可）
     *
     * @param \Cake\ORM\RulesChecker $rules ルールチェッカー
     * @return \Cake\ORM\RulesChecker 修正済みルールチェッカー
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        // 通知の受信者（Users）に存在しているか
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        // 通知の送り手（SenderUsers）に存在しているか
        $rules->add($rules->existsIn('sender_id', 'SenderUsers'), ['errorField' => 'sender_id']);

        // ポートフォリオが存在しているか（nullableでも可）
        $rules->add($rules->existsIn('portfolio_id', 'Portfolios'), ['errorField' => 'portfolio_id']);

        return $rules;
    }
}
