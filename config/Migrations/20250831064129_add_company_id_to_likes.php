<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCompanyIdToLikes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('likes');
        if (!$table->hasColumn('company_id')) {
            $table->addColumn('company_id', 'integer', ['null' => true, 'after' => 'user_id'])
                  ->addIndex(['company_id'])
                  ->update();
        }

        // 既存に無ければユニークIndexを作成（どちらか片方がNULLでもユニークが効く）
        if (!$table->hasIndex(['portfolio_id', 'user_id'])) {
            $table->addIndex(['portfolio_id', 'user_id'], ['unique' => true, 'name' => 'uix_likes_user']);
        }
        if (!$table->hasIndex(['portfolio_id', 'company_id'])) {
            $table->addIndex(['portfolio_id', 'company_id'], ['unique' => true, 'name' => 'uix_likes_company']);
        }
        $table->update();

        // 外部キー（InnoDB前提）
        $this->execute('
            ALTER TABLE likes
            ADD CONSTRAINT fk_likes_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE CASCADE ON UPDATE CASCADE
        ');
    }
}

