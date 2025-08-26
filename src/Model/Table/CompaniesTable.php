<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Companies Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Company newEmptyEntity()
 * @method \App\Model\Entity\Company newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Company[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Company get($primaryKey, $options = [])
 * @method \App\Model\Entity\Company findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Company patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Company[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Company|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Company saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Company[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Company[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Company[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Company[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CompaniesTable extends Table
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

        $this->setTable('companies');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'owner_user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasOne('Company', [ // エイリアスは単数でOK（テーブルは自動で 'companies' にマップ）
            'className' => 'Companies',
            'foreignKey' => 'owner_user_id',
            'dependent' => true, // ユーザー削除時に会社も削除（FKもCASCADEなので二重保険）
        ]);        
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 160)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->add('slug', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('website')
            ->maxLength('website', 255)
            ->allowEmptyString('website');

        $validator
            ->scalar('industry')
            ->maxLength('industry', 100)
            ->allowEmptyString('industry');

        $validator
            ->scalar('size')
            ->maxLength('size', 30)
            ->allowEmptyString('size');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('logo_path')
            ->maxLength('logo_path', 255)
            ->allowEmptyString('logo_path');

        $validator
            ->scalar('domain')
            ->maxLength('domain', 120)
            ->allowEmptyString('domain');

        $validator
            ->boolean('verified')
            ->notEmptyString('verified');

        $validator
            ->scalar('plan')
            ->maxLength('plan', 30)
            ->notEmptyString('plan');

        $validator
            ->scalar('billing_email')
            ->maxLength('billing_email', 255)
            ->allowEmptyString('billing_email');

        $validator
            ->integer('owner_user_id')
            ->notEmptyString('owner_user_id')
            ->add('owner_user_id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        return $validator
            ->email('auth_email', false, 'Invalid email')
            ->allowEmptyString('auth_email') // 必須化はコントローラ側の運用に合わせて
            ->minLength('auth_password', 8, 'Min 8 chars')
            ->allowEmptyString('auth_password'); // 未入力→自動発行なら allow
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
        $rules->add($rules->isUnique(['slug']), ['errorField' => 'slug']);
        $rules->add($rules->existsIn('owner_user_id', 'Users'), ['errorField' => 'owner_user_id']);

        return $rules;
    }
}
