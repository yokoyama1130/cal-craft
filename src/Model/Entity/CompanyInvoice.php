<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class CompanyInvoice extends Entity
{
    protected $_accessible = [
        'company_id' => true,
        'stripe_customer_id' => true,
        'stripe_subscription_id' => true,
        'stripe_invoice_id' => true,
        'stripe_payment_intent_id' => true,
        'plan' => true,
        'amount' => true,
        'currency' => true,
        'status' => true,
        'paid_at' => true,
        'raw_payload' => true,
        'created' => true,
        'modified' => true,
        'company' => true,
    ];
}
