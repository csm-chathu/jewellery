# Jewellery Management System — Shop Onboarding Guide

> Hand this document to the shop owner / manager before they start using the system.
> Read it top to bottom in order — each step builds on the previous one.

---

## Part 1 — What This System Can Do

### 1.1 Core Business Modules

| Module | What It Does |
|--------|-------------|
| **Sales** | Create invoices, collect payment, print/share receipts, manage bookings and layaways |
| **Purchases** | Record stock received from suppliers — cash or cheque |
| **Sales Returns** | Process item returns, refund tracking, auto-restores stock |
| **Gold Buybacks** | Record gold bought from walk-in customers (old jewellery, scrap) |
| **Gold Loans** | Lend money against pledged gold, track repayments and reminders |
| **Layaway / Installments** | Installment booking → convert to full sale when fully paid |
| **Custom Made Orders** | Track custom jewellery orders from deposit to delivery |
| **Rework / Job Orders** | Repairs, resizing, polishing — with scrap/buyback as raw material |
| **Products & Stock** | Product catalogue with weight, karat, making charge, gemstone details |
| **Categories** | Group products (Rings, Necklaces, Bangles, etc.) |
| **Customers** | Customer profiles with NIC, birthday, purchase history |
| **Suppliers** | Supplier directory linked to purchases |
| **Employees & Payroll** | Staff records, monthly salary payments with EPF/ETF calculation |
| **Expenses** | Day-to-day shop expenses (electricity, maintenance, etc.) |
| **SMS** | Send promotions, birthday wishes, payment reminders |
| **Private Book** | Off-record gold purchases, private sales, cash adjustments (admin only) |
| **Reports** | Day End, Sales Summary, Purchase Summary, Stock Ledger, Category Stock Value, Metal Balance, Gold Rate History, and more |
| **Accounting (GL)** | Full double-entry general ledger — Trial Balance, Balance Sheet, Income Statement |
| **Gold Rates** | Daily gold rate entry per karat (18k, 22k, 24k etc.) |
| **Audit Log** | Every important action is recorded with who did it and when |

---

### 1.2 How Everything Connects to the Accounts (General Ledger)

Every financial event in the system **automatically creates a journal entry** (double-entry bookkeeping). You do not need to enter journals manually for normal operations.

#### Key Account Codes (pre-configured)

| Code | Account Name | Type | Used When |
|------|-------------|------|-----------|
| 1000 | Cash in Hand | Asset | Cash received / paid out |
| 1010 | Bank / Card Account | Asset | Card, bank transfer, cheque settlements |
| 1100 | Accounts Receivable | Asset | Booking deposits, partial payments owed |
| 2000 | Accounts Payable | Liability | Supplier invoices not yet paid |
| 2100 | Tax Payable | Liability | GST / VAT collected on sales |
| 2200 | Customer Deposits | Liability | Layaway / booking advance payments |
| 4000 | Sales Revenue | Revenue | Every sale |
| 5000 | Cost of Goods | Expense | Product cost when sold |
| 6000+ | Operating Expenses | Expense | Salaries, rent, electricity, etc. |

#### What Happens in the Ledger Automatically

| Action | Debit (Dr) | Credit (Cr) |
|--------|-----------|------------|
| **Cash sale** | Cash (1000) | Revenue (4000) + Tax Payable (2100) |
| **Booking sale** | Customer Deposit (2200) | Revenue (4000) |
| **Settle booking** | Cash (1000) | Customer Deposit (2200) |
| **Purchase — cash** | COGS / Inventory | Cash (1000) |
| **Purchase — cheque** | COGS / Inventory | Accounts Payable (2000) |
| **Settle cheque** | Accounts Payable (2000) | Bank (1010) |
| **Gold buyback — cash** | Metal Asset | Cash (1000) |
| **Layaway payment** | Cash (1000) | Customer Deposit (2200) |
| **Layaway → full sale** | Customer Deposit (2200) | Revenue (4000) |
| **Sales return (refund)** | Revenue (4000) | Cash/Bank (1000/1010) |
| **Expense** | Expense account | Cash (1000) |
| **Salary payment** | Salary Expense | Cash (1000) |
| **Gold loan issued** | Gold Loan Receivable | Cash (1000) |
| **Gold loan repayment** | Cash (1000) | Gold Loan Receivable |

> **Bottom line:** Every rupee in every transaction is tracked. The Trial Balance, Balance Sheet, and Income Statement reflect the real financial position at any time.

---

## Part 2 — Setup Sequence (Do This In Order Before Going Live)

Go to **Settings** menu on the left sidebar and work through steps 1–8 before entering any real data.

### Step 1 — Shop Information

**Settings → Shop Settings**

Fill in:
- Shop Name (appears on all invoices and receipts)
- Address (printed on invoices)
- Phone Number
- BR Number (Business Registration Number)
- Upload your shop logo
- Print Mode: choose **POS** (thermal roll) or **A5** (half-page paper)

---

### Step 2 — Gold Rates

**Settings → Gold Rates**

Enter today's buying rate per gram for each karat:

| Karat | Example Rate |
|-------|-------------|
| 24k | LKR 22,500 |
| 22k | LKR 20,625 |
| 18k | LKR 16,875 |
| 14k | LKR 13,125 |

> **Important:** You must set the gold rate every morning before starting work. The system uses today's rate to calculate product values, category stock values, and the Metal Balance report.

---

### Step 3 — Carat Settings

**Settings → Carats**

Default carats (18k, 22k, 24k, etc.) are already seeded. You can add or toggle carats your shop uses.

---

### Step 4 — Tax Settings

**Settings → Tax Settings**

Add your applicable tax rate (e.g., NBT 3%, GST 15%). If you do not charge tax, leave this empty — the system will not add tax to invoices.

---

### Step 5 — Chart of Accounts

**Accounting → Chart of Accounts**

The system comes with a standard chart of accounts pre-configured. Before going live, you should:

1. Review the default accounts — especially codes 1000 (Cash), 1010 (Bank), 4000 (Revenue)
2. Add any additional accounts your shop needs (e.g., separate accounts for each bank, or specific expense categories)
3. If you have **multiple bank accounts**, add each as a sub-account under 1010

> **Do not delete system accounts** (marked with a lock icon). These are used by automatic journal entries.

---

### Step 6 — Opening Balances

**Accounting → Opening Balances**

This is the most important step before going live. Enter the real balance of each account **as of the date you start using the system**.

**What to collect before doing this step:**

- Cash in hand (count the till)
- Bank account balances (from your bank statement)
- How much money suppliers owe you / you owe suppliers
- Current gold loan outstanding totals
- Any business loan outstanding balance
- Any rent deposit paid

**Example opening balances to enter:**

| Account | Amount (LKR) |
|---------|-------------|
| Cash in Hand (1000) | 150,000 |
| Bank — Sampath (1010) | 850,000 |
| Accounts Payable (2000) | 320,000 *(owe to supplier)* |

> Once opening balances are saved, your Trial Balance will show the correct starting position.

---

### Step 7 — Suppliers

**Purchases → Suppliers**

Add all suppliers you buy from:
- Name, email, phone
- City and country
- Any notes

---

### Step 8 — Employees

**HR → Employees**

Add all staff:
- Name, NIC, position, contact
- Basic salary, EPF/ETF eligibility
- Joined date

Then go to **HR → EPF/ETF Settings** and enter the current EPF and ETF contribution percentages.

---

## Part 3 — Entering Existing Data Before Going Live

### 3.1 Existing Products (Current Stock)

You have jewellery in the shop right now. Every piece must be entered as a product.

**Before entering products, create your Categories first:**

Go to **Inventory → Categories** and add:
- Rings
- Necklaces
- Bangles
- Bracelets
- Earrings
- Chains
- Pendants
- Anklets
- (any others your shop uses)

**Then enter each product at Inventory → Products:**

For each piece record:
- Name (e.g., "Gold Bangle 22k Plain")
- SKU / Barcode (your existing tag number — scan or type it)
- Category
- Material (Gold / Silver / Platinum)
- Karat (18k / 22k / 24k)
- Weight in grams
- Making charge (per gram or fixed amount)
- Wastage % (if applicable)
- Gemstone details (if any)
- Purchase price (what you paid the supplier)
- Selling price (your marked price)
- **Stock Quantity** — this is the count of pieces you have **right now**
- Minimum stock level (alert threshold)
- Supplier (who you bought it from)

> **Tip:** Group similar items. If you have 10 identical bangles, enter stock_quantity = 10, not 10 separate products.

---

### 3.2 Existing Customers

Go to **Customers → Customers** and add regular customers:
- Name, phone, email
- NIC (optional, needed for gold loans)
- Birthday (for birthday SMS feature)
- Address

You do not need to add every customer before you start — you can add them during the first sale.

---

### 3.3 Existing Cheques (Pending Supplier Payments)

If you already have cheques issued to suppliers that have not been cleared yet:

1. Go to **Purchases → New Purchase**
2. Enter the purchase as if it happened on the original date
3. Set Payment Method = **Cheque**
4. Enter the cheque number, cheque date, and bank name
5. Set status = **Received** (the goods are already in stock)

> The system will show this under "Pending Cheques". When the cheque clears at the bank, go to that purchase and click **Settle Cheque** — this moves the liability from Accounts Payable to Bank account.

---

### 3.4 Existing Gold Loans (Pledged Gold)

If you currently have customers who have pledged gold and received cash loans:

1. Go to **Gold Loans → New Loan**
2. Enter the customer, principal amount, interest rate, loan date, and item description
3. The system will track repayments and send reminders

---

### 3.5 Existing Layaways / Installment Bookings

If you have customers currently on installment plans:

1. Go to **Layaway → New Layaway**
2. Enter the customer, product, total price, deposit paid so far, and due date
3. Record any instalments already paid using the **Pay** button

---

### 3.6 Existing Scrap / Gold Buybacks

If you have old gold or scrap in the shop you bought from customers:

1. Go to **Gold Buyback → New Buyback**
2. Enter the customer (or "Walk-in"), date, weight, karat, and amount paid
3. If it is raw scrap you have not yet processed, it appears in **Inventory → Scrap Items**

---

## Part 4 — Daily Operating Procedure

### Every Morning

1. **Set Today's Gold Rate** — Settings → Gold Rates → enter today's rate per karat
2. Check **Gold Loan Reminders** — any loans due today will appear
3. Check **Layaway Reminders** — any instalment due today

### During the Day

- **New Sale:** Sales → New Sale — scan barcode or search product, add customer, select payment method, print receipt
- **New Purchase:** Purchases → New Purchase — select supplier, add items received, choose payment method
- **Expenses:** Expenses → New Expense — record every cash outgoing

### End of Day

1. Go to **Reports → Day End Report**
2. Review the summary (sales, cash collected, expenses paid)
3. Click **Save Day End** to lock the record
4. Count the physical cash in the till — it should match the system's cash balance
5. If there is a difference, use **Private Book → Cash Adjustment** to record it (admin only)

---

## Part 5 — User Roles and Access

| Role | Access Level |
|------|-------------|
| **admin** | Full access to everything including private book, user management, all reports |
| **manager** | All operations + private book, no user management |
| **gold_buyer** | Gold buybacks, informal purchases, private book (limited) |
| **branch** | Sales, purchases, customers, products for their own branch only |
| **auditor** | Read-only access to reports, audit log, accounts |
| **cashier** | Sales and basic customer management only |

**To create users:** Settings → Users → New User

> Each user is assigned to a branch. Branch users can only see data for their own branch. Admins can see all branches.

---

## Part 6 — Private Book (Sensitive — Admin/Manager Only)

The Private Book is for off-record gold transactions that are not part of the official accounts.

- **Informal Purchases** — gold bought from individuals (no supplier invoice)
- **Private Sales** — gold sold informally
- **Private Expenses** — cash expenses from the private fund
- **Cash Adjustments** — topping up or drawing from the private cash pool

> The Private Book does **not** post to the General Ledger. It has its own cashbook that shows the running private cash balance.
> Only users with `admin`, `manager`, or `gold_buyer` role can access this section.

---

## Part 7 — Reports Available

| Report | What It Shows | How Often to Use |
|--------|--------------|-----------------|
| **Day End** | Daily sales, purchases, expenses summary | Daily |
| **Sales Summary** | Sales by period, by payment method | Weekly |
| **Purchase Summary** | Stock received by period | Monthly |
| **Stock Ledger** | Movement of each product (in/out) | As needed |
| **Category Stock Value** | Total weight and gold value per category at today's rate | Weekly |
| **Metal Balance** | Total gold in stock vs. purchased vs. sold | Weekly |
| **Gold Rate History** | How rates changed over time | As needed |
| **Buyback Report** | Gold bought from customers | Monthly |
| **Salary Report** | Payroll history by period | Monthly |
| **Expenses Report** | Expense breakdown by period | Monthly |
| **Gold Loans Report** | Active and closed loan summary | Weekly |
| **Trial Balance** | All account balances (must balance to zero) | Monthly |
| **Balance Sheet** | Assets vs. Liabilities vs. Equity | Monthly |
| **Income Statement** | Revenue minus expenses = net profit | Monthly |

---

## Part 8 — Things to Be Careful About

### 1. Set Gold Rate Every Day
If you forget to set today's rate, the system will use the last entered rate. This can make the Category Stock Value and Metal Balance reports incorrect. Make this the **first task every morning**.

### 2. Never Delete Products That Have Been Sold
If a product has sales history, deleting it will cause missing data in reports. Instead, mark it as **inactive** (toggle the active switch off).

### 3. Cheque Payments Need Two Steps
- Step 1: Enter the purchase with Payment Method = Cheque (records the liability)
- Step 2: When the cheque clears at the bank, open the purchase and click **Settle Cheque** (moves it to the bank account)

If you forget Step 2, your Accounts Payable will be overstated and your Bank balance will be wrong.

### 4. Stock Quantity is Live
The system automatically deducts stock when you make a sale and restores it when you process a return. Do not manually change stock quantities after you go live — let the system track it. Only manually adjust during initial setup.

### 5. Opening Balances Must Be Accurate
If you enter wrong opening balances, every report from day one will be wrong. Take time to get the correct figures from your bank statements and cash count before entering them.

### 6. Day End Should Be Done Every Day
The Day End report locks the record for that day. If you miss a day, do it the next morning before any new transactions.

### 7. Sales Returns Affect Stock and Accounts
When you process a return, the system automatically:
- Adds the quantity back to stock
- Reverses the revenue in the journal (Dr Revenue, Cr Cash)
- Updates the invoice status to "Refunded"

Never manually adjust stock or create a manual journal for a return — use the Return button on the sale.

### 8. Layaway to Sale Conversion
When a layaway customer pays the final instalment, use **Convert to Sale** button — do not create a new sale manually. This properly closes the layaway and creates the correct invoice.

### 9. Private Book is Separate
Transactions in the Private Book do not appear in the official accounts or reports. Do not mix private cash with the main till cash.

### 10. Audit Log Cannot Be Deleted
Every action is logged with the user's name, timestamp, and what changed. If there is a dispute, check the Audit Log first.

### 11. Back Up the Database Regularly
This system stores all data in a database. Set up a daily database backup. If the server fails without a backup, all data could be lost.

---

## Part 9 — Quick Reference: First Week Checklist

```
[ ] Shop Settings filled in (name, address, phone, logo)
[ ] Gold rates entered for today
[ ] Carats confirmed (18k, 22k, 24k active)
[ ] Tax settings configured (or left empty if no tax)
[ ] Chart of accounts reviewed
[ ] Opening balances entered (cash, bank, payables)
[ ] All categories created
[ ] All suppliers added
[ ] All existing products entered with correct stock quantities
[ ] Regular customers added
[ ] All employees added with salary details
[ ] EPF/ETF rates set
[ ] Pending cheques entered as purchases
[ ] Active gold loans entered
[ ] Active layaways entered
[ ] Test sale done and invoice printed/shared
[ ] Test sale return done
[ ] Day end report completed
[ ] All staff accounts created with correct roles
[ ] Admin password changed from default
```

---

## Part 10 — Contact & Support

For system issues or questions, contact the system developer.

> **Default admin login (change immediately after first login):**
> - Email: `admin@jewellery.com`
> - Password: `password`
>
> Go to your profile and change the password before handing the system to staff.

---

*Document prepared for jewellery shop system handover — Version 1.0*
