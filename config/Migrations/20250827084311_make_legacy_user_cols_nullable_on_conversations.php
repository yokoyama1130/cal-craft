<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class MakeLegacyUserColsNullableOnConversations extends AbstractMigration
{
    public function up(): void
    {
        $t = $this->table('conversations');

        // 既存の外部キーがあれば外す（名前分からなくても列指定でOK）
        if (method_exists($t, 'hasForeignKey') && $t->hasForeignKey('user1_id')) {
            $t->dropForeignKey('user1_id');
        }
        if (method_exists($t, 'hasForeignKey') && $t->hasForeignKey('user2_id')) {
            $t->dropForeignKey('user2_id');
        }

        // NOT NULL → NULL 許可
        if ($t->hasColumn('user1_id')) {
            $t->changeColumn('user1_id', 'integer', [
                'null' => true,
                'signed' => false,
                'default' => null,
            ]);
        }
        if ($t->hasColumn('user2_id')) {
            $t->changeColumn('user2_id', 'integer', [
                'null' => true,
                'signed' => false,
                'default' => null,
            ]);
        }

        $t->update();
    }

    public function down(): void
    {
        $t = $this->table('conversations');

        // 元に戻す（必要なら外部キーも貼り直す）
        if ($t->hasColumn('user1_id')) {
            $t->changeColumn('user1_id', 'integer', [
                'null' => false,
                'signed' => false,
                'default' => null,
            ]);
        }
        if ($t->hasColumn('user2_id')) {
            $t->changeColumn('user2_id', 'integer', [
                'null' => false,
                'signed' => false,
                'default' => null,
            ]);
        }

        // 外部キーを戻したい場合（constraint名は環境に合わせて変更）
        // $t->addForeignKey('user1_id', 'users', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION', 'constraint' => 'fk_conversations_user1_id']);
        // $t->addForeignKey('user2_id', 'users', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION', 'constraint' => 'fk_conversations_user2_id']);

        $t->update();
    }
}

