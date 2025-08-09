<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddDeletedAtToUsers extends AbstractMigration
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
        $table = $this->table('users');
    
        if (!$table->hasColumn('deleted_at')) {
            $table->addColumn('deleted_at', 'datetime', [
                'null' => true,
                'default' => null, // ← これ重要
                'comment' => 'アカウント削除日時'
            ]);
        }
    
        $table->update();
    }
}
