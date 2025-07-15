<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFollowers extends AbstractMigration
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
        $table = $this->table('followers');
        $table
            ->addColumn('follower_id', 'integer') // フォローする側
            ->addColumn('followee_id', 'integer') // フォローされる側
            ->addColumn('created', 'datetime')
            ->addIndex(['follower_id', 'followee_id'], ['unique' => true]) // 一人一回だけ
            ->create();
    }
}
