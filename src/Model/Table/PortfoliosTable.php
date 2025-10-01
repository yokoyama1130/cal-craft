<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Portfolios Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @property \App\Model\Table\LikesTable&\Cake\ORM\Association\HasMany $Likes
 * @property \App\Model\Table\CommentsTable&\Cake\ORM\Association\HasMany $Comments
 * @method \App\Model\Entity\Portfolio newEmptyEntity()
 * @method \App\Model\Entity\Portfolio newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Portfolio[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Portfolio get($primaryKey, $options = [])
 * @method \App\Model\Entity\Portfolio findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Portfolio patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Portfolio[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Portfolio|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Portfolio saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Portfolio[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Portfolio[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Portfolio[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Portfolio[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class PortfoliosTable extends Table
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

        $this->setTable('portfolios');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            // 'joinType' => 'INNER', // ← 削除（LEFT相当）
        ]);

        $this->belongsTo('Companies', [
            'foreignKey' => 'company_id',
        ]);

        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('Likes');
        $this->hasMany('Comments', [
            'foreignKey' => 'portfolio_id',
            'dependent' => true,
        ]);
    }

    /**
     * バリデーションルールを定義
     *
     * - user_id: 任意（企業投稿を許可するため）
     * - company_id: 任意（ユーザー投稿を許可するため）
     * - category_id: 必須
     * - title: 必須、最大255文字
     * - description: 必須
     * - thumbnail: 任意、最大255文字
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator 修正済みバリデータ
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id'); // ← 会社投稿を許可

        $validator
            ->integer('category_id')
            ->notEmptyString('category_id', 'ジャンルを選択してください');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('description')
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        // サムネ必須にしないなら allowEmpty に変更
        $validator
            ->scalar('thumbnail')
            ->maxLength('thumbnail', 255)
            ->allowEmptyString('thumbnail');

        return $validator;
    }

    /**
     * アプリケーションルール（整合性チェック）を定義
     *
     * - user_id が Users に存在する場合は存在チェック
     * - company_id が Companies に存在する場合は存在チェック
     * - user_id または company_id のどちらか必須
     * - 両方同時指定は不可
     *
     * @param \Cake\ORM\RulesChecker $rules ルールチェッカー
     * @return \Cake\ORM\RulesChecker 修正済みルールチェッカー
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        // 片方があれば existsIn を確認する
        $rules->add($rules->existsIn(['user_id'], 'Users'), [
            'errorField' => 'user_id',
            'allowNullableNulls' => true,
        ]);
        $rules->add($rules->existsIn(['company_id'], 'Companies'), [
            'errorField' => 'company_id',
            'allowNullableNulls' => true,
        ]);

        // どちらか一方必須
        $rules->add(function ($entity, $options) {
            return (bool)($entity->user_id || $entity->company_id);
        }, 'OwnerRequired', [
            'errorField' => 'owner',
            'message' => 'ユーザーIDまたは会社IDのいずれかを指定してください。',
        ]);

        // 両方同時はNG
        $rules->add(function ($entity, $options) {
            return !($entity->user_id && $entity->company_id);
        }, 'OwnerExclusive', [
            'errorField' => 'owner',
            'message' => 'ユーザーと会社の両方を同時に設定することはできません。',
        ]);

        return $rules;
    }
}
