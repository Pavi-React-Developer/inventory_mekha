<?php
namespace App\Models;
use App\Core\Model;

class CashTransaction extends Model {
    protected string $table = 'cash_transactions';
    protected bool $tenantScoped = true;
}
