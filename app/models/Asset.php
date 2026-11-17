<?php
namespace App\Models;
use App\Core\Model;

class Asset extends Model {
    protected string $table = 'assets';
    protected bool $tenantScoped = false;
}
