<?php
namespace App\Models;
use App\Core\Model;

class SalesInvoice extends Model {
    protected string $table = 'sales_invoices';
    protected bool $tenantScoped = true;
}
