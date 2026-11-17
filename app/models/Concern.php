<?php
// app/models/Concern.php
namespace App\Models;
use App\Core\Model;

class Concern extends Model {
    protected string $table = 'concerns';
    protected bool $tenantScoped = false;
}
