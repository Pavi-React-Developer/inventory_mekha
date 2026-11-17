<?php
namespace App\Models;
use App\Core\Model;

class Outward extends Model {
    protected string $table = 'outwards';
    protected bool $tenantScoped = true;
}
