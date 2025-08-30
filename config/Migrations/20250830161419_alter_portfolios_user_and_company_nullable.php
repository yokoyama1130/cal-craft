<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterPortfoliosUserAndCompanyNullable extends AbstractMigration
{
    public function up(): void
    {
        // 1) 既存の user_id / company_id の FK を動的に削除
        $rows = $this->fetchAll("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'portfolios'
              AND REFERENCED_TABLE_NAME IS NOT NULL
              AND COLUMN_NAME IN ('user_id', 'company_id')
        ");
        foreach ($rows as $r) {
            $name = $r['CONSTRAINT_NAME'];
            $this->execute("ALTER TABLE `portfolios` DROP FOREIGN KEY `{$name}`");
        }

        // 2) 参照先の型/unsigned/文字セットを取得し、portfolios 側を完全一致に変更（NULL 許可）
        $this->syncColumnToRef('portfolios', 'user_id', 'users', 'id');
        $this->syncColumnToRef('portfolios', 'company_id', 'companies', 'id');

        // 3) FK を再作成（削除時は NULL、更新は CASCADE）
        $this->execute("
            ALTER TABLE `portfolios`
              ADD CONSTRAINT `fk_portfolios_user_id`
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE,
              ADD CONSTRAINT `fk_portfolios_company_id`
                FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }

    public function down(): void
    {
        // 逆は環境依存なので、FK だけ落とす（必要ならここに元の型へ戻す処理を足してください）
        $this->execute("
            ALTER TABLE `portfolios`
              DROP FOREIGN KEY `fk_portfolios_user_id`,
              DROP FOREIGN KEY `fk_portfolios_company_id`
        ");
    }

    /**
     * 参照先カラムの定義に "完全一致" させて、対象カラムを NULL 許可で変更する。
     * - INT/BIGINT の unsigned/signed を含む
     * - CHAR/VARCHAR の場合は文字セット/照合順序も含めて一致させる
     */
    private function syncColumnToRef(string $table, string $col, string $refTable, string $refCol): void
    {
        $ref = $this->fetchRow("
            SELECT DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$refTable}'
              AND COLUMN_NAME = '{$refCol}'
            LIMIT 1
        ");

        if (!$ref) {
            throw new \RuntimeException("Cannot find {$refTable}.{$refCol} definition.");
        }

        $dataType   = strtolower($ref['DATA_TYPE']);          // int / bigint / char / varchar / etc.
        $columnType = strtolower($ref['COLUMN_TYPE']);         // e.g. 'int unsigned', 'bigint(20) unsigned', 'char(36)'
        $charLen    = $ref['CHARACTER_MAXIMUM_LENGTH'];
        $charset    = $ref['CHARACTER_SET_NAME'];
        $collation  = $ref['COLLATION_NAME'];

        // 文字列型は文字セット/照合順序も揃える（FKの要件）
        if (in_array($dataType, ['char', 'varchar'], true)) {
            $len = (int)$charLen;
            $charsetSql  = $charset   ? " CHARACTER SET `{$charset}`" : '';
            $collateSql  = $collation ? " COLLATE `{$collation}`"     : '';
            $sql = sprintf(
                "ALTER TABLE `%s` MODIFY `%s` %s(%d)%s%s NULL",
                $table, $col, strtoupper($dataType), $len, $charsetSql, $collateSql
            );
            $this->execute($sql);
            return;
        }

        // 数値型は COLUMN_TYPE をそのまま使う（unsigned/signed を完全一致させる）
        // 例: COLUMN_TYPE = 'int unsigned', 'bigint(20) unsigned'
        $sql = sprintf(
            "ALTER TABLE `%s` MODIFY `%s` %s NULL",
            $table, $col, strtoupper($columnType) // MySQL は大文字小文字を気にしないが見やすさのため
        );
        $this->execute($sql);
    }
}

