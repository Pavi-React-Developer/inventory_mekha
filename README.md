# INVENTO - Multi-Concern Enterprise Inventory System

A multi-tenant inventory and production ERP system built with **PHP 8, MySQL (InnoDB), Apache (XAMPP), HTML5, CSS3, and JavaScript**.

---

## 1. System Architecture & Tenant Isolation

- **Single Database, Strict Tenant Scoping**: All business records carry `concern_id`. The tenant ID is read **strictly from the authenticated session** (`Auth::concernId()`) and never accepted from URLs, forms, or QR barcodes.
- **Tenant-Scoped Base Model (`App\Core\Model`)**: Every select, update, and delete query automatically injects `WHERE concern_id = ?`. Every insert automatically stamps `concern_id = Auth::concernId()`.
- **Single Stock Gateway (`App\Services\StockService::move()`)**: Stock quantities can **only** be modified via `StockService::move()`. Every movement executes inside an atomic DB transaction with row locking (`FOR UPDATE`) and appends an immutable entry to `stock_ledger`. Ledger net totals always equal live warehouse balances.
- **Dynamic Module Guard (`App\Core\ModuleGuard`)**: Every route and sidebar item checks `concern_modules`. If a module is not licensed for a concern, access is blocked with **403 Forbidden**.
- **Audit Trail (`App\Services\AuditLog`)**: Complete create, update, delete, stock move, and label generation history is preserved in `audit_log` with before/after state snapshots.

---

## 2. Default Login Credentials

| Role | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@invento.com` | `password123` | Platform Dashboard, Concern Management, Company Assets, Consolidated Reports |
| **Concern 1 Admin** (GRM Garments) | `grm.admin@invento.com` | `password123` | All 22 Concern Modules active |
| **Concern 2 Admin** (Apex Fashion) | `apex.admin@invento.com` | `password123` | Limited Modules (Demonstrating module toggle & isolation) |

*Quick-fill buttons are provided on the login page for instant one-click testing.*

---

## 3. Module Map

### Super Admin Modules
1. **Super Admin Dashboard** (`/admin/dashboard`): System-wide KPIs, active concerns, asset valuation, audit stream.
2. **Concern Management** (`/admin/concerns`): Guided multi-step creation wizard with interactive **Module Picker Checklist** and live module toggles.
3. **Asset Management** (`/admin/assets`): Fixed company assets (machinery, IT equipment, vehicles).
4. **Consolidated Reports** (`/admin/reports`): Aggregate valuation, purchase volume, and sales across all concerns.

### Concern Modules (All 22 Implemented)
| Group | Modules | Stock & Financial Effect |
| :--- | :--- | :--- |
| **Dashboard** | Concern Dashboard | Real-time KPIs, stock valuation, ledger feed, low stock alerts |
| **Purchase** | Supplier, Purchase Billing, Purchase Damage | Vendor directory, GST purchase invoices, vendor damage deducting stock |
| **Production** | Catalog & Program, Cutting & Wastage, Stitching & Wastage, Label Generation, QC Stocks | Cutting consumes fabric; Stitching tracks assembly; QC Passed items move directly into live stock |
| **Stock** | Inward, Stocks, Concern Damage, Return & Exchange | Inward adds stock; Damage deducts; Returns restock via `StockService::move()` |
| **Sales** | Customer Details, Request, Batch, Billing, Outward, Dispatch | Customer directory, demand requests, lot tracking, GST sales billing, Outward stock deduction, logistics dispatch |
| **Finance** | Expense, Cash, Cash Flow | Operational expense register, bank & cash accounts, read-only unified cash flow statement |

---

## 4. QR Code & Label Flow

- **Standard QR Payload**: `C{category_id}-S{subcategory_id}-V{variant_id}` (e.g. `C1-S1-V1`).
- **Data Encapsulation**: Price, MRP, size, attributes, creation date, and brand stay in the `variants` row; quantity is calculated live from `stocks`. Price updates immediately reflect on existing printed labels when scanned.
- **Label Generation** (`/m/label_generation`): Pick variant, enter prints count ($N$), prints repeatable grid on A4 / thermal roll, logs to `label_jobs`.
- **Live Scanner & Cross-Tenant Security** (`/scan` & `/api/qr/lookup`): Live camera or barcode gun input. If Concern B scans Concern A's barcode, it returns **404 Not Found** under tenant isolation.

---

## 5. Automated Verification Test Suite

A complete automated test suite is provided at `tests/IsolationSecurityTest.php`. Run it anytime via terminal:

```bash
php c:\xampp\htdocs\INVENTO\tests\IsolationSecurityTest.php
```

All 24 security, isolation, reconciliation, and module guard tests pass.

---

## 6. Accessing via Browser

Open XAMPP Apache and navigate to:
```
http://localhost/INVENTO/
```
(or `http://localhost/INVENTO/public/`)
