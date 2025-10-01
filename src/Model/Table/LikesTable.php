<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class LikesTable extends Table
{
    /**
     * 初期化処理
     *
     * - likes テーブルをセット
     * - 主キーを id に設定
     * - Timestamp ビヘイビアを追加
     * - Users, Companies, Portfolios との関連を定義
     *
     * @param array<string, mixed> $config テーブル設定オプション
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('likes');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        // ★ 関連を明示（エイリアス名は buildRules の existsIn と一致させる）
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
            // 'className' => 'App.Users', // 基本不要。別プラグインなら指定
        ]);

        $this->belongsTo('Companies', [
            'foreignKey' => 'company_id',
            'joinType' => 'LEFT',
            // 'className' => 'App.Companies',
        ]);

        $this->belongsTo('Portfolios', [
            'foreignKey' => 'portfolio_id',
            'joinType' => 'INNER',
            // 'className' => 'App.Portfolios',
        ]);
    }

    /**
     * バリデーションルールを定義
     *
     * - user_id / company_id はどちらか一方が入力される想定
     * - portfolio_id は必須
     * - カスタムルールで「user_id または company_id のいずれか必須」を担保
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator 修正済みバリデータ
     */
    public function validationDefault(Validator $validator): Validator
    {
        // 会社いいね対応: user_id / company_id はどちらか一方が入る想定
        $validator->integer('user_id')->allowEmptyString('user_id');
        $validator->integer('company_id')->allowEmptyString('company_id');

        $validator->integer('portfolio_id')->notEmptyString('portfolio_id');

        // どちらか必須チェック
        $validator->add('user_or_company', 'requireOne', [
            'rule' => function ($value, $context) {
                $d = $context['data'] ?? [];

                return !empty($d['user_id']) || !empty($d['company_id']);
            },
            'message' => 'ユーザーまたは会社のどちらかを指定してください。',
        ]);

        return $validator;
    }

    /**
     * アプリケーションルール（整合性チェック）を定義
     *
     * - portfolio_id が存在することを確認
     * - user_id が指定されている場合、Users テーブルに存在するか確認
     * - company_id が指定されている場合、Companies テーブルに存在するか確認
     *
     * @param \Cake\ORM\RulesChecker $rules ルールチェッカー
     * @return \Cake\ORM\RulesChecker 修正済みルールチェッカー
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        // 存在チェック（関連が正しく生えていればOK）
        $rules->add($rules->existsIn(['portfolio_id'], 'Portfolios'), ['errorField' => 'portfolio_id']);

        // それぞれセットされているときのみ存在チェック
        $rules->add(function ($entity) {
            return $entity->user_id ? $this->Users->exists(['id' => $entity->user_id]) : true;
        }, 'userExists', ['errorField' => 'user_id', 'message' => 'ユーザーが存在しません。']);

        $rules->add(function ($entity) {
            return $entity->company_id ? $this->Companies->exists(['id' => $entity->company_id]) : true;
        }, 'companyExists', ['errorField' => 'company_id', 'message' => '会社が存在しません。']);

        return $rules;
    }
}
