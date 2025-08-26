<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddAuthFieldsToCompanies extends AbstractMigration
{
    public function up(): void
    {
        $t = $this->table('companies');

        // 外部キーがあれば外す（列指定でOK）
        if (method_exists($t, 'hasForeignKey') && $t->hasForeignKey('owner_user_id')) {
            $t->dropForeignKey('owner_user_id');
        }

        // インデックス（もしあれば）も外す保険
        if ($t->hasIndex(['owner_user_id'])) {
            $t->removeIndex(['owner_user_id']);
        }

        // 列を削除
        if ($t->hasColumn('owner_user_id')) {
            $t->removeColumn('owner_user_id');
        }

        $t->update();
    }

    public function down(): void
    {
        $t = $this->table('companies');

        // 列を復元（型は安全側で BIGINT UNSIGNED NULL）
        if (!$t->hasColumn('owner_user_id')) {
            $t->addColumn('owner_user_id', 'biginteger', [
                'null'    => true,
                'signed'  => false,
                'default' => null,
                'after'   => 'id', // 好きな位置に調整
                'comment' => 'Deprecated: previously linked to users.id',
            ]);
        }

        // 必要ならインデックス
        if (!$t->hasIndex(['owner_user_id'])) {
            $t->addIndex(['owner_user_id']);
        }

        // 外部キー（任意。復元時だけ付けたい場合）
        $t->addForeignKey('owner_user_id', 'users', 'id', [
            'delete'     => 'SET_NULL',
            'update'     => 'NO_ACTION',
            'constraint' => 'fk_companies_owner_user_id',
        ]);

        $t->update();
    }
}
