<?php
namespace App\Models;
use App\Core\Model;

class CuttingLog extends Model {
    protected string $table = 'cutting_logs';
    protected bool $tenantScoped = true;
}
