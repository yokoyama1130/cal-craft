<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CompanyInvoicesTable extends Table
{
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->integer('company_id')->requirePresence('company_id')->notEmptyString('company_id')
            ->integer('amount')->allowEmptyString('amount')       // ← ここで弾かない
            ->scalar('currency')->allowEmptyString('currency')
            ->scalar('status')->allowEmptyString('status')
            ->dateTime('paid_at')->allowEmptyDateTime('paid_at')
            ->allowEmptyString('raw_payload');
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('company_invoices');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Companies', [
            'foreignKey' => 'company_id',
            'joinType'   => 'INNER',
        ]);
    }
}
