<?php
namespace App\Models;
use App\Core\Model;

class Category extends Model {
    protected string $table = 'categories';
    protected bool $tenantScoped = true;
}
