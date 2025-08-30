<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateCompanyInvoices extends AbstractMigration
{
    public function up(): void
    {
        $t = $this->table('company_invoices', ['id' => true, 'signed' => false]);
        $t->addColumn('company_id', 'integer', ['null' => false, 'signed' => false])
          ->addColumn('stripe_customer_id', 'string', ['null' => true, 'limit' => 191])
          ->addColumn('stripe_subscription_id', 'string', ['null' => true, 'limit' => 191])
          ->addColumn('stripe_invoice_id', 'string', ['null' => true, 'limit' => 191])
          ->addColumn('stripe_payment_intent_id', 'string', ['null' => true, 'limit' => 191])
          ->addColumn('plan', 'string', ['null' => true, 'limit' => 50]) // 反映した(or予定)プラン
          ->addColumn('amount', 'integer', ['null' => true])             // 最小単位（JPYなら円）
          ->addColumn('currency', 'string', ['null' => true, 'limit' => 10])
          ->addColumn('status', 'string', ['null' => true, 'limit' => 50]) // paid, open, failed など
          ->addColumn('paid_at', 'datetime', ['null' => true])
          ->addColumn('raw_payload', 'text', ['null' => true])            // Webhookの生JSON保管（監査用）
          ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
          ->addColumn('modified', 'datetime', ['null' => true])
          ->addIndex(['company_id'])
          ->addIndex(['stripe_invoice_id'], ['unique' => false])
          ->addIndex(['stripe_payment_intent_id'], ['unique' => false])
          ->create();
    }

    public function down(): void
    {
        $this->table('company_invoices')->drop()->save();
    }
}
