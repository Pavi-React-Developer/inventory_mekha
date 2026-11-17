<?php
namespace App\Models;
use App\Core\Model;

class Catalog extends Model {
    protected string $table = 'catalogs';
    protected bool $tenantScoped = true;
}
