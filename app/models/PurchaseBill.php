<?php
namespace App\Models;
use App\Core\Model;

class PurchaseBill extends Model {
    protected string $table = 'purchase_bills';
    protected bool $tenantScoped = true;
}
