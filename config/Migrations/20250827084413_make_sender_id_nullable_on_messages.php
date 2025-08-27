<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class MakeSenderIdNullableOnMessages extends AbstractMigration
{
    public function up(): void
    {
        $t = $this->table('messages');

        // 既存のFKがあれば外す（列名指定でOK）
        if (method_exists($t, 'hasForeignKey') && $t->hasForeignKey('sender_id')) {
            $t->dropForeignKey('sender_id');
        }

        if ($t->hasColumn('sender_id')) {
            $t->changeColumn('sender_id', 'integer', [
                'null'    => true,     // ← ここが重要
                'signed'  => false,
                'default' => null,
            ]);
        }

        $t->update();
    }

    public function down(): void
    {
        $t = $this->table('messages');

        if ($t->hasColumn('sender_id')) {
            $t->changeColumn('sender_id', 'integer', [
                'null'    => false,    // 元に戻す
                'signed'  => false,
                'default' => null,     // 必要なら default を外す/変える
            ]);
        }

        // 外部キーを戻したい場合（constraint名は環境に合わせて調整）
        // $t->addForeignKey('sender_id', 'users', 'id', [
        //     'delete' => 'NO_ACTION', 'update' => 'NO_ACTION',
        //     'constraint' => 'fk_messages_sender_id'
        // ]);

        $t->update();
    }
}
