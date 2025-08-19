<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateCompanies extends AbstractMigration
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
        $table = $this->table('companies');
        $table
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 160, 'null' => false])
            ->addIndex(['slug'], ['unique' => true])
            ->addColumn('website', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('industry', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('size', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('logo_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('domain', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('verified', 'boolean', ['default' => 0, 'null' => false])
            ->addColumn('plan', 'string', ['limit' => 30, 'default' => 'free', 'null' => false])
            ->addColumn('billing_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->create();
    }
}
