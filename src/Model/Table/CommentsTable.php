<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;

/**
 * Comments Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\PortfoliosTable&\Cake\ORM\Association\BelongsTo $Portfolios
 * @method \App\Model\Entity\Comment newEmptyEntity()
 * @method \App\Model\Entity\Comment newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Comment[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Comment get($primaryKey, $options = [])
 * @method \App\Model\Entity\Comment findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Comment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Comment[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Comment|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Comment saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CommentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('comments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT', // 会社コメント時は NULL なので LEFT
        ]);

        $this->belongsTo('Companies', [
            'foreignKey' => 'company_id',
            'joinType' => 'LEFT',
        ]);

        $this->belongsTo('Portfolios', [
            'foreignKey' => 'portfolio_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $v Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(\Cake\Validation\Validator $v): \Cake\Validation\Validator
    {
        $v->integer('portfolio_id')->notEmptyString('portfolio_id');
        $v->notEmptyString('content');

        // user/company はどちらか片方だけ必須（アプリ側で担保）
        $v->allowEmptyString('user_id');
        $v->allowEmptyString('company_id');

        // カスタムルール：「user_id XOR company_id」
        $v->add('user_id', 'xorUserCompany', [
            'rule' => function ($value, $context) {
                $u = $value ?? null;
                $c = $context['data']['company_id'] ?? null;

                return ($u && !$c) || (!$u && $c);
            },
            'message' => 'ユーザーまたは会社のどちらか一方を指定してください。',
        ]);

        return $v;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn('company_id', 'Companies'), ['errorField' => 'compnay_id']);
        $rules->add($rules->existsIn('portfolio_id', 'Portfolios'), ['errorField' => 'portfolio_id']);

        return $rules;
    }
}
