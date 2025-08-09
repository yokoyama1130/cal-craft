<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterIconPathDefaultToUsers extends AbstractMigration
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
        $this->table('users')
            ->changeColumn('icon_path', 'string', [
                'default' => '',     // 空文字を許可
                'null' => true,      // NULL も許可する場合は true に（任意）
            ])
            ->update();
    }    
}
