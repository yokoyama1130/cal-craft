<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \App\Model\Table\PortfoliosTable&\Cake\ORM\Association\HasMany $Portfolios
 *
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class UsersTable extends Table
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
    
        $this->setTable('users');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
    
        // 既存のアソシエーション
        $this->hasMany('Portfolios');
        $this->hasMany('Likes');
    
        // 👇 フォロワー（自分をフォローしているユーザーたち）
        $this->hasMany('Followers', [
            'className' => 'Follows',
            'foreignKey' => 'followed_id'
        ]);
    
        // 👇 フォロー中（自分がフォローしているユーザーたち）
        $this->hasMany('Followings', [
            'className' => 'Follows',
            'foreignKey' => 'follower_id'
        ]);

        $this->hasMany('Comments', [
            'foreignKey' => 'user_id',
            'dependent' => true,
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
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->notEmptyString('password')
            ->minLength('password', 8, '8文字以上にしてください。');

        $validator
            ->scalar('bio')
            ->allowEmptyString('bio');

        return $validator;
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
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        return $rules;
    }

    // src/Model/Table/UsersTable.php
    public function findAuth(\Cake\ORM\Query $query, array $options)
    {
        return $query->where(['email_verified' => true]);
    }

    public function validationEmailChange(Validator $validator): Validator
    {
        $v = new Validator();
        $v->email('new_email', false, '正しいメール形式で入力してください。')
          ->requirePresence('new_email')
          ->notEmptyString('new_email', '新しいメールアドレスを入力してください。')
          ->add('new_email', 'unique', [
              'rule' => function ($value) {
                  // 既存email と他ユーザーの new_email に重複しない
                  return !$this->exists(['email' => $value]) && !$this->exists(['new_email' => $value]);
              },
              'message' => 'このメールは既に使用されています。'
          ]);
        return $v;
    }

    public function validationPasswordChange(Validator $validator): Validator
    {
        $v = new Validator();
        $v->minLength('password', 8, '8文字以上にしてください。')
          ->add('password', 'complexity', [
              'rule' => function (string $value) {
                  $score = (int)preg_match('/[a-z]/', $value)
                         + (int)preg_match('/[A-Z]/', $value)
                         + (int)preg_match('/\d/', $value)
                         + (int)preg_match('/[^a-zA-Z0-9]/', $value);
                  return $score >= 2;
              },
              'message' => '大文字・小文字・数字・記号のうち2種類以上を含めてください。'
          ]);
        return $v;
    }
}
