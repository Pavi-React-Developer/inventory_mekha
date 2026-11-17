<?php
namespace App\Models;
use App\Core\Model;

class ConcernDamage extends Model {
    protected string $table = 'concern_damages';
    protected bool $tenantScoped = true;
}
