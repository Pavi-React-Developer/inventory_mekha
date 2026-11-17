<?php
namespace App\Models;
use App\Core\Model;

class Module extends Model {
    protected string $table = 'modules';
    protected bool $tenantScoped = false;
}
