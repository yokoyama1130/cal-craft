<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class FixPdfFieldsNullableInPortfolios extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('portfolios');

        if ($table->hasColumn('drawing_pdf_path')) {
            $table->changeColumn('drawing_pdf_path', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
                'comment' => '図面PDFの相対パス',
            ]);
        }

        if ($table->hasColumn('supplement_pdf_paths')) {
            $table->changeColumn('supplement_pdf_paths', 'text', [
                'null' => true,
                'default' => null, // TEXT でも NULL はOK（デフォルト値は不可）
                'comment' => '補足PDFの相対パス(配列JSON)',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('portfolios');

        // 先に NULL を埋めてから NOT NULL に戻す（違反回避）
        if ($table->hasColumn('drawing_pdf_path')) {
            // NULLは空文字に置換
            $this->execute("UPDATE portfolios SET drawing_pdf_path = '' WHERE drawing_pdf_path IS NULL");

            // NOT NULL（デフォルト値は付けない：元の挙動に近い）
            $table->changeColumn('drawing_pdf_path', 'string', [
                'null' => false,
                'limit' => 255,
                'comment' => '図面PDFの相対パス',
            ]);
        }

        if ($table->hasColumn('supplement_pdf_paths')) {
            // TEXT はデフォルト値を持てないので、NULLを '[]' にしてから NOT NULL へ
            $this->execute("UPDATE portfolios SET supplement_pdf_paths = '[]' WHERE supplement_pdf_paths IS NULL");

            $table->changeColumn('supplement_pdf_paths', 'text', [
                'null' => false,        // デフォルト値は指定しない（TEXT不可）
                'comment' => '補足PDFの相対パス(配列JSON)',
            ]);
        }

        $table->update();
    }
}

