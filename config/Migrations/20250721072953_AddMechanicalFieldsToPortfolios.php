<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddMechanicalFieldsToPortfolios extends AbstractMigration
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
        $table->addColumn('tool_used', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('material_used', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('processing_method', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('analysis_method', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('development_period', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('design_url', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('design_description', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('mechanical_notes', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->update();
    }
}
