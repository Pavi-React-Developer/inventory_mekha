<?php
namespace App\Models;
use App\Core\Model;

class LabelJob extends Model {
    protected string $table = 'label_jobs';
    protected bool $tenantScoped = true;
}
