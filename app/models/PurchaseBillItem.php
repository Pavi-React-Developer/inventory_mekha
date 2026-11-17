<?php
namespace App\Models;
use App\Core\Model;

class PurchaseBillItem extends Model {
    protected string $table = 'purchase_bill_items';
    protected bool $tenantScoped = true;
}
