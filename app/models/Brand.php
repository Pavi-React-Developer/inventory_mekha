<?php
namespace App\Models;
use App\Core\Model;

class Brand extends Model {
    protected string $table = 'brands';
    protected bool $tenantScoped = true;
}
