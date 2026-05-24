# Opening Balance Setup Guide
Date of opening entry: **2025-09-22**

---

## STEP 1 — Accounts Status (Chart of Accounts page)

### Already exist — DO NOT create again

| Code | Name                        | Type      |
|------|-----------------------------|-----------|
| 1000 | Cash on Hand                | Asset     |
| 1010 | Bank Account                | Asset     |
| 1020 | Sampath Bank – Savings      | Asset     |
| 1210 | Inventory – Gold Stock      | Asset     |
| 1500 | Equipment & Fixtures        | Asset     |
| 2000 | Accounts Payable            | Liability |
| 3000 | Owner's Capital             | Equity    |

### Must CREATE (go to Accounts → Add)

| Code | Name                          | Type      | Notes                                      |
|------|-------------------------------|-----------|--------------------------------------------|
| 1520 | Key Money / Lease Deposit     | Asset     | 1510 is taken by Accumulated Depreciation  |
| 2510 | Car Loan                      | Liability | 2100 is taken by VAT/Tax Payable           |
| 2511 | Samurdhi Loan                 | Liability |                                            |
| 2512 | Cop City Loan                 | Liability |                                            |
| 2513 | Bike Loan                     | Liability |                                            |
| 2520 | Personal Loan — Naudawa       | Liability |                                            |
| 2521 | Personal Loan — Udaya Boss    | Liability |                                            |
| 2522 | Personal Loan — Naudang       | Liability |                                            |

---

## STEP 2 — Enter Opening Balances (Opening Balances page)

Date: **2025-09-22**

### DEBIT side (what you own)

| Account                   | Code | Amount (LKR)     | Notes                              |
|---------------------------|------|------------------|------------------------------------|
| Equipment & Fixtures      | 1500 | 3,151,948        | all fixed assets except key money  |
| Key Money / Lease Deposit | 1520 | 800,000          | shop deposit                       |
| Inventory – Gold Stock    | 1210 | **? fill in**    | gold stock value at cost price     |
| Cash on Hand              | 1000 | **? fill in**    | cash in hand on 2025-09-22         |
| Bank Account              | 1010 | **? fill in**    | main bank balance on 2025-09-22    |
| Sampath Bank – Savings    | 1020 | **? fill in**    | savings balance (if any)           |

### CREDIT side (what you owe)

#### Formal Loans — enter REMAINING balance as of 2025-09-22, not original amount

| Account       | Code | Original    | Started    | Monthly | Approx Remaining | Notes                     |
|---------------|------|-------------|------------|---------|-----------------|---------------------------|
| Car Loan      | 2510 | 2,170,000   | 2024-10-16 | 67,196  | ~1,430,844      | 11 months paid by Sep 22  |
| Samurdhi Loan | 2511 | 2,000,000   | 2025-07-31 | 50,000  | ~1,900,000      | ~2 months paid            |
| Cop City Loan | 2512 | 500,000     | 2024-12-12 | ?       | check statement | for Scale & Densy machine |
| Bike Loan     | 2513 | 446,450     | 2025-01-27 | ?       | check statement | office bike               |

> Get exact remaining balance from your loan statement — do not use original amount.

#### Personal / Family Loans

| Account                    | Code | Amount (LKR) |
|---------------------------|------|--------------|
| Personal Loan — Naudawa   | 2520 | 595,100      |
| Personal Loan — Udaya Boss| 2521 | 464,000      |
| Personal Loan — Naudang   | 2522 | 637,200      |

#### Owner's Capital — enter last after all others are filled

| Account         | Code | Amount (LKR)                    |
|-----------------|------|---------------------------------|
| Owner's Capital | 3000 | = Total DR minus Total CR       |

---

## STEP 3 — Owner's Capital Formula

```
Total DR = 3,151,948 + 800,000 + [Gold Stock] + [Cash] + [Bank] + [Sampath]

Total CR = [Car Loan remaining]
         + [Samurdhi remaining]
         + [Cop City remaining]
         + [Bike Loan remaining]
         + 595,100 + 464,000 + 637,200
         + [Accounts Payable if any]

Owner's Capital (3000) CR = Total DR − Total CR
```

---

## Fixed Assets Breakdown (what makes up account 1500 = 3,151,948)

| Item                      | Amount (LKR) |
|--------------------------|--------------|
| CCTV + Partition + Alarm  | 2,300,000    |
| Furniture                 | 200,000      |
| Photo Copy Machine        | 150,000      |
| Laminating Machine        | 25,000       |
| Scale                     | 138,500      |
| Densy Machine             | 150,000      |
| Computer System           | 36,500       |
| AC Machine                | 151,948      |
| **Total → 1500**          | **3,151,948**|
| Key Money → 1520          | 800,000      |
| **Grand Total**           | **3,951,948**|

---

## Still Missing — Collect Before Entering

- [ ] Cash on hand on 2025-09-22
- [ ] Bank Account balance on 2025-09-22
- [ ] Sampath Savings balance on 2025-09-22
- [ ] Gold stock value at cost on 2025-09-22
- [ ] Cop City Loan — remaining balance (check statement)
- [ ] Bike Loan — remaining balance (check statement)
- [ ] Accounts Payable — any unpaid supplier invoices on 2025-09-22
