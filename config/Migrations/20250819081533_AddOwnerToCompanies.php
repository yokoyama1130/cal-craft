<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddOwnerToCompanies extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        // Users.id が integer なら integer、biginteger なら biginteger に合わせてね
        $fkType = 'integer';
    
        $t = $this->table('companies');
    
        // owner_user_id を追加（1ユーザー=1社なので unique）
        if (!$t->hasColumn('owner_user_id')) {
            $t->addColumn('owner_user_id', $fkType, ['null' => false]);
        }
    
        // ユニーク制約（同じユーザーが2社持てない）
        if (!$t->hasIndex(['owner_user_id'])) {
            $t->addIndex(['owner_user_id'], ['unique' => true, 'name' => 'UNQ_companies_owner']);
        }
    
        // 外部キー（ユーザー削除時に会社も消すなら CASCADE、残すなら RESTRICT）
        $t->addForeignKey('owner_user_id', 'users', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
            'constraint' => 'FK_companies_owner_user_id',
        ])->update();
    }    
}
