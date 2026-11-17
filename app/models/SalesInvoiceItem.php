<?php
namespace App\Models;
use App\Core\Model;

class SalesInvoiceItem extends Model {
    protected string $table = 'sales_invoice_items';
    protected bool $tenantScoped = true;
}
