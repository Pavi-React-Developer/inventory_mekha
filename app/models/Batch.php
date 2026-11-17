<?php
namespace App\Models;
use App\Core\Model;

class Batch extends Model {
    protected string $table = 'batches';
    protected bool $tenantScoped = true;
}
