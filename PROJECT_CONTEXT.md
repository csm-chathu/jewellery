# Jewellery Management System — Project Context

> Paste this file at the start of a new Claude chat to get instant full context.
> Last updated: 2026-05-18

---

## Stack

| Layer | Tech |
|-------|------|
| Backend | Laravel 10, PHP 8.2, Sanctum (Bearer token auth) |
| Frontend | Vue 3 (Composition API, `<script setup>`), Vite, Tailwind CSS |
| State | Pinia (`stores/auth.js`) |
| HTTP | Axios — `Authorization: Bearer <token>` header, set from `localStorage` |
| DB | MySQL (migrations-managed) |
| Images | Cloudinary via `CloudinaryUploadController` |
| SMS | Custom `SmsController` |
| Build | `npm run build` — **user builds manually, do not build unless asked** |

---

## Directory Structure

```
app/
  Http/Controllers/Api/   ← all API controllers (one per feature)
  Models/                 ← Eloquent models
  Http/Controllers/Auth/  ← AuthController (login/logout)
database/
  migrations/             ← chronological, numbered 000001–000042+
  seeders/
resources/
  js/
    app.js                ← Vue entry point, Axios config, 401 interceptor
    router.js             ← Vue Router, auth guards, role redirects
    stores/auth.js        ← Pinia auth store (token + user in localStorage)
    layouts/AppLayout.vue ← sidebar + topbar shell, role-based nav
    pages/                ← one .vue per page/route
    components/           ← StatCard, ProductModal, SmartImageUploader
  css/app.css
routes/api.php            ← all REST routes (auth:sanctum middleware)
```

---

## Auth & Roles

Auth: POST `/login` → `{ token, user }` stored in localStorage.

Roles: `admin`, `manager`, `accountant`, `hr`, `finance`, `cashier`, `branch`, `auditor`, `gold_buyer`

Special flag: `can_override_gold_rate` (boolean on user) — allows non-admin to access gold rates page.

`gold_buyer` role is redirected to `/informal-purchases` instead of dashboard.

---

## Frontend Routes

| Path | Page | Roles |
|------|------|-------|
| `/` | Dashboard | all standard |
| `/products` | Products | all standard |
| `/categories` | Categories | all standard |
| `/customers` | Customers | all standard |
| `/suppliers` | Suppliers | all standard |
| `/sales` | Sales list | all standard |
| `/sales/new` | New Sale | all standard |
| `/sales/:id` | Sale Receipt | all standard |
| `/purchases` | Purchases list | all standard |
| `/purchases/new` | New Purchase | all standard |
| `/gold-rates` | Gold Rate Management | admin, manager (+ override flag) |
| `/buy-back` | Gold Buy-Back | admin, manager, cashier, branch |
| `/scrap` | Scrap Gold | admin, manager |
| `/rework-orders` | Rework / Job Orders | admin, manager, cashier, branch |
| `/layaways` | Layaway / Installments | admin, manager, cashier, branch |
| `/reports` | Reports & Analytics | admin, manager, accountant, auditor |
| `/day-end` | Day-End Reconciliation | admin, manager, cashier, branch |
| `/audit-log` | Audit Log | admin |
| `/users` | User Management | admin |
| `/shop-settings` | Shop Settings | admin, manager |
| `/expenses` | Expense Management | admin, manager, finance, auditor |
| `/sms` | SMS Centre | admin, manager |
| `/opening-balances` | Opening Balances | admin, manager, accountant, auditor |
| `/accounts` | Chart of Accounts | admin, manager, accountant, auditor |
| `/journal-entries` | Journal Entries | admin, manager, accountant, auditor |
| `/general-ledger` | General Ledger | admin, manager, accountant, auditor |
| `/employees` | Employees | admin, manager, hr |
| `/salary-payments` | Salary Payments | admin, manager, hr |
| `/loans` | Business Loans | admin, manager, finance, auditor |
| `/rentals` | Monthly Rentals | admin, manager, finance, auditor |
| `/gold-loans` | Gold Loans | admin, manager, finance, auditor |
| `/customer-investments` | Owner Investments | admin, manager, finance |
| `/informal-purchases` | Private Gold Book | gold_buyer |
| `/receipt/:token` | Public Sale Receipt | public (no auth) |

---

## API Routes (all under `auth:sanctum` except noted)

### Core CRUD
- `GET/POST/PUT/DELETE /api/categories`
- `GET/POST/PUT/DELETE /api/suppliers`
- `GET/POST/PUT/DELETE /api/products`
- `GET/POST/PUT/DELETE /api/customers`
- `GET/POST/DELETE /api/sales` + `POST /api/sales/{sale}/settle-booking` + `POST /api/sales/{sale}/send-sms`
- `GET/POST/DELETE /api/purchases` + `POST /api/purchases/{id}/receive` + `POST /api/purchases/{id}/settle-cheque`

### Gold Rates & Carats
- `GET/POST /api/gold-rates`, `GET /api/gold-rates/today`, `POST /api/gold-rates/calculate`
- `GET/POST /api/carats`, `DELETE /api/carats/{id}`, `PATCH /api/carats/{id}/toggle`

### Layaways / Installments
- `GET/POST /api/layaways`
- `GET/PUT/DELETE /api/layaways/{id}`
- `POST /api/layaways/{id}/pay`
- `POST /api/layaways/{id}/cancel`          ← cancellation with GL entry
- `POST /api/layaways/{id}/convert-to-sale`

### Accounting / GL
- `GET/POST/PUT/DELETE /api/accounts`, `GET /api/accounts/all`
- `GET/POST /api/opening-balances`
- `GET/POST/DELETE /api/journal-entries`, `GET /api/journal-entries/{id}`
- `GET /api/gl/trial-balance`, `/balance-sheet`, `/income-statement`, `/gl/ledger/{account}`

### HR / Payroll
- `GET/POST/PUT/DELETE /api/employees`, `GET /api/employees/all`
- `GET/POST/DELETE /api/salary-payments`, `GET /api/salary-payments/summary`
- `GET/POST /api/epf-etf-settings`, `GET /api/epf-etf-settings/current`

### Finance
- `GET/POST /api/loans`, `GET /api/loans/{id}`, `POST /api/loans/{id}/repay`
- `GET/POST /api/rent-payments`, `POST /api/rent-payments/{id}/pay`, `GET /api/rent-payments/reminders`
- `GET/POST/PUT/DELETE /api/gold-buybacks`
- `GET/POST /api/gold-loans`, `GET /api/gold-loans/{id}`, `POST /api/gold-loans/{id}/repay`, `GET /api/gold-loans/reminders`

### Private / Off-Record
- `GET/POST/PUT/DELETE /api/informal-purchases`
- `GET/POST/PUT/DELETE /api/private-sales`, `GET /api/private-cashbook`
- `GET/POST/PUT/DELETE /api/private-expenses`
- `GET/POST/PUT/DELETE /api/private-cash-adjustments`

### Other
- `GET /api/dashboard`
- `GET/POST /api/expenses`, `DELETE /api/expenses/{id}`, `GET /api/expenses-summary`
- `GET/POST /api/scrap-items`, `PUT /api/scrap-items/{id}`, `DELETE /api/scrap-items/{id}`, `POST /api/scrap-items/convert-product`
- `GET/POST/PUT/DELETE /api/rework-orders`, `POST /api/rework-orders/{id}/complete`
- `GET/POST /api/tax-settings`
- `GET/POST /api/shop-settings`, `POST /api/shop-settings/logo`
- `GET /api/shop-branding` (public, no auth)
- `GET /api/sales/public/{token}` (public)
- `GET/POST /api/sms/*` (logs, customer-list, birthday-preview, send-custom, send-promotion, send-birthdays)
- `GET /api/audit-logs`
- `GET /api/reports/*` (metal-balance, rate-pnl, day-end, sales-summary, purchases, gold-rate-history, buybacks, salary, expenses, gold-loans)
- `POST /api/uploads/cloudinary`
- `GET /api/branches`

---

## Database Models

| Model | Table | Key Relations |
|-------|-------|--------------|
| User | users | belongsTo Branch |
| Branch | branches | — |
| Category | categories | — |
| Supplier | suppliers | — |
| Product | products | belongsTo Category |
| Customer | customers | — |
| Sale | sales | hasMany SaleItem, belongsTo Customer, belongsTo Branch |
| SaleItem | sale_items | belongsTo Sale, belongsTo Product |
| Purchase | purchases | hasMany PurchaseItem, belongsTo Supplier |
| PurchaseItem | purchase_items | belongsTo Purchase, belongsTo Product |
| GoldRate | gold_rates | belongsTo Carat, belongsTo User (created_by) |
| Carat | carats | hasMany GoldRate |
| GoldBuyback | gold_buybacks | belongsTo Customer |
| ScrapItem | scrap_items | — |
| Account | accounts | — |
| JournalEntry | journal_entries | hasMany JournalEntryLine |
| JournalEntryLine | journal_entry_lines | belongsTo JournalEntry, belongsTo Account |
| Employee | employees | — |
| SalaryPayment | salary_payments | belongsTo Employee |
| EpfEtfSetting | epf_etf_settings | — |
| BusinessLoan | business_loans | hasMany LoanRepayment |
| RentPayment | rent_payments | — |
| GoldLoan | gold_loans | hasMany GoldLoanRepayment |
| Expense | expenses | — |
| ShopSetting | shop_settings | — |
| TaxSetting | tax_settings | — |
| AuditLog | audit_logs | — |
| DayEndReport | day_end_reports | — |
| SmsLog | sms_logs | — |
| InformalGoldPurchase | informal_gold_purchases | belongsTo User (created_by) |
| PrivateSale | private_sales | — |
| PrivateExpense | private_expenses | — |
| PrivateCashAdjustment | private_cash_adjustments | — |
| ReworkOrder | rework_orders | — |
| Layaway | layaways | belongsTo Customer, belongsTo Product, belongsTo User (created_by), belongsTo Branch, belongsTo Sale, hasMany LayawayPayment |
| LayawayPayment | layaway_payments | belongsTo Layaway |

---

## Key GL / Accounting Account Codes

| Code | Name | Type |
|------|------|------|
| 1000 | Cash | asset |
| 1010 | Bank | asset |
| 2200 | Customer Deposit (Layaway liability) | liability |
| 4050 | Cancellation Fee Income | income |

GL entries are `journal_entries` + `journal_entry_lines` (debit/credit per account).

### Layaway GL Flow
- **Payment received**: CR Customer Deposit (2200), DR Cash/Bank
- **Cancellation — full refund**: DR Customer Deposit (2200), CR Cash/Bank
- **Cancellation — partial** (with fee): DR Customer Deposit (2200), CR Cash/Bank (refund), CR Cancellation Fee Income (4050)
- **Cancellation — forfeit**: DR Customer Deposit (2200), CR Cancellation Fee Income (4050)
- **Convert to sale**: links layaway to a Sale record, status → `collected`

---

## Layaway Cancellation Feature (implemented 2026-05-16)

**Migration**: `2026_05_16_000042_add_cancellation_fields_to_layaways.php`
Columns added (with `hasColumn()` guards): `cancelled_at`, `cancellation_reason`, `refund_type`, `cancellation_fee`, `refund_amount`, `refund_method`, `cancellation_journal_id`

**Refund types**: `full` | `partial` | `forfeit`

**API**: `POST /api/layaways/{id}/cancel`
Body: `{ refund_type, cancellation_fee?, cancellation_reason?, refund_method, cancelled_at }`

**Frontend**: Cancel button opens a modal with radio-based refund policy, live GL preview, cancellation reason, and auto-prints an A5 landscape cancellation slip on submit.

---

## Migrations (in order)

```
2014 — users, password_reset_tokens, failed_jobs, personal_access_tokens
2024_01_01_000001 — categories
2024_01_01_000002 — suppliers
2024_01_01_000003 — products
2024_01_01_000004 — customers
2024_01_01_000005 — sales
2024_01_01_000006 — sale_items
2024_01_01_000007 — purchases
2024_01_01_000008 — purchase_items
2026_05_07_000009 — branches + roles
2026_05_07_000010 — gold_rates
2026_05_07_000011 — making_charges + stones to products
2026_05_07_000012 — tax_settings
2026_05_07_000013 — audit_logs
2026_05_07_000014 — day_end_reports
2026_05_07_000015 — permissions to users (can_override_gold_rate etc)
2026_05_07_000016 — KYC fields to customers
2026_05_07_000017 — gold_buybacks
2026_05_07_000018 — scrap_items
2026_05_09_000019 — barcode to products
2026_05_09_000020 — fix sales sold_at type
2026_05_12_000021 — accounting tables (accounts, journal_entries, journal_entry_lines)
2026_05_12_000022 — HR tables (employees, salary_payments)
2026_05_12_000023 — finance + purchase payment fields
2026_05_12_000023 — images to products and buybacks
2026_05_12_000024 — cheque fields to gold_buybacks
2026_05_12_000025 — link sales/purchases to GL + booking
2026_05_12_000026 — gold_loan tables
2026_05_12_055357 — shop_settings
2026_05_12_120000 — expenses
2026_05_12_130000 — journal_entry to expenses
2026_05_12_140000 — sms_logs
2026_05_14_000027 — carats
2026_05_14_000028 — carat_id to gold_rates
2026_05_14_000029 — informal_gold_purchases
2026_05_14_000030 — customer_id to business_loans
2026_05_14_000031 — source to business_loans
2026_05_14_000032 — make loan accounts nullable
2026_05_14_000033 — rework_orders
2026_05_15_000034 — epf_etf_settings
2026_05_15_000035 — layaways
2026_05_15_000036 — sale_id to layaways
2026_05_15_000037 — view_token to sales
2026_05_15_000038 — private_sales
2026_05_15_210008 — reseed product SKUs to numeric
2026_05_16_000039 — photos to informal_gold_purchases
2026_05_16_000040 — private_expenses
2026_05_16_000040 — selling_price to purchase_items
2026_05_16_000041 — private_cash_adjustments
2026_05_16_000041 — cheque settlement to purchases
2026_05_16_000042 — cancellation fields to layaways + seed account 4050
```

---

## Business Setup — Opening Data (2025/09/22)

### Setup Order (must follow this sequence)
1. `/categories` — add jewelry categories first
2. `/suppliers` — add suppliers
3. `/products` — add all jewelry items (needs categories)
4. `/accounts` — set up Chart of Accounts
5. `/opening-balances` — enter all starting balances (DR must = CR)
6. `/loans` — register each loan with lender, amount, interest, repayment
7. `/general-ledger` → Trial Balance — verify DR = CR

### Fixed Assets from Manual Ledger (မුලික වියදම් / ස්ථාවර වත්කම්)
Date: 2025/09/22

| Item | Amount (LKR) |
|---|---|
| Key Money (shop deposit) | 800,000 |
| CCTV + Partition + Alarm System | 2,300,000 |
| Furniture | 200,000 |
| Photo Copy Machine | 150,000 |
| Laminating Machine | 25,000 |
| Scale | 138,500 |
| Densy Machine | 150,000 |
| Computer System | 36,500 |
| AC Machine | 151,948 |
| **Total Fixed Assets** | **3,951,948** |

### Accounts to Create (Chart of Accounts)

| Code | Name | Type | Notes |
|---|---|---|---|
| 1000 | Cash | asset | already seeded |
| 1010 | Bank | asset | already seeded |
| 1200 | Inventory / Gold Stock | asset | needs creating |
| 1500 | Fixed Assets | asset | needs creating |
| 1510 | Key Money / Lease Deposit | asset | needs creating |
| 2000 | Accounts Payable | liability | needs creating |
| 2100 | Bank Loan | liability | needs creating |
| 2110 | Finance Company Loan | liability | needs creating |
| 2120 | Personal / Family Loan | liability | needs creating |
| 3000 | Owner's Capital | equity | needs creating |

### Opening Balance Table (INCOMPLETE — fill in before entering)

| Account | DR | CR |
|---|---|---|
| Fixed Assets (1500) | 3,151,948 | — |
| Key Money (1510) | 800,000 | — |
| Cash (1000) | **?** | — |
| Bank (1010) | **?** | — |
| Inventory / Gold Stock (1200) | **?** | — |
| Accounts Payable (2000) | — | **?** |
| Bank Loan (2100) | — | **?** |
| Finance Loan (2110) | — | **?** |
| Owner's Capital (3000) | — | **[sum of all DR minus other CR]** |

> Still needed from user: Cash balance, Bank balance, Gold/Jewelry stock value, Supplier payables, Loan amounts and lenders.

### Loan Management
- User has loans taken to buy products and fixed assets
- Each loan goes into `/loans` (Business Loans page): lender name, total amount, interest rate, repayment schedule
- Loan outstanding balance on opening day goes as CR in opening balances (liability)
- Assets bought with loan go as DR (inventory or fixed assets)
- Loan repayments are tracked via `/loans/{id}/repay` which auto-posts GL entries

---

## Key Conventions

- **No build unless asked** — user runs `npm run build` themselves
- Vue pages: `<script setup>` + `ref`/`reactive`/`computed` from Vue 3
- API calls: always `axios` (never fetch), relative URLs `/api/...`
- Print slips: popup window with standalone HTML (`window.open`, `document.write`, `window.print()`)
- Migrations: use `Schema::hasColumn()` guards when adding columns that might already exist
- `accounts` table columns: `id, code, name, type, sub_type, parent_id, branch_id, is_active, is_system, description, created_at, updated_at, deleted_at` — **no `balance` column**
- Tailwind classes in Vue templates — custom classes: `card`, `table-th`, `table-td`, `badge`, `btn`, `btn-primary`
- GL entries always go through `JournalEntry` + `JournalEntryLine` models
