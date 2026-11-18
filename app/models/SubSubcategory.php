<?php
namespace App\Models;
use App\Core\Model;

class SubSubcategory extends Model {
    protected string $table = 'sub_subcategories';
    protected bool $tenantScoped = true;
}
