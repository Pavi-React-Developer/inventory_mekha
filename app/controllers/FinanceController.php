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

        $sql = "SELECT e.*, ca.name AS account_name, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM expenses e
                LEFT JOIN cash_accounts ca ON e.account_id = ca.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN concerns con ON e.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE e.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY e.id DESC";
        $expenses = DB::fetchAll($sql, $params);

        if ($cid) {
            $accounts = DB::fetchAll("SELECT * FROM cash_accounts WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $accounts = DB::fetchAll("SELECT * FROM cash_accounts WHERE status = 'active'");
        }

        $totalExpense = array_sum(array_column($expenses, 'amount'));

        $this->render('concern/expense', [
            'pageTitle'     => 'Operational Expense Registry',
            'currentModule' => 'expense',
            'expenses'      => $expenses,
            'accounts'      => $accounts,
            'totalExpense'  => $totalExpense,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeExpense(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/expense');
        }

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

        $sql = "SELECT ca.*, con.name AS concern_name, con.code AS concern_code,
                       (SELECT COUNT(*) FROM cash_transactions ct WHERE ct.account_id = ca.id) AS tx_count
                FROM cash_accounts ca
                LEFT JOIN concerns con ON ca.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE ca.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY ca.id ASC";
        $accounts = DB::fetchAll($sql, $params);

        $sql2 = "SELECT ct.*, ca.name AS account_name, u.name AS user_name, con.name AS concern_name
                 FROM cash_transactions ct
                 JOIN cash_accounts ca ON ct.account_id = ca.id
                 LEFT JOIN concerns con ON ct.concern_id = con.id
                 LEFT JOIN users u ON ct.created_by = u.id";
        $params2 = [];
        if ($cid) {
            $sql2 .= " WHERE ct.concern_id = ?";
            $params2[] = $cid;
        }
        $sql2 .= " ORDER BY ct.id DESC LIMIT 20";
        $transactions = DB::fetchAll($sql2, $params2);

        $totalCashInHand = array_reduce($accounts, fn($sum, $a) => $sum + $a['balance'], 0);

        $this->render('concern/cash', [
            'pageTitle'       => 'Cash Accounts & Banking Books',
            'currentModule'   => 'cash',
            'accounts'        => $accounts,
            'transactions'    => $transactions,
            'totalCashInHand' => $totalCashInHand,
            'selectedCid'     => $cid,
        ]);
    }

    public function storeAccount(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/cash');
        }

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

        $sqlIn = "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'credit'";
        $sqlOut = "SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'debit'";
        $sqlFeed = "SELECT ct.*, ca.name AS account_name, con.name AS concern_name
                    FROM cash_transactions ct
                    JOIN cash_accounts ca ON ct.account_id = ca.id
                    LEFT JOIN concerns con ON ct.concern_id = con.id";
        
        $params = [];
        if ($cid) {
            $sqlIn .= " AND concern_id = ?";
            $sqlOut .= " AND concern_id = ?";
            $sqlFeed .= " WHERE ct.concern_id = ?";
            $params[] = $cid;
        }
        $sqlFeed .= " ORDER BY ct.txn_date DESC, ct.id DESC";

        $totalInflow = (float)DB::fetchValue($sqlIn, $params);
        $totalOutflow = (float)DB::fetchValue($sqlOut, $params);
        $netFlow = $totalInflow - $totalOutflow;
        $feed = DB::fetchAll($sqlFeed, $params);

        $this->render('concern/cash_flow', [
            'pageTitle'     => 'Cash Flow Statement',
            'currentModule' => 'cash_flow',
            'totalInflow'   => $totalInflow,
            'totalOutflow'  => $totalOutflow,
            'netFlow'       => $netFlow,
            'feed'          => $feed,
            'selectedCid'   => $cid,
        ]);
    }
}
