<?php
namespace App\Models;
use App\Core\Model;

class QcInspection extends Model {
    protected string $table = 'qc_inspections';
    protected bool $tenantScoped = true;
}
