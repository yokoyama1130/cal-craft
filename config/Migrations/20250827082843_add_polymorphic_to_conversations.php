<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddPolymorphicToConversations extends AbstractMigration
{
    public function up(): void
    {
        $t = $this->table('conversations');

        // 新しいカラムを追加
        $t->addColumn('p1_type', 'string', [
            'limit' => 20,
            'null' => false,
            'default' => 'user',
            'after' => 'id',
            'comment' => 'participant1 type: user/company',
        ]);
        $t->addColumn('p1_id', 'integer', [
            'null' => false,
            'signed' => false,
            'after' => 'p1_type',
        ]);
        $t->addColumn('p2_type', 'string', [
            'limit' => 20,
            'null' => false,
            'default' => 'user',
            'after' => 'p1_id',
            'comment' => 'participant2 type: user/company',
        ]);
        $t->addColumn('p2_id', 'integer', [
            'null' => false,
            'signed' => false,
            'after' => 'p2_type',
        ]);

        $t->update();

        // 既存データを user 固定で移行
        $this->execute("
            UPDATE conversations
            SET p1_type = 'user', p1_id = user1_id,
                p2_type = 'user', p2_id = user2_id
        ");
    }

    public function down(): void
    {
        $t = $this->table('conversations');
        $t->removeColumn('p1_type')
          ->removeColumn('p1_id')
          ->removeColumn('p2_type')
          ->removeColumn('p2_id')
          ->update();
    }
}

