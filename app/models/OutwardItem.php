<?php
namespace App\Models;
use App\Core\Model;

class OutwardItem extends Model {
    protected string $table = 'outward_items';
    protected bool $tenantScoped = true;
}
