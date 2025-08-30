<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

final class AddStripeToCompanies extends AbstractMigration
{
    public function up(): void
    {
        $t = $this->table('companies');
        if (!$t->hasColumn('stripe_customer_id')) {
            $t->addColumn('stripe_customer_id', 'string', ['limit' => 191, 'null' => true, 'after' => 'auth_email']);
        }
        if (!$t->hasColumn('stripe_subscription_id')) {
            $t->addColumn('stripe_subscription_id', 'string', ['limit' => 191, 'null' => true, 'after' => 'stripe_customer_id']);
        }
        if (!$t->hasColumn('plan')) {
            $t->addColumn('plan', 'string', ['limit' => 32, 'null' => false, 'default' => 'free', 'after' => 'stripe_subscription_id']);
        }
        $t->update();
    }

    public function down(): void
    {
        $t = $this->table('companies');
        if ($t->hasColumn('plan')) $t->removeColumn('plan');
        if ($t->hasColumn('stripe_subscription_id')) $t->removeColumn('stripe_subscription_id');
        if ($t->hasColumn('stripe_customer_id')) $t->removeColumn('stripe_customer_id');
        $t->update();
    }
}

