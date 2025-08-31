<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddCompanyIdToComments extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('comments');

        // 会社コメント用の列（未追加なら）
        if (!$table->hasColumn('company_id')) {
            $table->addColumn('company_id', 'integer', ['null' => true, 'after' => 'user_id'])
                  ->addIndex(['company_id'])
                  ->update();
        }

        // ユーザー以外（会社）のコメントを入れられるように NULL 許可
        $this->execute('ALTER TABLE comments MODIFY user_id INT NULL;');

        // （任意・MySQL8+）どちらか一方が入っていることをDBで担保
        // $this->execute("ALTER TABLE comments
        //   ADD CONSTRAINT chk_comments_user_or_company
        //   CHECK (
        //     (user_id IS NOT NULL AND company_id IS NULL) OR
        //     (user_id IS NULL AND company_id IS NOT NULL)
        //   );");
    }
}

