<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddPolymorphicToMessages extends AbstractMigration
{
    public function up(): void
    {
        $t = $this->table('messages');

        $t->addColumn('sender_type', 'string', [
            'limit' => 20,
            'null' => false,
            'default' => 'user',
            'after' => 'conversation_id',
            'comment' => 'sender type: user/company',
        ]);
        $t->addColumn('sender_ref_id', 'integer', [
            'null' => false,
            'signed' => false,
            'after' => 'sender_type',
        ]);

        $t->update();

        // 既存データは sender_type = 'user' で移行
        $this->execute("
            UPDATE messages
            SET sender_type = 'user', sender_ref_id = sender_id
        ");
    }

    public function down(): void
    {
        $t = $this->table('messages');
        $t->removeColumn('sender_type')
          ->removeColumn('sender_ref_id')
          ->update();
    }
}

