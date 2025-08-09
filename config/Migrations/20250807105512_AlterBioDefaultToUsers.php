<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterBioDefaultToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    // generated migration file
    public function change(): void
    {
        $this->table('users')
            ->changeColumn('bio', 'text', [
                'default' => '',      // 空文字をデフォルトにする
                'null' => true        // もしくは null を許容（どちらかでOK）
            ])
            ->update();
    }

}
