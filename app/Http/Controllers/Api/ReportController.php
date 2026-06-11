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
            SUM(official_total) as total_revenue,
            SUM(gold_value_total) as gold_value,
            SUM(gemstone_value_total) as gemstone_value,
            SUM(making_charges_total) as making_charges,
            SUM(wastage_total) as wastage,
            SUM(tax) as total_tax,
            SUM(discount) as total_discount,
            SUM(LEAST(amount_paid, official_total)) as amount_paid,
            SUM(GREATEST(official_total - amount_paid, 0)) as outstanding
        ')->first();

        // Add item-level discounts to the total_discount figure
        $itemDiscountTotal = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->when(!$user->isAdmin(), fn($q) => $q->where('sales.branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$from, $to])
            ->whereNull('sales.deleted_at')
            ->sum('sale_items.discount');

        if ($totals) {
            $totals->total_discount = round(($totals->total_discount ?? 0) + $itemDiscountTotal, 2);
        }

        $rows = (clone $query)
            ->with([
                'customer:id,name',
                'items.product:id,name,weight,karat,sku',
            ])
            ->withSum('items as item_discount_total', 'discount')
            ->orderByDesc('created_at')
            ->get(['id', 'invoice_number', 'customer_id', 'official_total', 'amount_paid', 'discount', 'tax', 'payment_method', 'sale_type', 'created_at'])
            ->each(function ($row) {
                $row->discount    = round(($row->discount ?? 0) + ($row->item_discount_total ?? 0), 2);
                $row->amount_paid = min((float) $row->amount_paid, (float) $row->official_total);
                unset($row->item_discount_total);
            });

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

    /** Revenue Check: compare purchase cost vs gold value vs sale revenue per sold item */
    public function revenueCheck(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $items = SaleItem::with([
                'product:id,name,karat,weight,purchase_price,sku',
                'sale:id,invoice_number,sold_at,customer_id,branch_id,total,official_total',
                'sale.customer:id,name',
            ])
            ->whereHas('sale', function ($q) use ($user, $from, $to) {
                $q->whereNull('deleted_at')
                  ->whereBetween(DB::raw('DATE(sold_at)'), [$from, $to])
                  ->when(!$user->isAdmin(), fn($q2) => $q2->where('branch_id', $user->branch_id));
            })
            ->get();

        $saleDates = $items->map(fn($i) => $i->sale?->sold_at?->toDateString())->filter()->unique();

        $goldRatesByDate = GoldRate::with('carat')
            ->whereIn('date', $saleDates)
            ->get()
            ->groupBy(fn($r) => $r->date->toDateString())
            ->map(fn($dayRates) => $dayRates->keyBy(fn($r) => strtolower($r->carat?->label ?? '')));

        $todayRates = GoldRate::todayRatesByLabel();

        $rows = $items->map(function ($item) use ($goldRatesByDate, $todayRates) {
            $product  = $item->product;
            $sale     = $item->sale;
            $saleDate = $sale?->sold_at?->toDateString();
            $karat    = strtolower($product?->karat ?? '');
            $weight   = (float) ($product?->weight ?? 0);
            $qty      = (int) $item->quantity;

            $dayRates     = $goldRatesByDate[$saleDate] ?? collect();
            $rateForKarat = $dayRates[$karat] ?? $todayRates[$karat] ?? null;
            $ratePerGram  = $rateForKarat?->rate_per_gram ?? 0;

            $goldValue    = round($ratePerGram * $weight * $qty, 2);
            $purchaseCost = round(($product?->purchase_price ?? 0) * $qty, 2);
            $saleTotal    = (float) $sale?->total;
            $officialTotal = (float) ($sale?->official_total ?? $sale?->total);
            $ratio        = ($saleTotal > 0) ? $officialTotal / $saleTotal : 1;
            $saleRevenue  = round((float) $item->total * $ratio, 2);
            $makingCharge = round((float) ($item->making_charge ?? 0), 2);
            $goldMargin   = round($saleRevenue - $goldValue, 2);
            $grossProfit  = round($saleRevenue - $purchaseCost, 2);

            return [
                'invoice'       => $sale?->invoice_number,
                'sale_date'     => $saleDate,
                'customer'      => $sale?->customer?->name ?? 'Walk-in',
                'product'       => $product?->name,
                'sku'           => $product?->sku,
                'karat'         => $product?->karat,
                'weight_g'      => $weight,
                'qty'           => $qty,
                'purchase_cost' => $purchaseCost,
                'gold_rate'     => $ratePerGram,
                'gold_value'    => $goldValue,
                'making_charge' => $makingCharge,
                'sale_revenue'  => $saleRevenue,
                'gold_margin'   => $goldMargin,
                'gross_profit'  => $grossProfit,
                'margin_pct'    => $purchaseCost > 0 ? round(($grossProfit / $purchaseCost) * 100, 1) : null,
            ];
        })->values();

        $totals = [
            'count'         => $rows->count(),
            'purchase_cost' => round($rows->sum('purchase_cost'), 2),
            'gold_value'    => round($rows->sum('gold_value'), 2),
            'making_charge' => round($rows->sum('making_charge'), 2),
            'sale_revenue'  => round($rows->sum('sale_revenue'), 2),
            'gold_margin'   => round($rows->sum('gold_margin'), 2),
            'gross_profit'  => round($rows->sum('gross_profit'), 2),
        ];

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'totals' => $totals,
            'rows'   => $rows->sortByDesc('sale_date')->values(),
        ]);
    }

    /** Cash Book: all DR/CR movements through cash & bank accounts */
    public function cashbook(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $cashAccountIds = DB::table('accounts')
            ->whereIn('code', ['1000', '1010'])
            ->when(!$user->isAdmin(), fn($q) => $q->where(fn($q2) => $q2->whereNull('branch_id')->orWhere('branch_id', $user->branch_id)))
            ->pluck('id');

        // Find journal entry IDs that have at least one cash movement
        $cashEntryIds = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->whereIn('jel.account_id', $cashAccountIds)
            ->where('je.status', 'posted')
            ->whereNull('je.deleted_at')
            ->whereBetween('je.entry_date', [$from, $to])
            ->when(!$user->isAdmin(), fn($q) => $q->where(fn($q2) =>
                $q2->whereNull('je.branch_id')->orWhere('je.branch_id', $user->branch_id)
            ))
            ->pluck('jel.journal_entry_id')
            ->unique();

        // Fetch ALL lines for those entries (full double-entry view)
        $allLines = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'jel.journal_entry_id', '=', 'je.id')
            ->join('accounts as a', 'a.id', '=', 'jel.account_id')
            ->leftJoin('users as u', 'u.id', '=', 'je.created_by')
            ->whereIn('je.id', $cashEntryIds)
            ->select([
                'je.id as journal_entry_id',
                'je.entry_date as date',
                'je.entry_number',
                'je.description',
                'je.reference_type',
                'je.reference_id',
                'jel.id as line_id',
                'jel.account_id',
                'jel.debit',
                'jel.credit',
                'jel.description as line_description',
                'a.name as account_name',
                'a.code as account_code',
                'u.name as created_by',
            ])
            ->orderBy('je.entry_date')
            ->orderBy('je.id')
            ->orderBy('jel.id')
            ->get();

        $cashIds = $cashAccountIds->toArray();
        $balance = 0;

        $rows = $allLines->groupBy('journal_entry_id')->map(function ($lines) use ($cashIds, &$balance) {
            $first      = $lines->first();
            $cashLines  = $lines->filter(fn($l) => in_array($l->account_id, $cashIds));
            $cashDebit  = round($cashLines->sum('debit'), 2);
            $cashCredit = round($cashLines->sum('credit'), 2);
            $balance   += $cashDebit - $cashCredit;

            return [
                'journal_entry_id' => $first->journal_entry_id,
                'date'             => $first->date,
                'entry_number'     => $first->entry_number,
                'description'      => $first->description,
                'reference_type'   => $first->reference_type,
                'reference_id'     => $first->reference_id,
                'cash_debit'       => $cashDebit,
                'cash_credit'      => $cashCredit,
                'balance'          => round($balance, 2),
                'created_by'       => $first->created_by,
                'lines'            => $lines->map(fn($l) => [
                    'account_code'     => $l->account_code,
                    'account_name'     => $l->account_name,
                    'is_cash'          => in_array($l->account_id, $cashIds),
                    'debit'            => (float) $l->debit,
                    'credit'           => (float) $l->credit,
                    'line_description' => $l->line_description,
                ])->values(),
            ];
        })->values();

        $totalDebit  = $rows->sum('cash_debit');
        $totalCredit = $rows->sum('cash_credit');

        return response()->json([
            'from'         => $from,
            'to'           => $to,
            'rows'         => $rows,
            'total_debit'  => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'net'          => round($totalDebit - $totalCredit, 2),
        ]);
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
