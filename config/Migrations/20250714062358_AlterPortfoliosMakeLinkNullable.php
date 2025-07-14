<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterPortfoliosMakeLinkNullable extends AbstractMigration
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
        $this->table('portfolios')
            ->changeColumn('link', 'string', ['null' => true])
            ->update();
    }
}
