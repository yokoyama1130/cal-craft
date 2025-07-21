<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddStructuredMechanicalFieldsToPortfolios extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('portfolios');
        $table->addColumn('purpose', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('basic_spec', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('parts_list', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('processing_notes', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('analysis_result', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('reference_links', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->update();
    }
}
