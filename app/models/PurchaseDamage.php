<?php
namespace App\Models;
use App\Core\Model;

class PurchaseDamage extends Model {
    protected string $table = 'purchase_damages';
    protected bool $tenantScoped = true;
}
