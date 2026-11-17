<?php
namespace App\Models;
use App\Core\Model;

class Expense extends Model {
    protected string $table = 'expenses';
    protected bool $tenantScoped = true;
}
