<?php
// app/services/CashService.php - Cash & Account Ledger

namespace App\Services;

use App\Core\Auth;
use App\Core\DB;
use Exception;

class CashService {
    /**
     * Post a cash/bank transaction
     */
    public static function postTransaction(
        int $concernId,
        int $accountId,
        string $txnDate,
        string $type, // 'credit' or 'debit'
        float $amount,
        string $refModule,
        ?int $refId = null,
        ?string $description = null,
        ?int $userId = null
    ): void {
        if ($amount <= 0) {
            throw new Exception("Transaction amount must be greater than zero.");
        }

        $type = strtolower($type);
        if (!in_array($type, ['credit', 'debit'])) {
            throw new Exception("Invalid transaction type. Must be 'credit' or 'debit'.");
        }

        $userId = $userId ?? Auth::id();

        $hasTx = DB::pdo()->inTransaction();
        if (!$hasTx) {
            DB::beginTransaction();
        }

        try {
            // Check account belongs to concern
            $acc = DB::fetch(
                "SELECT id, balance FROM cash_accounts WHERE id = ? AND concern_id = ? FOR UPDATE",
                [$accountId, $concernId]
            );
            if (!$acc) {
                throw new Exception("Cash account not found.");
            }

            $currentBalance = (float)$acc['balance'];
            $newBalance = $type === 'credit' ? ($currentBalance + $amount) : ($currentBalance - $amount);

            // Update balance
            DB::query(
                "UPDATE cash_accounts SET balance = ? WHERE id = ? AND concern_id = ?",
                [$newBalance, $accountId, $concernId]
            );

            // Record transaction
            DB::query(
                "INSERT INTO cash_transactions (concern_id, account_id, txn_date, type, amount, ref_module, ref_id, description, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$concernId, $accountId, $txnDate, $type, $amount, $refModule, $refId, $description, $userId]
            );

            if (!$hasTx) {
                DB::commit();
            }
        } catch (\Throwable $e) {
            if (!$hasTx) {
                DB::rollBack();
            }
            throw $e;
        }
    }
}
