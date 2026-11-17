<?php
// app/controllers/FinanceController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\CashService;
use Exception;

class FinanceController extends Controller {
    // 1. EXPENSES
    public function expense(): void {
        $cid = Auth::concernId();

        $expenses = DB::fetchAll(
            "SELECT e.*, ca.name AS account_name, u.name AS user_name
             FROM expenses e
             LEFT JOIN cash_accounts ca ON e.account_id = ca.id
             LEFT JOIN users u ON e.created_by = u.id
             WHERE e.concern_id = ?
             ORDER BY e.id DESC",
            [$cid]
        );

        $accounts = DB::fetchAll("SELECT * FROM cash_accounts WHERE concern_id = ? AND status = 'active'", [$cid]);
        $totalExpense = array_sum(array_column($expenses, 'amount'));

        $this->render('concern/expense', [
            'pageTitle'     => 'Operational Expense Registry',
            'currentModule' => 'expense',
            'expenses'      => $expenses,
            'accounts'      => $accounts,
            'totalExpense'  => $totalExpense,
        ]);
    }

    public function storeExpense(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

        $expDate   = $_POST['expense_date'] ?: date('Y-m-d');
        $category  = trim($_POST['category'] ?? 'Miscellaneous');
        $amount    = (float)($_POST['amount'] ?? 0);
        $payMode   = trim($_POST['payment_mode'] ?? 'Cash');
        $accountId = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;
        $desc      = trim($_POST['description'] ?? '');

        if ($amount <= 0) {
            $this->flash('error', 'Expense amount must be greater than zero.');
            $this->redirect('/m/expense');
        }

        DB::beginTransaction();
        try {
            $expNo = 'EXP-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO expenses (concern_id, expense_no, expense_date, category, amount, payment_mode, account_id, description, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $expNo, $expDate, $category, $amount, $payMode, $accountId, $desc, Auth::id()]
            );
            $expId = (int)DB::lastInsertId();

            // If linked to cash account, post debit transaction
            if ($accountId) {
                CashService::postTransaction(
                    $cid,
                    $accountId,
                    $expDate,
                    'debit',
                    $amount,
                    'Expense',
                    $expId,
                    "Expense [{$category}]: {$desc}",
                    Auth::id()
                );
            }

            DB::commit();

            $this->flash('success', "Expense '{$expNo}' recorded for ₹" . number_format($amount, 2) . ".");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to log expense: ' . $e->getMessage());
        }

        $this->redirect('/m/expense');
    }

    // 2. CASH ACCOUNTS
    public function cash(): void {
        $cid = Auth::concernId();

        $accounts = DB::fetchAll(
            "SELECT ca.*, 
                    (SELECT COUNT(*) FROM cash_transactions ct WHERE ct.account_id = ca.id) AS tx_count
             FROM cash_accounts ca
             WHERE ca.concern_id = ?
             ORDER BY ca.id ASC",
            [$cid]
        );

        $transactions = DB::fetchAll(
            "SELECT ct.*, ca.name AS account_name, u.name AS user_name
             FROM cash_transactions ct
             JOIN cash_accounts ca ON ct.account_id = ca.id
             LEFT JOIN users u ON ct.created_by = u.id
             WHERE ct.concern_id = ?
             ORDER BY ct.id DESC LIMIT 20",
            [$cid]
        );

        $totalCashInHand = array_reduce($accounts, fn($sum, $a) => $sum + $a['balance'], 0);

        $this->render('concern/cash', [
            'pageTitle'       => 'Cash Accounts & Banking Books',
            'currentModule'   => 'cash',
            'accounts'        => $accounts,
            'transactions'    => $transactions,
            'totalCashInHand' => $totalCashInHand,
        ]);
    }

    public function storeAccount(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

        $name    = trim($_POST['name'] ?? '');
        $type    = $_POST['account_type'] ?? 'cash';
        $accNo   = trim($_POST['account_number'] ?? '');
        $opening = (float)($_POST['opening_balance'] ?? 0);

        if (empty($name)) {
            $this->flash('error', 'Account Name is required.');
            $this->redirect('/m/cash');
        }

        DB::query(
            "INSERT INTO cash_accounts (concern_id, name, account_type, account_number, balance, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'active', NOW())",
            [$cid, $name, $type, $accNo, $opening]
        );

        $this->flash('success', "Cash Account '{$name}' created with opening balance of ₹" . number_format($opening, 2) . ".");
        $this->redirect('/m/cash');
    }

    // 3. CASH FLOW (Read-only view over Billing, Purchase, Expense and Cash)
    public function cashFlow(): void {
        $cid = Auth::concernId();

        // Summary totals
        $totalInflow = (float)DB::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE concern_id = ? AND type = 'credit'",
            [$cid]
        );

        $totalOutflow = (float)DB::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE concern_id = ? AND type = 'debit'",
            [$cid]
        );

        $netFlow = $totalInflow - $totalOutflow;

        // Unified Ledger Feed
        $feed = DB::fetchAll(
            "SELECT ct.*, ca.name AS account_name
             FROM cash_transactions ct
             JOIN cash_accounts ca ON ct.account_id = ca.id
             WHERE ct.concern_id = ?
             ORDER BY ct.txn_date DESC, ct.id DESC",
            [$cid]
        );

        $this->render('concern/cash_flow', [
            'pageTitle'     => 'Cash Flow Statement',
            'currentModule' => 'cash_flow',
            'totalInflow'   => $totalInflow,
            'totalOutflow'  => $totalOutflow,
            'netFlow'       => $netFlow,
            'feed'          => $feed,
        ]);
    }
}
