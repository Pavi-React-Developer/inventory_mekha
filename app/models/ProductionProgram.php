<?php
namespace App\Models;
use App\Core\Model;

class ProductionProgram extends Model {
    protected string $table = 'production_programs';
    protected bool $tenantScoped = true;
}
