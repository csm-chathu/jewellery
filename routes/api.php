<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountTransferController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CloudinaryUploadController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SalaryPaymentController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GLController;
use App\Http\Controllers\Api\GoldBuybackController;
use App\Http\Controllers\Api\GoldLoanController;
use App\Http\Controllers\Api\CaratController;
use App\Http\Controllers\Api\GoldRateController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\OpeningBalanceController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\RentPaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\ScrapItemController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TaxSettingController;
use App\Http\Controllers\Api\ShopSettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InformalPurchaseController;
use App\Http\Controllers\Api\PrivateCashAdjustmentController;
use App\Http\Controllers\Api\PrivateExpenseController;
use App\Http\Controllers\Api\PrivateSaleController;
use App\Http\Controllers\Api\PrivateBuyerController;
use App\Http\Controllers\Api\EpfEtfSettingController;
use App\Http\Controllers\Api\SalaryAdvanceController;
use App\Http\Controllers\Api\LayawayController;
use App\Http\Controllers\Api\ReworkOrderController;
use App\Http\Controllers\Api\CustomMadeOrderController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/shop-branding', [ShopSettingController::class, 'branding']);

// Public — no auth required
Route::get('/sales/public/{token}', [SaleController::class, 'publicView']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user()->load('branch:id,name,code'));
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Reference selects (no pagination)
    Route::get('/categories/all',  [CategoryController::class, 'all']);
    Route::get('/suppliers/all',   [SupplierController::class, 'all']);
    Route::get('/customers/all',   [CustomerController::class, 'all']);
    Route::post('/uploads/cloudinary', [CloudinaryUploadController::class, 'store']);

    // CRUD resources
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('suppliers',  SupplierController::class);
    Route::apiResource('products',   ProductController::class);
    Route::apiResource('customers',  CustomerController::class);
    Route::apiResource('sales',      SaleController::class)->except(['update']);
    Route::post('/sales/{sale}/settle-booking', [SaleController::class, 'settleBooking']);
    Route::post('/sales/{sale}/send-sms',       [SaleController::class, 'sendSms']);
    Route::get('/sales/{sale}/returns',         [SaleReturnController::class, 'index']);
    Route::post('/sales/{sale}/return',         [SaleReturnController::class, 'store']);
    Route::apiResource('purchases',  PurchaseController::class)->except(['update']);
    Route::post('purchases/{purchase}/receive',      [PurchaseController::class, 'receive']);
    Route::post('purchases/{purchase}/settle-cheque',[PurchaseController::class, 'settleChequePurchase']);

    // Gold rates
    Route::get('/gold-rates',            [GoldRateController::class, 'index']);
    Route::post('/gold-rates',           [GoldRateController::class, 'store']);
    Route::get('/gold-rates/today',      [GoldRateController::class, 'todayRate']);
    Route::post('/gold-rates/calculate', [GoldRateController::class, 'calculate']);

    // Carat master data
    Route::get('/carats',             [CaratController::class, 'index']);
    Route::post('/carats',            [CaratController::class, 'store']);
    Route::delete('/carats/{carat}',  [CaratController::class, 'destroy']);
    Route::patch('/carats/{carat}/toggle', [CaratController::class, 'toggle']);

    // Tax settings
    Route::apiResource('tax-settings', TaxSettingController::class);

    // Shop Settings
    Route::get('/shop-settings', [ShopSettingController::class, 'index']);
    Route::post('/shop-settings', [ShopSettingController::class, 'update']);

    // Expenses
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/:id', [ExpenseController::class, 'show']);
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
    Route::get('/expenses-summary', [ExpenseController::class, 'summary']);

    // SMS
    Route::get('/sms/logs',              [SmsController::class, 'logs']);
    Route::get('/sms/customer-list',     [SmsController::class, 'customerList']);
    Route::get('/sms/birthday-preview',  [SmsController::class, 'birthdayPreview']);
    Route::post('/sms/send-custom',      [SmsController::class, 'sendCustom']);
    Route::post('/sms/send-promotion',   [SmsController::class, 'sendPromotion']);
    Route::post('/sms/send-birthdays',   [SmsController::class, 'sendBirthdays']);

    // User management (admin)
    Route::get('/users',              [UserController::class, 'index']);
    Route::post('/users',             [UserController::class, 'store']);
    Route::put('/users/{user}',       [UserController::class, 'update']);
    Route::delete('/users/{user}',    [UserController::class, 'destroy']);
    Route::get('/branches',           [UserController::class, 'branches']);

    // Reports
    Route::get('/reports/metal-balance',    [ReportController::class, 'metalBalance']);
    Route::get('/reports/rate-pnl',         [ReportController::class, 'ratePnl']);
    Route::get('/reports/day-end',          [ReportController::class, 'dayEnd']);
    Route::post('/reports/day-end',         [ReportController::class, 'storeDayEnd']);
    Route::get('/reports/sales-summary',    [ReportController::class, 'salesSummary']);
    Route::get('/reports/purchases',        [ReportController::class, 'purchasesSummary']);
    Route::get('/reports/gold-rate-history',[ReportController::class, 'goldRateHistory']);
    Route::get('/reports/buybacks',         [ReportController::class, 'buybacksReport']);
    Route::get('/reports/salary',           [ReportController::class, 'salaryReport']);
    Route::get('/reports/expenses',         [ReportController::class, 'expensesReport']);
    Route::get('/reports/gold-loans',       [ReportController::class, 'goldLoansReport']);
    Route::get('/reports/stock-ledger',       [ReportController::class, 'stockLedger']);
    Route::get('/reports/category-stock',     [ReportController::class, 'categoryStockValue']);
    Route::get('/reports/revenue-check',      [ReportController::class, 'revenueCheck']);
    Route::get('/reports/cashbook',           [ReportController::class, 'cashbook']);

    // Audit log
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // Buy-back (purchasing gold from customers)
    Route::get('/gold-buybacks',                [GoldBuybackController::class, 'index']);
    Route::post('/gold-buybacks',               [GoldBuybackController::class, 'store']);
    Route::put('/gold-buybacks/{goldBuyback}',  [GoldBuybackController::class, 'update']);
    Route::delete('/gold-buybacks/{goldBuyback}',[GoldBuybackController::class, 'destroy']);

    // Gold loan (lend money against pledged gold)
    Route::get('/gold-loans',                    [GoldLoanController::class, 'index']);
    Route::post('/gold-loans',                   [GoldLoanController::class, 'store']);
    Route::get('/gold-loans/reminders',          [GoldLoanController::class, 'reminders']);
    Route::get('/gold-loans/{goldLoan}',         [GoldLoanController::class, 'show']);
    Route::post('/gold-loans/{goldLoan}/repay',  [GoldLoanController::class, 'repay']);

    // Scrap management
    Route::get('/scrap-items',                          [ScrapItemController::class, 'index']);
    Route::post('/scrap-items/convert-product',         [ScrapItemController::class, 'convertProduct']);
    Route::put('/scrap-items/{scrapItem}',              [ScrapItemController::class, 'update']);
    Route::delete('/scrap-items/{scrapItem}',           [ScrapItemController::class, 'destroy']);

    // ── Accounting ──────────────────────────────────────────────────────────
    // Chart of Accounts
    Route::get('/accounts/all',          [AccountController::class, 'all']);
    Route::get('/accounts',              [AccountController::class, 'index']);
    Route::post('/accounts',             [AccountController::class, 'store']);
    Route::put('/accounts/{account}',    [AccountController::class, 'update']);
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy']);

    // Account Transfers
    Route::get('/account-transfers',                      [AccountTransferController::class, 'index']);
    Route::post('/account-transfers',                     [AccountTransferController::class, 'store']);
    Route::delete('/account-transfers/{journalEntry}',    [AccountTransferController::class, 'destroy']);

    // Journal Entries
    Route::get('/opening-balances',  [OpeningBalanceController::class, 'index']);
    Route::post('/opening-balances', [OpeningBalanceController::class, 'store']);

    Route::get('/journal-entries',                   [JournalEntryController::class, 'index']);
    Route::post('/journal-entries',                  [JournalEntryController::class, 'store']);
    Route::get('/journal-entries/{journalEntry}',    [JournalEntryController::class, 'show']);
    Route::delete('/journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy']);

    // General Ledger reports
    Route::get('/gl/trial-balance',    [GLController::class, 'trialBalance']);
    Route::get('/gl/balance-sheet',    [GLController::class, 'balanceSheet']);
    Route::get('/gl/income-statement', [GLController::class, 'incomeStatement']);
    Route::get('/gl/ledger/{account}', [GLController::class, 'ledger']);

    // ── HR / Employee & Payroll ─────────────────────────────────────────────
    Route::get('/employees/all',          [EmployeeController::class, 'all']);
    Route::get('/employees',              [EmployeeController::class, 'index']);
    Route::post('/employees',             [EmployeeController::class, 'store']);
    Route::get('/employees/{employee}',   [EmployeeController::class, 'show']);
    Route::put('/employees/{employee}',   [EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}',[EmployeeController::class, 'destroy']);

    Route::get('/salary-payments',                     [SalaryPaymentController::class, 'index']);
    Route::post('/salary-payments',                    [SalaryPaymentController::class, 'store']);
    Route::delete('/salary-payments/{salaryPayment}',  [SalaryPaymentController::class, 'destroy']);
    Route::get('/salary-payments/summary',             [SalaryPaymentController::class, 'summary']);

    // Salary Advances
    Route::get('/salary-advances',                              [SalaryAdvanceController::class, 'index']);
    Route::post('/salary-advances',                             [SalaryAdvanceController::class, 'store']);
    Route::delete('/salary-advances/{salaryAdvance}',           [SalaryAdvanceController::class, 'destroy']);
    Route::get('/salary-advances/pending/{employeeId}',         [SalaryAdvanceController::class, 'pending']);

    // EPF / ETF rate settings
    Route::get('/epf-etf-settings',         [EpfEtfSettingController::class, 'index']);
    Route::get('/epf-etf-settings/current', [EpfEtfSettingController::class, 'current']);
    Route::post('/epf-etf-settings',        [EpfEtfSettingController::class, 'store']);

    // ── Finance: Loans & Monthly Rent ──────────────────────────────────────
    Route::get('/loans',                   [LoanController::class, 'index']);
    Route::post('/loans',                  [LoanController::class, 'store']);
    Route::get('/loans/{loan}',            [LoanController::class, 'show']);
    Route::post('/loans/{loan}/repay',     [LoanController::class, 'repay']);

    Route::get('/rent-payments',                   [RentPaymentController::class, 'index']);
    Route::post('/rent-payments',                  [RentPaymentController::class, 'store']);
    Route::post('/rent-payments/{rentPayment}/pay',[RentPaymentController::class, 'pay']);
    Route::get('/rent-payments/reminders',         [RentPaymentController::class, 'reminders']);

    // Private book — off-record gold purchases & sales (admin, manager, gold_buyer)
    Route::get('/informal-purchases',                         [InformalPurchaseController::class, 'index']);
    Route::post('/informal-purchases',                        [InformalPurchaseController::class, 'store']);
    Route::put('/informal-purchases/{informalGoldPurchase}',  [InformalPurchaseController::class, 'update']);
    Route::delete('/informal-purchases/{informalGoldPurchase}',[InformalPurchaseController::class, 'destroy']);

    Route::get('/private-sales',                 [PrivateSaleController::class, 'index']);
    Route::post('/private-sales',                [PrivateSaleController::class, 'store']);
    Route::put('/private-sales/{privateSale}',   [PrivateSaleController::class, 'update']);
    Route::delete('/private-sales/{privateSale}',[PrivateSaleController::class, 'destroy']);
    Route::get('/private-cashbook',              [PrivateSaleController::class, 'cashbook']);

    Route::get('/private-expenses',                  [PrivateExpenseController::class, 'index']);
    Route::post('/private-expenses',                 [PrivateExpenseController::class, 'store']);
    Route::put('/private-expenses/{privateExpense}', [PrivateExpenseController::class, 'update']);
    Route::delete('/private-expenses/{privateExpense}', [PrivateExpenseController::class, 'destroy']);

    Route::get('/private-cash-adjustments',                         [PrivateCashAdjustmentController::class, 'index']);
    Route::post('/private-cash-adjustments',                        [PrivateCashAdjustmentController::class, 'store']);
    Route::put('/private-cash-adjustments/{privateCashAdjustment}', [PrivateCashAdjustmentController::class, 'update']);
    Route::delete('/private-cash-adjustments/{privateCashAdjustment}', [PrivateCashAdjustmentController::class, 'destroy']);

    Route::get('/private-buyers',                    [PrivateBuyerController::class, 'index']);
    Route::post('/private-buyers',                   [PrivateBuyerController::class, 'store']);
    Route::put('/private-buyers/{customer}',         [PrivateBuyerController::class, 'update']);
    Route::delete('/private-buyers/{customer}',      [PrivateBuyerController::class, 'destroy']);

    // Layaway / Installment Bookings
    Route::get('/layaways',                          [LayawayController::class, 'index']);
    Route::post('/layaways',                         [LayawayController::class, 'store']);
    Route::get('/layaways/{layaway}',                [LayawayController::class, 'show']);
    Route::put('/layaways/{layaway}',                [LayawayController::class, 'update']);
    Route::post('/layaways/{layaway}/pay',           [LayawayController::class, 'pay']);
    Route::post('/layaways/{layaway}/cancel',        [LayawayController::class, 'cancel']);
    Route::post('/layaways/{layaway}/convert-to-sale', [LayawayController::class, 'convertToSale']);
    Route::delete('/layaways/{layaway}',             [LayawayController::class, 'destroy']);

    // Custom Made Orders (private gold book)
    Route::get('/custom-made-orders',                              [CustomMadeOrderController::class, 'index']);
    Route::post('/custom-made-orders',                             [CustomMadeOrderController::class, 'store']);
    Route::put('/custom-made-orders/{customMadeOrder}',            [CustomMadeOrderController::class, 'update']);
    Route::post('/custom-made-orders/{customMadeOrder}/complete',  [CustomMadeOrderController::class, 'complete']);
    Route::post('/custom-made-orders/{customMadeOrder}/issue',     [CustomMadeOrderController::class, 'issue']);
    Route::delete('/custom-made-orders/{customMadeOrder}',         [CustomMadeOrderController::class, 'destroy']);
    Route::get('/custom-made-orders/options/customers',            [CustomMadeOrderController::class, 'customers']);

    // Rework / Job Orders
    Route::get('/rework-orders',                          [ReworkOrderController::class, 'index']);
    Route::post('/rework-orders',                         [ReworkOrderController::class, 'store']);
    Route::put('/rework-orders/{reworkOrder}',            [ReworkOrderController::class, 'update']);
    Route::post('/rework-orders/{reworkOrder}/complete',  [ReworkOrderController::class, 'complete']);
    Route::delete('/rework-orders/{reworkOrder}',         [ReworkOrderController::class, 'destroy']);
    Route::get('/rework-orders/options/buybacks',         [ReworkOrderController::class, 'buybackOptions']);
    Route::get('/rework-orders/options/scraps',           [ReworkOrderController::class, 'scrapOptions']);
    Route::get('/rework-orders/options/categories',       [ReworkOrderController::class, 'categories']);
});
