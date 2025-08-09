<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddEmailVerificationToUsers extends AbstractMigration
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
        $table = $this->table('users');
        $table->addColumn('email_verified', 'boolean', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('email_token', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->update();
    }
}
