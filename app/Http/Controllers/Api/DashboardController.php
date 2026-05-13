<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                'revenue_today'    => (clone $salesQuery)->whereDate('sold_at', $today)->where('payment_status', 'paid')->sum('total'),
                'revenue_month'    => (clone $salesQuery)->where('sold_at', '>=', $thisMonth)->where('payment_status', 'paid')->sum('total'),
                'purchases_month'  => (clone $purchasesQuery)->where('purchased_at', '>=', $thisMonth)->sum('total'),
                'low_stock_count'  => (clone $productsQuery)->whereColumn('stock_quantity', '<=', 'min_stock_level')->count(),
            ],
            'sales_chart' => (clone $salesQuery)->select(
                    DB::raw('DATE(sold_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('sold_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'top_products' => Product::select('products.id', 'products.name', DB::raw('SUM(sale_items.quantity) as total_sold'))
                ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.sold_at', '>=', $thisMonth)
                ->when(!$user->isAdmin(), fn($q) => $q->where('products.branch_id', $user->branch_id))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_sold')
                ->take(5)
                ->get(),
            'low_stock' => (clone $productsQuery)->with('category:id,name')
                ->whereColumn('stock_quantity', '<=', 'min_stock_level')
                ->take(10)
                ->get(['id', 'name', 'sku', 'stock_quantity', 'min_stock_level', 'category_id']),
            'recent_sales' => (clone $salesQuery)->with('customer:id,name')
                ->latest('sold_at')
                ->take(5)
                ->get(['id', 'invoice_number', 'customer_id', 'total', 'payment_status', 'sold_at']),
        ];

        return response()->json($data);
    }
}
