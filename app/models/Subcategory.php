<?php
namespace App\Models;
use App\Core\Model;

class Subcategory extends Model {
    protected string $table = 'subcategories';
    protected bool $tenantScoped = true;
}
