<?php
namespace App\Models;
use App\Core\Model;

class ReturnExchange extends Model {
    protected string $table = 'returns_exchanges';
    protected bool $tenantScoped = true;
}
