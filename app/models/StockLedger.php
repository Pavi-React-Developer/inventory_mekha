<?php
namespace App\Models;
use App\Core\Model;

class StockLedger extends Model {
    protected string $table = 'stock_ledger';
    protected bool $tenantScoped = true;
}
