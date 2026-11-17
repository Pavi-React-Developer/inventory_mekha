<?php
namespace App\Models;
use App\Core\Model;

class CustomerRequest extends Model {
    protected string $table = 'customer_requests';
    protected bool $tenantScoped = true;
}
