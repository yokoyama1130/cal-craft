<?php
use Migrations\AbstractMigration;

class AddCompanyIdToPortfolios extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('portfolios');

        if (!$table->hasColumn('company_id')) {
            $table->addColumn('company_id', 'integer', [
                'null'   => true,
                'signed' => true,   // ← companies.id が signed なので合わせる
                'after'  => 'user_id',
            ])->addIndex(['company_id'], ['name' => 'idx_portfolios_company_id'])
              ->update();
        } else {
            $table->changeColumn('company_id', 'integer', [
                'null'   => true,
                'signed' => true,   // ← 明示して揃える
            ])->update();
        }

        // 外部キー
        $table->addForeignKey('company_id', 'companies', 'id', [
            'delete'     => 'SET_NULL',
            'update'     => 'CASCADE',
            'constraint' => 'fk_portfolios_company_id',
        ])->update();
    }
}
