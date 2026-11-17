<?php
namespace App\Models;
use App\Core\Model;

class Inward extends Model {
    protected string $table = 'inwards';
    protected bool $tenantScoped = true;
}
