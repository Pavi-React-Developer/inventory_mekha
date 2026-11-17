<?php
namespace App\Models;
use App\Core\Model;

class InwardItem extends Model {
    protected string $table = 'inward_items';
    protected bool $tenantScoped = true;
}
