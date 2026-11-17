<?php
namespace App\Models;
use App\Core\Model;

class Dispatch extends Model {
    protected string $table = 'dispatches';
    protected bool $tenantScoped = true;
}
