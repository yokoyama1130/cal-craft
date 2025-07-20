<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMessages extends AbstractMigration
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
    public function change()
    {
        $table = $this->table('messages');
        $table->addColumn('conversation_id', 'integer')
              ->addColumn('sender_id', 'integer')
              ->addColumn('content', 'text')
              ->addColumn('is_read', 'boolean', ['default' => false])
              ->addColumn('created', 'datetime')
              ->addColumn('modified', 'datetime')
              ->create();
    }
}
