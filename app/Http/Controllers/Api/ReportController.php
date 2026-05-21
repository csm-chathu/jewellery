<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayEndReport;
use App\Models\Expense;
use App\Models\GoldBuyback;
use App\Models\GoldLoan;
use App\Models\GoldRate;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalaryPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Metal Balance Report: grams by karat across all/branch products */
    public function metalBalance(Request $request)
    {
        $user = $request->user();

        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotNull('karat')
            ->where('stock_quantity', '>', 0)
            ->get(['karat', 'weight', 'stock_quantity', 'purchase_price', 'material', 'branch_id', 'name']);

        $goldRates = GoldRate::todayRatesByLabel();
        $goldRate  = $goldRates['24k'] ?? GoldRate::today();

        $byKarat = $products->groupBy('karat')->map(function ($items, $karat) use ($goldRates) {
            $totalWeight  = $items->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity);
            $karatKey     = strtolower($karat);
            $karatRate    = $goldRates[$karatKey] ?? null;
            $rate24k      = $goldRates['24k'] ?? null;
            $ratePerGram  = $karatRate?->rate_per_gram
                ?? ($rate24k ? $rate24k->rate_per_gram * GoldRate::purityForLabel($karatKey) : null);
            $goldValueLkr = $ratePerGram ? $ratePerGram * $totalWeight : null;

            return [
                'karat'         => $karat,
                'purity'        => round(GoldRate::purityForLabel($karatKey) * 100, 2),
                'item_count'    => $items->count(),
                'piece_count'   => $items->sum('stock_quantity'),
                'total_weight_g'=> round($totalWeight, 3),
                'gold_value_lkr'=> $goldValueLkr ? round($goldValueLkr, 2) : null,
                'cost_value_lkr'=> round($items->sum(fn($p) => $p->purchase_price * $p->stock_quantity), 2),
            ];
        })->values();

        $totals = [
            'total_weight_g'=> round($byKarat->sum('total_weight_g'), 3),
            'gold_value_lkr'=> $goldRate ? round($byKarat->sum('gold_value_lkr'), 2) : null,
            'cost_value_lkr'=> round($byKarat->sum('cost_value_lkr'), 2),
        ];

        return response()->json([
            'by_karat'  => $byKarat,
            'totals'    => $totals,
            'gold_rate' => $goldRate,
            'date'      => today()->toDateString(),
        ]);
    }

    /** Profit/Loss on rate fluctuations (unrealized P&L) */
    public function ratePnl(Request $request)
    {
        $user      = $request->user();
        $goldRates = GoldRate::todayRatesByLabel();
        $goldRate  = $goldRates['24k'] ?? GoldRate::today();

        if (empty($goldRates)) {
            return response()->json(['message' => 'No gold rate set for today'], 404);
        }

        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotNull('karat')
            ->whereNotNull('weight')
            ->where('stock_quantity', '>', 0)
            ->get();

        $rows = $products->map(function ($p) use ($goldRates) {
            $karatKey    = strtolower($p->karat);
            $karatRate   = $goldRates[$karatKey] ?? null;
            $rate24k     = $goldRates['24k'] ?? null;
            $ratePerGram = $karatRate?->rate_per_gram
                ?? ($rate24k ? $rate24k->rate_per_gram * GoldRate::purityForLabel($karatKey) : 0);
            $currentGoldV = $ratePerGram * ($p->weight ?? 0);
            $costBasis    = $p->purchase_price;
            $unrealized   = ($currentGoldV - $costBasis) * $p->stock_quantity;

            return [
                'id'              => $p->id,
                'name'            => $p->name,
                'karat'           => $p->karat,
                'weight_g'        => $p->weight,
                'stock'           => $p->stock_quantity,
                'cost_per_unit'   => $costBasis,
                'gold_value_now'  => round($currentGoldV, 2),
                'unrealized_pnl'  => round($unrealized, 2),
                'pnl_percent'     => $costBasis > 0 ? round((($currentGoldV - $costBasis) / $costBasis) * 100, 2) : null,
            ];
        });

        return response()->json([
            'products'             => $rows,
            'total_unrealized_pnl' => round($rows->sum('unrealized_pnl'), 2),
            'gold_rate'            => $goldRate,
            'date'                 => today()->toDateString(),
        ]);
    }

    /** Sales summary report */
    public function salesSummary(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $query = Sale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);

        $totals = (clone $query)->selectRaw('
            COUNT(*) as count,
            SUM(total) as total_revenue,
            SUM(gold_value_total) as gold_value,
            SUM(gemstone_value_total) as gemstone_value,
            SUM(making_charges_total) as making_charges,
            SUM(wastage_total) as wastage,
            SUM(tax) as total_tax,
            SUM(discount) as total_discount,
            SUM(amount_paid) as amount_paid,
            SUM(total - amount_paid) as outstanding
        ')->first();

        $rows = (clone $query)
            ->with('customer:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'invoice_number', 'customer_id', 'total', 'amount_paid', 'discount', 'tax', 'payment_method', 'sale_type', 'created_at']);

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'totals' => $totals,
            'rows'   => $rows,
        ]);
    }

    /** Purchase summary report */
    public function purchasesSummary(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $query = Purchase::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(purchased_at)'), [$from, $to]);

        $totals = (clone $query)->selectRaw('
            COUNT(*) as count,
            SUM(subtotal) as subtotal,
            SUM(total) as total,
            SUM(tax) as total_tax
        ')->first();

        $rows = (clone $query)
            ->with('supplier:id,name')
            ->orderByDesc('purchased_at')
            ->get(['id', 'purchase_number', 'supplier_id', 'purchased_at', 'subtotal', 'tax', 'total', 'status', 'payment_method']);

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'totals' => $totals,
            'rows'   => $rows,
        ]);
    }

    /** Gold rate history — day by day per carat */
    public function goldRateHistory(Request $request)
    {
        $from = $request->date_from ?? now()->subDays(30)->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $rates = GoldRate::with(['carat', 'createdBy:id,name'])
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->get();

        // Group by date so each row = one date, columns = per carat
        $byDate = $rates->groupBy(fn($r) => $r->date->toDateString())
            ->map(function ($dayRates, $date) {
                $row = ['date' => $date, 'set_by' => $dayRates->first()?->createdBy?->name ?? '—'];
                foreach ($dayRates as $r) {
                    $row[strtolower($r->carat?->label ?? 'unknown')] = $r->rate_per_gram;
                }
                return $row;
            })->values();

        // Get all unique carat labels present
        $carats = $rates->map(fn($r) => $r->carat?->label)->filter()->unique()->sort()->values();

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'carats' => $carats,
            'rows'   => $byDate,
        ]);
    }

    /** Old gold / buybacks report */
    public function buybacksReport(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $query = GoldBuyback::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);

        $totals = (clone $query)->selectRaw('
            COUNT(*) as count,
            SUM(net_weight) as total_weight,
            SUM(final_price) as total_paid
        ')->first();

        $rows = (clone $query)
            ->with('customer:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'buyback_number', 'customer_id', 'declared_karat', 'net_weight',
                   'rate_per_gram', 'buying_price_per_gram', 'final_price', 'status', 'payment_method', 'created_at']);

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'totals' => $totals,
            'rows'   => $rows,
        ]);
    }

    /** Salary payments report */
    public function salaryReport(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $query = SalaryPayment::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween('payment_date', [$from, $to]);

        $totals = (clone $query)->selectRaw('
            COUNT(*) as count,
            SUM(basic_salary) as total_basic,
            SUM(allowances) as total_allowances,
            SUM(deductions) as total_deductions,
            SUM(net_salary) as total_net
        ')->first();

        $rows = (clone $query)
            ->with('employee:id,name,designation,department')
            ->orderByDesc('payment_date')
            ->get(['id', 'payment_number', 'employee_id', 'period_from', 'period_to',
                   'payment_date', 'basic_salary', 'allowances', 'deductions', 'net_salary', 'payment_method', 'status']);

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'totals' => $totals,
            'rows'   => $rows,
        ]);
    }

    /** Expenses report */
    public function expensesReport(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $query = Expense::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween('expense_date', [$from, $to]);

        $totals = (clone $query)->selectRaw('
            COUNT(*) as count,
            SUM(amount) as total_amount
        ')->first();

        $byCategory = (clone $query)
            ->selectRaw('category, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $rows = (clone $query)
            ->with('paidByUser:id,name')
            ->orderByDesc('expense_date')
            ->get(['id', 'expense_date', 'category', 'description', 'amount', 'payment_method', 'paid_by_user_id', 'reference_number']);

        return response()->json([
            'from'        => $from,
            'to'          => $to,
            'totals'      => $totals,
            'by_category' => $byCategory,
            'rows'        => $rows,
        ]);
    }

    /** Gold loans report */
    public function goldLoansReport(Request $request)
    {
        $user   = $request->user();
        $status = $request->status; // active, overdue, closed, all

        $query = GoldLoan::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status));

        $summary = [
            'total'     => (clone $query)->count(),
            'active'    => (clone $query)->where('status', 'active')->count(),
            'overdue'   => (clone $query)->where('status', 'overdue')->count(),
            'closed'    => (clone $query)->where('status', 'closed')->count(),
            'total_loaned'       => (clone $query)->sum('loan_amount'),
            'total_outstanding'  => (clone $query)->sum('outstanding_principal'),
        ];

        $rows = (clone $query)
            ->with('customer:id,name,phone')
            ->orderByDesc('disbursed_date')
            ->get(['id', 'loan_number', 'customer_id', 'declared_karat', 'net_weight',
                   'loan_amount', 'interest_rate_monthly', 'outstanding_principal',
                   'disbursed_date', 'maturity_date', 'status']);

        return response()->json([
            'summary' => $summary,
            'rows'    => $rows,
        ]);
    }

    /** Day-end: get system stock snapshot + previous reports */
    public function dayEnd(Request $request)
    {
        $user = $request->user();

        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->with('category:id,name')
            ->get(['id','name','sku','karat','weight','stock_quantity','purchase_price','category_id','branch_id']);

        $goldRate = GoldRate::today();

        $karatBreakdown = $products->whereNotNull('karat')
            ->groupBy('karat')
            ->map(fn($items, $karat) => [
                'karat'    => $karat,
                'pieces'   => $items->sum('stock_quantity'),
                'weight_g' => round($items->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity), 3),
            ])->values();

        $reports = DayEndReport::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->latest('report_date')->take(10)->get();

        return response()->json([
            'products'        => $products,
            'karat_breakdown' => $karatBreakdown,
            'gold_rate'       => $goldRate,
            'recent_reports'  => $reports,
            'date'            => today()->toDateString(),
        ]);
    }

    /** Stock Ledger: per-product movement history (purchases in, sales out) */
    public function stockLedger(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'manager', 'auditor']), 403, 'Access denied.');

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
        ]);

        $product = Product::with(['category:id,name', 'supplier:id,name'])->findOrFail($request->product_id);

        // Opening balance = all transactions BEFORE date_from
        $openingBalance = 0;
        if ($request->date_from) {
            $purchasedBefore = PurchaseItem::where('product_id', $product->id)
                ->whereHas('purchase', fn($q) => $q->whereDate('purchased_at', '<', $request->date_from)->whereNull('deleted_at'))
                ->sum('quantity');
            $soldBefore = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', fn($q) => $q->whereDate('sold_at', '<', $request->date_from)->whereNull('deleted_at'))
                ->sum('quantity');
            $openingBalance = (int) $purchasedBefore - (int) $soldBefore;
        }

        // Purchases in range
        $purchases = PurchaseItem::with([
                'purchase:id,purchase_number,purchased_at,supplier_id',
                'purchase.supplier:id,name',
            ])
            ->where('product_id', $product->id)
            ->whereHas('purchase', function ($q) use ($request) {
                $q->whereNull('deleted_at');
                if ($request->date_from) $q->whereDate('purchased_at', '>=', $request->date_from);
                if ($request->date_to)   $q->whereDate('purchased_at', '<=', $request->date_to);
            })
            ->get()
            ->map(fn($item) => [
                'date'      => $item->purchase->purchased_at->toDateString(),
                'ref'       => $item->purchase->purchase_number,
                'type'      => 'purchase',
                'party'     => $item->purchase->supplier?->name ?? '—',
                'in'        => (int) $item->quantity,
                'out'       => 0,
                'unit_cost' => (float) $item->unit_cost,
                'balance'   => 0,
            ]);

        // Sales in range
        $sales = SaleItem::with([
                'sale:id,invoice_number,sold_at,customer_id',
                'sale.customer:id,name',
            ])
            ->where('product_id', $product->id)
            ->whereHas('sale', function ($q) use ($request) {
                $q->whereNull('deleted_at');
                if ($request->date_from) $q->whereDate('sold_at', '>=', $request->date_from);
                if ($request->date_to)   $q->whereDate('sold_at', '<=', $request->date_to);
            })
            ->get()
            ->map(fn($item) => [
                'date'       => $item->sale->sold_at->toDateString(),
                'ref'        => $item->sale->invoice_number,
                'type'       => 'sale',
                'party'      => $item->sale->customer?->name ?? 'Walk-in',
                'in'         => 0,
                'out'        => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'balance'    => 0,
            ]);

        // Merge, sort, compute running balance
        $balance = $openingBalance;
        $entries = $purchases->merge($sales)
            ->sortBy(['date', 'ref'])
            ->values()
            ->map(function ($entry) use (&$balance) {
                $balance += $entry['in'] - $entry['out'];
                $entry['balance'] = $balance;
                return $entry;
            });

        return response()->json([
            'product'         => $product,
            'opening_balance' => $openingBalance,
            'closing_balance' => $balance,
            'current_stock'   => $product->stock_quantity,
            'total_in'        => $entries->sum('in'),
            'total_out'       => $entries->sum('out'),
            'entries'         => $entries->values(),
        ]);
    }

    /** Save a day-end physical count */
    public function storeDayEnd(Request $request)
    {
        $data = $request->validate([
            'report_date'              => 'required|date',
            'physical_gold_weight'     => 'required|numeric|min:0',
            'item_counts'              => 'required|array',
            'item_counts.*.product_id' => 'required|exists:products,id',
            'item_counts.*.physical_qty' => 'required|integer|min:0',
            'notes'                    => 'nullable|string',
            'status'                   => 'required|in:draft,submitted',
        ]);

        $user     = $request->user();
        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->get(['id','weight','karat','stock_quantity','purchase_price']);

        $goldRate     = GoldRate::today();
        $systemWeight = round($products->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity), 3);

        $report = DayEndReport::updateOrCreate(
            ['report_date' => $data['report_date']],
            [
                'created_by'           => $user->id,
                'branch_id'            => $user->branch_id ?? $products->first()?->branch_id,
                'system_gold_weight'   => $systemWeight,
                'physical_gold_weight' => $data['physical_gold_weight'],
                'variance_weight'      => round($data['physical_gold_weight'] - $systemWeight, 3),
                'system_stock_value'   => round($products->sum(fn($p) => $p->purchase_price * $p->stock_quantity), 2),
                'karat_breakdown'      => $products->whereNotNull('karat')->groupBy('karat')
                    ->map(fn($items, $k) => [
                        'karat'    => $k,
                        'pieces'   => $items->sum('stock_quantity'),
                        'weight_g' => round($items->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity), 3),
                    ])->values(),
                'item_counts' => $data['item_counts'],
                'notes'       => $data['notes'] ?? null,
                'status'      => $data['status'],
            ]
        );

        return response()->json($report, 201);
    }

    /** Category Stock Value Report: total weight & gold value per category */
    public function categoryStockValue(Request $request)
    {
        $user = $request->user();

        $products = Product::with('category:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('stock_quantity', '>', 0)
            ->get(['id', 'name', 'sku', 'category_id', 'karat', 'weight', 'stock_quantity', 'purchase_price']);

        $goldRates = GoldRate::todayRatesByLabel();

        $categories = $products->groupBy('category_id')->map(function ($items) use ($goldRates) {
            $category = $items->first()->category;

            $productRows = $items->map(function ($p) use ($goldRates) {
                $weight       = (float) ($p->weight ?? 0);
                $totalWeight  = round($weight * $p->stock_quantity, 3);
                $karatKey     = strtolower($p->karat ?? '');
                $karatRate    = $goldRates[$karatKey] ?? null;
                $rate24k      = $goldRates['24k'] ?? null;
                $ratePerGram  = $karatRate?->rate_per_gram
                    ?? ($rate24k && $karatKey
                        ? $rate24k->rate_per_gram * GoldRate::purityForLabel($karatKey)
                        : null);
                $goldValue = $ratePerGram ? round($ratePerGram * $totalWeight, 2) : null;

                return [
                    'id'           => $p->id,
                    'name'         => $p->name,
                    'sku'          => $p->sku,
                    'karat'        => $p->karat,
                    'weight_g'     => $weight,
                    'qty'          => (int) $p->stock_quantity,
                    'total_weight' => $totalWeight,
                    'rate_per_gram'=> $ratePerGram,
                    'gold_value'   => $goldValue,
                    'cost_value'   => round($p->purchase_price * $p->stock_quantity, 2),
                ];
            })->values();

            $totalWeight  = round($productRows->sum('total_weight'), 3);
            $goldValue    = $productRows->every(fn($r) => $r['gold_value'] !== null)
                ? round($productRows->sum('gold_value'), 2)
                : null;

            return [
                'category_id'   => $category?->id,
                'category_name' => $category?->name ?? 'Uncategorised',
                'item_count'    => $productRows->count(),
                'piece_count'   => (int) $productRows->sum('qty'),
                'total_weight'  => $totalWeight,
                'gold_value'    => $goldValue,
                'cost_value'    => round($productRows->sum('cost_value'), 2),
                'sell_value'    => round($productRows->sum('sell_value'), 2),
                'products'      => $productRows,
            ];
        })->sortBy('category_name')->values();

        $totals = [
            'item_count'   => $categories->sum('item_count'),
            'piece_count'  => $categories->sum('piece_count'),
            'total_weight' => round($categories->sum('total_weight'), 3),
            'gold_value'   => $categories->every(fn($c) => $c['gold_value'] !== null)
                ? round($categories->sum('gold_value'), 2) : null,
            'cost_value'   => round($categories->sum('cost_value'), 2),
            'sell_value'   => round($categories->sum('sell_value'), 2),
        ];

        return response()->json([
            'categories' => $categories,
            'totals'     => $totals,
            'gold_rates' => collect($goldRates)->map(fn($r) => [
                'label'        => $r->carat?->label,
                'rate_per_gram'=> $r->rate_per_gram,
            ])->values(),
            'date' => today()->toDateString(),
        ]);
    }
}
