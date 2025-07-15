<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotifications extends AbstractMigration
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
        $table = $this->table('notifications');
        $table
            ->addColumn('user_id', 'integer') // 通知を受け取るユーザー
            ->addColumn('sender_id', 'integer') // 通知を発信したユーザー
            ->addColumn('portfolio_id', 'integer', ['null' => true]) // 関連投稿（あれば）
            ->addColumn('type', 'string', ['limit' => 50]) // 'like' など
            ->addColumn('is_read', 'boolean', ['default' => false])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->create();
    }
}
