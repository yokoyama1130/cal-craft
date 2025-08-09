<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmailChangeFieldsToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('users');
        $table
            ->addColumn('new_email', 'string', ['null' => true, 'default' => null])
            ->addColumn('email_change_token', 'string', ['null' => true, 'default' => null])
            ->addColumn('email_change_expires', 'datetime', ['null' => true, 'default' => null])
            ->update();
    }
}
