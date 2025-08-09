<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class ModifyEmailTokenNullable extends AbstractMigration
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
        $this->table('users')
            ->changeColumn('email_token', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 255,
            ])
            ->update();
    }
}
