<?php

use App\Http\Controllers\Api\AccountController;
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
use App\Http\Controllers\Api\GoldRateController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\RentPaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ScrapItemController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TaxSettingController;
use App\Http\Controllers\Api\ShopSettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/shop-branding', [ShopSettingController::class, 'branding']);

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
    Route::apiResource('purchases',  PurchaseController::class)->except(['update']);

    // Gold rates
    Route::get('/gold-rates',           [GoldRateController::class, 'index']);
    Route::post('/gold-rates',          [GoldRateController::class, 'store']);
    Route::get('/gold-rates/today',     [GoldRateController::class, 'todayRate']);
    Route::post('/gold-rates/calculate',[GoldRateController::class, 'calculate']);

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
    Route::get('/reports/metal-balance', [ReportController::class, 'metalBalance']);
    Route::get('/reports/rate-pnl',      [ReportController::class, 'ratePnl']);
    Route::get('/reports/day-end',       [ReportController::class, 'dayEnd']);
    Route::post('/reports/day-end',      [ReportController::class, 'storeDayEnd']);
    Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary']);

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

    // Journal Entries
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

    // ── Finance: Loans & Monthly Rent ──────────────────────────────────────
    Route::get('/loans',                   [LoanController::class, 'index']);
    Route::post('/loans',                  [LoanController::class, 'store']);
    Route::get('/loans/{loan}',            [LoanController::class, 'show']);
    Route::post('/loans/{loan}/repay',     [LoanController::class, 'repay']);

    Route::get('/rent-payments',                   [RentPaymentController::class, 'index']);
    Route::post('/rent-payments',                  [RentPaymentController::class, 'store']);
    Route::post('/rent-payments/{rentPayment}/pay',[RentPaymentController::class, 'pay']);
    Route::get('/rent-payments/reminders',         [RentPaymentController::class, 'reminders']);
});
