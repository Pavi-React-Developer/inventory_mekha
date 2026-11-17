<?php
namespace App\Models;
use App\Core\Model;

class CashAccount extends Model {
    protected string $table = 'cash_accounts';
    protected bool $tenantScoped = true;
}
