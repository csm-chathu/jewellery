<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessLoan;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $thisMonth = now()->startOfMonth();
        $user = request()->user();

        $productsQuery = Product::query();
        $customersQuery = Customer::query();
        $salesQuery = Sale::query();
        $purchasesQuery = Purchase::query();

        if (!$user->isAdmin()) {
            $productsQuery->where('branch_id', $user->branch_id);
            $customersQuery->where('branch_id', $user->branch_id);
            $salesQuery->where('branch_id', $user->branch_id);
            $purchasesQuery->where('branch_id', $user->branch_id);
        }

        $data = [
            'totals' => [
                'products'         => (clone $productsQuery)->count(),
                'customers'        => (clone $customersQuery)->count(),
                'sales_today'      => (clone $salesQuery)->whereDate('sold_at', $today)->count(),
                'revenue_today'    => (clone $salesQuery)->whereDate('sold_at', $today)->where('payment_status', 'paid')->sum(DB::raw('COALESCE(official_total, total)')),
                'revenue_month'    => (clone $salesQuery)->where('sold_at', '>=', $thisMonth)->where('payment_status', 'paid')->sum(DB::raw('COALESCE(official_total, total)')),
                'purchases_month'  => (clone $purchasesQuery)->where('purchased_at', '>=', $thisMonth)->sum('total'),
                'low_stock_count'  => (clone $productsQuery)->where('stock_quantity', '<=', 0)->count(),
            ],
            'sales_chart' => (clone $salesQuery)->select(
                    DB::raw('DATE(sold_at) as date'),
                    DB::raw('SUM(COALESCE(official_total, total)) as revenue'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('sold_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'top_categories' => DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('sales.sold_at', '>=', $thisMonth)
                ->whereNull('sales.deleted_at')
                ->when(!$user->isAdmin(), fn($q) => $q->where('products.branch_id', $user->branch_id))
                ->select('categories.id', 'categories.name', DB::raw('SUM(sale_items.quantity) as total_sold'))
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_sold')
                ->take(5)
                ->get(),
            'low_stock' => (clone $productsQuery)->with('category:id,name')
                ->where('stock_quantity', '<=', 0)
                ->take(10)
                ->get(['id', 'name', 'sku', 'stock_quantity', 'min_stock_level', 'category_id']),
            'recent_sales' => (clone $salesQuery)->with('customer:id,name')
                ->latest('sold_at')
                ->take(5)
                ->get(['id', 'invoice_number', 'customer_id', 'total', 'payment_status', 'sold_at']),
            'loan_due_soon' => BusinessLoan::with(['liabilityAccount:id,code,name'])
                ->where('status', 'active')
                ->whereNotNull('next_payment_date')
                ->whereDate('next_payment_date', '<=', now()->addDays(3))
                ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
                ->orderBy('next_payment_date')
                ->get(['id', 'loan_number', 'lender_name', 'monthly_installment', 'outstanding_balance', 'next_payment_date', 'liability_account_id']),
            'cheque_reminders' => (clone $purchasesQuery)
                ->with('supplier:id,name')
                ->where('payment_method', 'cheque')
                ->whereNull('cheque_settled_at')
                ->whereNotNull('cheque_date')
                ->orderBy('cheque_date')
                ->get(['id', 'purchase_number', 'supplier_id', 'total', 'cheque_number', 'cheque_date', 'cheque_bank_name', 'status']),
        ];

        return response()->json($data);
    }
}
