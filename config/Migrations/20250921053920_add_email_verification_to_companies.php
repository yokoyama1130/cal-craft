<?php
declare(strict_types=1);

// config/Migrations/20250921_AddEmailVerificationToCompanies.php
use Migrations\AbstractMigration;

class AddEmailVerificationToCompanies extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('companies');
        $table
            ->addColumn('email_verified', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'auth_password',
            ])
            ->addColumn('email_token', 'string', [
                'limit' => 64,
                'null' => true,
                'default' => null,
                'after' => 'email_verified',
            ])
            ->addIndex(['auth_email'], ['unique' => true]) // 既にあれば不要
            ->update();
    }
}
