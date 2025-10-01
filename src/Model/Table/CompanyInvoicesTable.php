<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CompanyInvoicesTable extends Table
{
    /**
     * バリデーションルールを定義
     *
     * - company_id: 整数・必須
     * - amount: 整数・任意
     * - currency: 文字列・任意
     * - status: 文字列・任意
     * - paid_at: 日時・任意
     * - raw_payload: 任意（JSONや文字列）
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator 修正済みバリデータ
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->integer('company_id')->requirePresence('company_id')->notEmptyString('company_id')
            ->integer('amount')->allowEmptyString('amount')
            ->scalar('currency')->allowEmptyString('currency')
            ->scalar('status')->allowEmptyString('status')
            ->dateTime('paid_at')->allowEmptyDateTime('paid_at')
            ->allowEmptyString('raw_payload');
    }

    /**
     * 初期化処理
     *
     * - company_invoices テーブルをセット
     * - 主キーを id に設定
     * - Timestamp ビヘイビアを追加
     * - Companies との belongsTo 関連を定義
     *
     * @param array<string, mixed> $config テーブル設定オプション
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('company_invoices');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Companies', [
            'foreignKey' => 'company_id',
            'joinType' => 'INNER',
        ]);
    }
}
