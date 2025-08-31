<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddOwnerUserIdToCompanies extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('companies');
        $table
            ->addColumn('owner_user_id', 'integer', ['null' => true, 'after' => 'id'])
            ->addIndex(['owner_user_id'])
            ->addForeignKey('owner_user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_companies_owner_user',
            ])
            ->update();
    }
}
