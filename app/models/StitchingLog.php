<?php
namespace App\Models;
use App\Core\Model;

class StitchingLog extends Model {
    protected string $table = 'stitching_logs';
    protected bool $tenantScoped = true;
}
