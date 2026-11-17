<?php
// app/models/CashierShift.php

namespace App\Models;

use App\Core\Model;

class CashierShift extends Model {
    protected string $table = 'cashier_shifts';
    protected bool $tenantScoped = true;
}
