<template>
  <div class="flex gap-0 h-full">

    <!-- ── Sidebar ── -->
    <aside class="w-52 shrink-0 border-r border-gray-200 pr-2 space-y-0.5">
      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 pt-1 pb-2">Reports</p>
      <button v-for="r in reportList" :key="r.key"
        @click="switchReport(r.key)"
        :class="active === r.key
          ? 'bg-gold-50 text-gold-700 font-semibold border-r-2 border-gold-500'
          : 'text-gray-600 hover:bg-gray-50'"
        class="w-full text-left flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors">
        <span>{{ r.icon }}</span>{{ r.label }}
      </button>
    </aside>

    <!-- ── Main content ── -->
    <div class="flex-1 pl-6 min-w-0 space-y-4">

      <!-- Export bar -->
      <div class="flex items-center justify-between flex-wrap gap-2">
        <h3 class="font-semibold text-gray-800 text-base">{{ currentReport.label }}</h3>
        <div class="flex items-center gap-2">
          <button @click="doCSV"   class="btn-secondary text-xs flex items-center gap-1.5">⬇ CSV</button>
          <button @click="doPrint" class="btn-secondary text-xs flex items-center gap-1.5">🖨 Print / PDF</button>
        </div>
      </div>

      <!-- ── Date filter bar (shared) ── -->
      <div v-if="currentReport.hasDateFilter" class="card flex gap-4 items-end flex-wrap py-3">
        <div>
          <label class="form-label">From</label>
          <input v-model="dateFrom" type="date" class="form-input" />
        </div>
        <div>
          <label class="form-label">To</label>
          <input v-model="dateTo" type="date" class="form-input" />
        </div>
        <button @click="generate" :disabled="loading" class="btn-primary">
          {{ loading ? 'Loading…' : 'Generate' }}
        </button>
      </div>

      <!-- ── Gold Loans filter ── -->
      <div v-if="active === 'loans'" class="card flex gap-4 items-end flex-wrap py-3">
        <div>
          <label class="form-label">Status</label>
          <select v-model="loanStatus" class="form-input w-40">
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="overdue">Overdue</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <button @click="generate" :disabled="loading" class="btn-primary">
          {{ loading ? 'Loading…' : 'Generate' }}
        </button>
      </div>

      <div v-if="loading" class="card text-center text-gray-400 py-12">Loading…</div>
      <div v-else-if="!data" class="card text-center text-gray-400 py-12">
        Click Generate to load this report.
      </div>

      <!-- ══════════ SALES ══════════ -->
      <template v-else-if="active === 'sales'">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <StatTile label="Transactions"    :value="data.totals.count" plain :sub="'LKR ' + lkr(data.totals.total_revenue)" />
          <StatTile label="Total Revenue"   :value="lkr(data.totals.total_revenue)" />
          <StatTile label="Amount Paid"     :value="lkr(data.totals.amount_paid)" color="green" />
          <StatTile label="Outstanding"     :value="lkr(data.totals.outstanding)" color="red" />
          <StatTile label="Gold Value"      :value="lkr(data.totals.gold_value)" color="gold" />
          <StatTile label="Making Charges"  :value="lkr(data.totals.making_charges)" color="blue" />
          <StatTile label="Total Tax"       :value="lkr(data.totals.total_tax)" />
          <StatTile label="Total Discounts" :value="lkr(data.totals.total_discount)" />
        </div>
        <div class="card p-0 overflow-hidden overflow-x-auto" id="report-table">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Invoice</th>
                <th class="table-th">Customer</th>
                <th class="table-th">Date</th>
                <th class="table-th">Item Name</th>
                <th class="table-th">Karat</th>
                <th class="table-th text-right">Weight (g)</th>
                <th class="table-th text-right">Qty</th>
                <th class="table-th text-right">Unit Price</th>
                <th class="table-th text-right">Item Total</th>
                <th class="table-th text-right">Invoice Total</th>
                <th class="table-th text-right">Paid</th>
                <th class="table-th">Method</th>
                <th class="table-th">Type</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <template v-for="r in data.rows" :key="r.id">
                <template v-if="r.items && r.items.length">
                  <tr v-for="(item, idx) in r.items" :key="item.id"
                      class="hover:bg-gray-50">
                    <td class="table-td font-mono text-xs whitespace-nowrap">
                      {{ idx === 0 ? r.invoice_number : '' }}
                    </td>
                    <td class="table-td whitespace-nowrap">{{ idx === 0 ? (r.customer?.name ?? '—') : '' }}</td>
                    <td class="table-td whitespace-nowrap">{{ idx === 0 ? fmt(r.created_at) : '' }}</td>
                    <td class="table-td">
                      <span class="font-medium">{{ item.product?.name ?? '—' }}</span>
                      <span v-if="item.product?.sku" class="ml-1 text-gray-400 text-xs">{{ item.product.sku }}</span>
                    </td>
                    <td class="table-td text-center">
                      <span v-if="item.product?.karat" class="inline-block bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-[10px] font-semibold">
                        {{ item.product.karat }}
                      </span>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                    <td class="table-td text-right font-mono text-yellow-700">
                      {{ item.product?.weight != null ? item.product.weight + 'g' : '—' }}
                    </td>
                    <td class="table-td text-center">{{ item.quantity }}</td>
                    <td class="table-td text-right font-mono">{{ lkr(item.unit_price) }}</td>
                    <td class="table-td text-right font-mono font-medium">{{ lkr(item.total) }}</td>
                    <td class="table-td text-right font-mono font-semibold text-green-700">
                      {{ idx === 0 ? lkr(r.official_total) : '' }}
                    </td>
                    <td class="table-td text-right font-mono text-green-600">
                      {{ idx === 0 ? lkr(r.amount_paid) : '' }}
                    </td>
                    <td class="table-td whitespace-nowrap">{{ idx === 0 ? r.payment_method : '' }}</td>
                    <td class="table-td whitespace-nowrap">{{ idx === 0 ? r.sale_type : '' }}</td>
                  </tr>
                </template>
                <tr v-else class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs whitespace-nowrap">{{ r.invoice_number }}</td>
                  <td class="table-td">{{ r.customer?.name ?? '—' }}</td>
                  <td class="table-td whitespace-nowrap">{{ fmt(r.created_at) }}</td>
                  <td class="table-td text-gray-400" colspan="6">—</td>
                  <td class="table-td text-right font-mono font-semibold text-green-700">{{ lkr(r.official_total) }}</td>
                  <td class="table-td text-right font-mono text-green-600">{{ lkr(r.amount_paid) }}</td>
                  <td class="table-td">{{ r.payment_method }}</td>
                  <td class="table-td">{{ r.sale_type }}</td>
                </tr>
              </template>
              <tr v-if="!data.rows.length">
                <td colspan="13" class="table-td text-center text-gray-400 py-8">No data for this period</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- ══════════ PURCHASES ══════════ -->
      <template v-else-if="active === 'purchases'">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <StatTile label="Transactions" :value="data.totals.count" plain :sub="'LKR ' + lkr(data.totals.total)" />
          <StatTile label="Subtotal"     :value="lkr(data.totals.subtotal)" />
          <StatTile label="Tax"          :value="lkr(data.totals.total_tax)" />
          <StatTile label="Total Cost"   :value="lkr(data.totals.total)" color="gold" />
        </div>
        <ReportTable :headers="['Purchase #','Supplier','Date','Subtotal','Tax','Total','Status','Method']"
          :rows="data.rows.map(r => [r.purchase_number, r.supplier?.name ?? '—', fmt(r.purchased_at),
            lkr(r.subtotal), lkr(r.tax), lkr(r.total), r.status, r.payment_method])" />
      </template>

      <!-- ══════════ GOLD RATE HISTORY ══════════ -->
      <template v-else-if="active === 'gold-rates'">
        <div class="card p-0 overflow-hidden overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Date</th>
                <th v-for="c in data.carats" :key="c" class="table-th">{{ c }} (LKR/g)</th>
                <th class="table-th">Set By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in data.rows" :key="row.date" class="hover:bg-gray-50">
                <td class="table-td font-medium">{{ formatDate(row.date) }}</td>
                <td v-for="c in data.carats" :key="c" class="table-td text-gold-700 font-mono">
                  {{ row[c.toLowerCase()] ? lkr(row[c.toLowerCase()]) : '—' }}
                </td>
                <td class="table-td text-gray-400 text-xs">{{ row.set_by }}</td>
              </tr>
              <tr v-if="!data.rows.length">
                <td :colspan="data.carats.length + 2" class="table-td text-center text-gray-400 py-8">No data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- ══════════ OLD GOLD / BUYBACKS ══════════ -->
      <template v-else-if="active === 'buybacks'">
        <div class="grid grid-cols-3 gap-3">
          <StatTile label="Transactions"  :value="data.totals.count" plain :sub="'LKR ' + lkr(data.totals.total_paid)" />
          <StatTile label="Total Weight"  :value="(+data.totals.total_weight).toFixed(3) + 'g'" plain />
          <StatTile label="Total Paid"    :value="lkr(data.totals.total_paid)" color="gold" />
        </div>
        <ReportTable :headers="['Buyback #','Customer','Karat','Weight (g)','Market Rate','Buy Rate','Final Price','Status','Method','Date']"
          :rows="data.rows.map(r => [r.buyback_number, r.customer?.name ?? '—', r.declared_karat,
            r.net_weight, lkr(r.rate_per_gram), lkr(r.buying_price_per_gram), lkr(r.final_price),
            r.status, r.payment_method, fmt(r.created_at)])" />
      </template>

      <!-- ══════════ SALARY PAYMENTS ══════════ -->
      <template v-else-if="active === 'salary'">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <StatTile label="Payments"        :value="data.totals.count" plain :sub="'LKR ' + lkr(data.totals.total_net)" />
          <StatTile label="Total Basic"     :value="lkr(data.totals.total_basic)" />
          <StatTile label="Total Allowances":value="lkr(data.totals.total_allowances)" color="green" />
          <StatTile label="Total Deductions":value="lkr(data.totals.total_deductions)" color="red" />
          <StatTile label="Net Paid"        :value="lkr(data.totals.total_net)" color="gold" class="col-span-2" />
        </div>
        <ReportTable :headers="['Payment #','Employee','Position','Period','Payment Date','Basic','Allowances','Deductions','Net','Method','Status']"
          :rows="data.rows.map(r => [r.payment_number, r.employee?.name ?? '—', r.employee?.designation ?? '—',
            fmt(r.period_from) + ' → ' + fmt(r.period_to), fmt(r.payment_date),
            lkr(r.basic_salary), lkr(r.allowances), lkr(r.deductions), lkr(r.net_salary), r.payment_method, r.status])" />
      </template>

      <!-- ══════════ EXPENSES ══════════ -->
      <template v-else-if="active === 'expenses'">
        <div class="grid grid-cols-2 gap-3">
          <StatTile label="Total Expenses" :value="data.totals.count" plain :sub="'LKR ' + lkr(data.totals.total_amount)" />
          <StatTile label="Total Amount"   :value="lkr(data.totals.total_amount)" color="red" />
        </div>
        <!-- By category summary -->
        <div class="card p-0 overflow-hidden">
          <div class="px-4 py-3 border-b bg-gray-50">
            <p class="text-sm font-semibold text-gray-700">By Category</p>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="cat in data.by_category" :key="cat.category"
              class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50">
              <span class="text-sm text-gray-700 capitalize">{{ (cat.category || '').replace('_',' ') }}</span>
              <div class="text-right">
                <span class="text-sm font-semibold text-red-600">LKR {{ lkr(cat.total) }}</span>
                <span class="text-xs text-gray-400 ml-2">({{ cat.count }} entries)</span>
              </div>
            </div>
          </div>
        </div>
        <ReportTable :headers="['Date','Category','Description','Amount','Method','Ref #','Paid By']"
          :rows="data.rows.map(r => [fmt(r.expense_date), r.category, r.description,
            lkr(r.amount), r.payment_method, r.reference_number ?? '—', r.paid_by_user?.name ?? '—'])" />
      </template>

      <!-- ══════════ GOLD LOANS ══════════ -->
      <template v-else-if="active === 'loans'">
        <div class="grid grid-cols-3 gap-3">
          <StatTile label="Total Loans"   :value="data.summary.total" plain :sub="'LKR ' + lkr(data.summary.total_loaned)" />
          <StatTile label="Active"        :value="data.summary.active" color="blue" plain />
          <StatTile label="Overdue"       :value="data.summary.overdue" color="red" plain />
          <StatTile label="Closed"        :value="data.summary.closed" plain />
          <StatTile label="Total Loaned"  :value="lkr(data.summary.total_loaned)" color="gold" />
          <StatTile label="Outstanding"   :value="lkr(data.summary.total_outstanding)" color="red" />
        </div>
        <ReportTable :headers="['Loan #','Customer','Phone','Karat','Weight (g)','Loan Amount','Outstanding','Interest %','Disbursed','Maturity','Status']"
          :rows="data.rows.map(r => [r.loan_number, r.customer?.name ?? '—', r.customer?.phone ?? '—',
            r.declared_karat, r.net_weight, lkr(r.loan_amount), lkr(r.outstanding_principal),
            r.interest_rate_monthly + '%', fmt(r.disbursed_date), fmt(r.maturity_date), r.status])" />
      </template>

      <!-- ══════════ METAL BALANCE ══════════ -->
      <template v-else-if="active === 'metal'">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <StatTile label="Total Weight"  :value="data.totals.total_weight_g + 'g'" plain />
          <StatTile label="Gold Value"    :value="data.totals.gold_value_lkr ? lkr(data.totals.gold_value_lkr) : '—'" color="gold" />
          <StatTile label="Cost Value"    :value="lkr(data.totals.cost_value_lkr)" />
          <StatTile label="Sell Value"    :value="lkr(data.totals.sell_value_lkr)" color="green" />
        </div>
        <div v-if="data.gold_rate" class="text-xs text-gold-700 bg-gold-50 border border-gold-200 rounded-lg px-3 py-2">
          Based on today's rate: <strong>LKR {{ lkr(data.gold_rate.rate_per_gram) }}/g</strong> · {{ data.date }}
        </div>
        <ReportTable :headers="['Karat','Purity','Pieces','Weight (g)','Gold Value','Cost Value','Sell Value']"
          :rows="data.by_karat.map(r => [r.karat?.toUpperCase(), r.purity + '%', r.piece_count,
            r.total_weight_g + 'g', r.gold_value_lkr ? lkr(r.gold_value_lkr) : '—',
            lkr(r.cost_value_lkr), lkr(r.sell_value_lkr)])" />
      </template>

      <!-- ══════════ RATE P&L ══════════ -->
      <template v-else-if="active === 'pnl'">
        <div class="grid grid-cols-2 gap-3">
          <StatTile label="Total Unrealized P&L"
            :value="(data.total_unrealized_pnl >= 0 ? '+' : '') + 'LKR ' + lkr(data.total_unrealized_pnl)"
            :color="data.total_unrealized_pnl >= 0 ? 'green' : 'red'" plain />
          <StatTile v-if="data.gold_rate" label="24K Rate Today"
            :value="'LKR ' + lkr(data.gold_rate.rate_per_gram) + '/g'" color="gold" plain />
        </div>
        <ReportTable :headers="['Product','Karat','Weight','Stock','Cost/Unit','Gold Value Now','P&L/Unit','Total P&L']"
          :rows="data.products.map(r => [r.name, r.karat, r.weight_g + 'g', r.stock,
            lkr(r.cost_per_unit), lkr(r.gold_value_now),
            (r.gold_value_now - r.cost_per_unit >= 0 ? '+' : '') + lkr(r.gold_value_now - r.cost_per_unit),
            (r.unrealized_pnl >= 0 ? '+' : '') + lkr(r.unrealized_pnl)])" />
      </template>

      <!-- ══════════ CATEGORY STOCK VALUE ══════════ -->
      <template v-else-if="active === 'category-stock'">
        <div v-if="data.gold_rates?.length" class="text-xs text-gold-700 bg-gold-50 border border-gold-200 rounded-lg px-3 py-2">
          Today's gold rates —
          <span v-for="r in data.gold_rates" :key="r.label" class="mr-3">
            <strong>{{ r.label }}</strong>: LKR {{ lkr(r.rate_per_gram) }}/g
          </span>
        </div>
        <div v-else class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
          No gold rate set for today — gold values cannot be calculated.
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <StatTile label="Categories"    :value="data.categories.length" plain />
          <StatTile label="Total Pieces"  :value="data.totals.piece_count" plain />
          <StatTile label="Total Weight"  :value="data.totals.total_weight + 'g'" plain />
          <StatTile label="Gold Value"    :value="data.totals.gold_value ? lkr(data.totals.gold_value) : '—'" color="gold" />
        </div>
        <div class="card p-0 overflow-hidden" id="report-table">
          <table class="w-full text-sm">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="table-th text-left">Category</th>
                <th class="table-th text-right">Items</th>
                <th class="table-th text-right">Pieces</th>
                <th class="table-th text-right">Weight (g)</th>
                <th class="table-th text-right">Gold Value (LKR)</th>
                <th class="table-th text-right">Cost Value (LKR)</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="cat in data.categories" :key="cat.category_id">
                <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer font-medium"
                  @click="toggleCat(cat.category_id)">
                  <td class="table-td">
                    <span class="mr-2 text-gray-400 text-xs">{{ expandedCats.has(cat.category_id) ? '▲' : '▼' }}</span>
                    {{ cat.category_name }}
                  </td>
                  <td class="table-td text-right">{{ cat.item_count }}</td>
                  <td class="table-td text-right">{{ cat.piece_count }}</td>
                  <td class="table-td text-right font-semibold text-yellow-700">{{ cat.total_weight }}</td>
                  <td class="table-td text-right font-semibold text-green-700">
                    {{ cat.gold_value != null ? 'Rs. ' + lkr(cat.gold_value) : '—' }}
                  </td>
                  <td class="table-td text-right text-gray-600">Rs. {{ lkr(cat.cost_value) }}</td>
                </tr>
                <template v-if="expandedCats.has(cat.category_id)">
                  <tr v-for="p in cat.products" :key="p.id" class="bg-blue-50/40 border-b border-blue-100 text-xs">
                    <td class="table-td pl-10">
                      <span class="font-medium">{{ p.name }}</span>
                      <span v-if="p.sku" class="ml-2 text-gray-400">{{ p.sku }}</span>
                      <span v-if="p.karat" class="ml-2 inline-block bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-[10px] font-semibold">{{ p.karat }}</span>
                    </td>
                    <td class="table-td text-right text-gray-500">1</td>
                    <td class="table-td text-right">{{ p.qty }}</td>
                    <td class="table-td text-right text-yellow-700">{{ p.total_weight }}</td>
                    <td class="table-td text-right text-green-700">{{ p.gold_value != null ? 'Rs. ' + lkr(p.gold_value) : '—' }}</td>
                    <td class="table-td text-right text-gray-500">Rs. {{ lkr(p.cost_value) }}</td>
                  </tr>
                </template>
              </template>
              <tr class="bg-gray-800 text-white font-bold">
                <td class="table-td">TOTAL</td>
                <td class="table-td text-right">{{ data.totals.item_count }}</td>
                <td class="table-td text-right">{{ data.totals.piece_count }}</td>
                <td class="table-td text-right">{{ data.totals.total_weight }} g</td>
                <td class="table-td text-right">{{ data.totals.gold_value != null ? 'Rs. ' + lkr(data.totals.gold_value) : '—' }}</td>
                <td class="table-td text-right">Rs. {{ lkr(data.totals.cost_value) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- ══════════ REVENUE CHECK ══════════ -->
      <template v-else-if="active === 'revenue'">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <StatTile label="Items Sold"       :value="data.totals.count" plain :sub="'LKR ' + lkr(data.totals.sale_revenue)" />
          <StatTile label="Sale Revenue"     :value="lkr(data.totals.sale_revenue)"  color="green" />
          <StatTile label="Purchase Cost"    :value="lkr(data.totals.purchase_cost)" color="gold" />
          <StatTile label="Gold Value"       :value="lkr(data.totals.gold_value)"    color="gold" />
          <StatTile label="Making Charges"   :value="lkr(data.totals.making_charge)" />
          <StatTile label="Gold Margin"
            :value="(data.totals.gold_margin >= 0 ? '+' : '') + lkr(data.totals.gold_margin)"
            :color="data.totals.gold_margin >= 0 ? 'green' : 'red'" plain />
          <StatTile label="Gross Profit"
            :value="(data.totals.gross_profit >= 0 ? '+' : '') + lkr(data.totals.gross_profit)"
            :color="data.totals.gross_profit >= 0 ? 'green' : 'red'" plain
            class="col-span-2" />
        </div>
        <div class="text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
          <strong>Revenue Check</strong> — compares what each item cost us (purchase price) against
          the gold market value on the sale date and the actual selling price.
          <strong>Gold Margin</strong> = Revenue − Gold Value &nbsp;|&nbsp;
          <strong>Gross Profit</strong> = Revenue − Purchase Cost
        </div>
        <div class="card p-0 overflow-hidden overflow-x-auto" id="report-table">
          <table class="w-full text-sm">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="table-th whitespace-nowrap text-left">Date</th>
                <th class="table-th whitespace-nowrap text-left">Invoice</th>
                <th class="table-th whitespace-nowrap text-left">Customer</th>
                <th class="table-th whitespace-nowrap text-left">Product</th>
                <th class="table-th whitespace-nowrap">Karat</th>
                <th class="table-th whitespace-nowrap">Wt (g)</th>
                <th class="table-th whitespace-nowrap">Qty</th>
                <th class="table-th whitespace-nowrap text-right">Gold Rate/g</th>
                <th class="table-th whitespace-nowrap text-right">Gold Value</th>
                <th class="table-th whitespace-nowrap text-right">Purchase Cost</th>
                <th class="table-th whitespace-nowrap text-right">Making</th>
                <th class="table-th whitespace-nowrap text-right">Sale Revenue</th>
                <th class="table-th whitespace-nowrap text-right">Gold Margin</th>
                <th class="table-th whitespace-nowrap text-right">Gross Profit</th>
                <th class="table-th whitespace-nowrap text-right">Margin %</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(r, i) in data.rows" :key="i" class="hover:bg-gray-50">
                <td class="table-td whitespace-nowrap">{{ fmt(r.sale_date) }}</td>
                <td class="table-td whitespace-nowrap font-mono text-xs">{{ r.invoice }}</td>
                <td class="table-td whitespace-nowrap">{{ r.customer }}</td>
                <td class="table-td">
                  <span class="font-medium">{{ r.product }}</span>
                  <span v-if="r.sku" class="ml-1 text-gray-400 text-xs">{{ r.sku }}</span>
                </td>
                <td class="table-td text-center">
                  <span v-if="r.karat" class="inline-block bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-[10px] font-semibold">{{ r.karat }}</span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="table-td text-center text-yellow-700 font-mono">{{ r.weight_g || '—' }}</td>
                <td class="table-td text-center">{{ r.qty }}</td>
                <td class="table-td text-right font-mono text-xs text-gray-500">{{ r.gold_rate ? lkr(r.gold_rate) : '—' }}</td>
                <td class="table-td text-right font-mono text-yellow-700">{{ r.gold_value ? lkr(r.gold_value) : '—' }}</td>
                <td class="table-td text-right font-mono text-gray-600">{{ lkr(r.purchase_cost) }}</td>
                <td class="table-td text-right font-mono text-xs text-gray-500">{{ lkr(r.making_charge) }}</td>
                <td class="table-td text-right font-mono font-semibold text-green-700">{{ lkr(r.sale_revenue) }}</td>
                <td class="table-td text-right font-mono font-semibold"
                    :class="r.gold_margin >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ (r.gold_margin >= 0 ? '+' : '') + lkr(r.gold_margin) }}
                </td>
                <td class="table-td text-right font-mono font-semibold"
                    :class="r.gross_profit >= 0 ? 'text-green-700' : 'text-red-600'">
                  {{ (r.gross_profit >= 0 ? '+' : '') + lkr(r.gross_profit) }}
                </td>
                <td class="table-td text-right text-xs"
                    :class="r.margin_pct != null && r.margin_pct >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ r.margin_pct != null ? r.margin_pct + '%' : '—' }}
                </td>
              </tr>
              <tr v-if="!data.rows.length">
                <td colspan="15" class="table-td text-center text-gray-400 py-8">No sales data for this period</td>
              </tr>
              <tr v-if="data.rows.length" class="bg-gray-800 text-white font-bold text-sm">
                <td colspan="8" class="table-td">TOTAL</td>
                <td class="table-td text-right font-mono text-yellow-300">{{ lkr(data.totals.gold_value) }}</td>
                <td class="table-td text-right font-mono">{{ lkr(data.totals.purchase_cost) }}</td>
                <td class="table-td text-right font-mono">{{ lkr(data.totals.making_charge) }}</td>
                <td class="table-td text-right font-mono text-green-300">{{ lkr(data.totals.sale_revenue) }}</td>
                <td class="table-td text-right font-mono"
                    :class="data.totals.gold_margin >= 0 ? 'text-green-300' : 'text-red-300'">
                  {{ (data.totals.gold_margin >= 0 ? '+' : '') + lkr(data.totals.gold_margin) }}
                </td>
                <td class="table-td text-right font-mono"
                    :class="data.totals.gross_profit >= 0 ? 'text-green-300' : 'text-red-300'">
                  {{ (data.totals.gross_profit >= 0 ? '+' : '') + lkr(data.totals.gross_profit) }}
                </td>
                <td class="table-td"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- ══════════ CASH BOOK ══════════ -->
      <template v-else-if="active === 'cashbook'">
        <div class="card p-0 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b border-gray-200">
              <tr>
                <th class="table-th text-left whitespace-nowrap text-gray-600">Date</th>
                <th class="table-th text-left text-gray-600">Description</th>
                <th class="table-th text-right whitespace-nowrap text-green-700">Cash In (DR)</th>
                <th class="table-th text-right whitespace-nowrap text-red-600">Cash Out (CR)</th>
                <th class="table-th text-center text-gray-600">Detail</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="!data.rows.length">
                <tr><td colspan="5" class="table-td text-center text-gray-400 py-8">No cash transactions for this period</td></tr>
              </template>
              <tr v-for="entry in data.rows" :key="entry.journal_entry_id"
                class="border-b border-gray-100 hover:bg-gray-50">
                <td class="table-td whitespace-nowrap text-gray-600 text-xs">{{ fmt(entry.date) }}</td>
                <td class="table-td">
                  <span class="font-medium text-gray-800">{{ entry.description }}</span>
                  <span v-if="entry.reference_type" class="ml-2 text-xs text-gray-400">
                    {{ entry.reference_type }} #{{ entry.reference_id }}
                  </span>
                </td>
                <td class="table-td text-right font-mono font-semibold text-green-700">
                  {{ entry.cash_debit > 0 ? lkr(entry.cash_debit) : '' }}
                </td>
                <td class="table-td text-right font-mono font-semibold text-red-600">
                  {{ entry.cash_credit > 0 ? lkr(entry.cash_credit) : '' }}
                </td>
                <td class="table-td text-center">
                  <button @click="openCashbookEntry(entry.journal_entry_id)"
                    class="text-xs px-2.5 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded border border-blue-200 font-medium">
                    View
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot v-if="data.rows.length">
              <tr class="bg-gray-50 border-t-2 border-gray-300">
                <td colspan="2" class="table-td font-semibold text-gray-700">Total</td>
                <td class="table-td text-right font-mono font-bold text-green-700">{{ lkr(data.total_debit) }}</td>
                <td class="table-td text-right font-mono font-bold text-red-600">{{ lkr(data.total_credit) }}</td>
                <td></td>
              </tr>
              <tr class="bg-gray-800">
                <td colspan="2" class="table-td font-semibold text-white">Net Cash Movement</td>
                <td colspan="2" class="table-td text-right font-mono font-bold text-lg"
                  :class="data.net >= 0 ? 'text-green-300' : 'text-red-300'">
                  {{ (data.net >= 0 ? '' : '-') + lkr(Math.abs(data.net)) }}
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </template>

      <!-- ══════════ TAX SETTINGS ══════════ -->
      <template v-else-if="active === 'tax'">
        <div class="flex justify-end">
          <button @click="openTaxModal(null)" class="btn-primary text-sm">+ Add Tax</button>
        </div>
        <ReportTable :headers="['Name','Rate (%)','Applies To','Status','Description']"
          :rows="taxList.map(t => [t.name, t.rate + '%', t.applies_to, t.is_active ? 'Active' : 'Inactive', t.description ?? '—'])" />

        <!-- Tax modal -->
        <div v-if="showTaxModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b">
              <h3 class="font-semibold">{{ editingTax ? 'Edit Tax' : 'Add Tax Setting' }}</h3>
              <button @click="showTaxModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form @submit.prevent="saveTax" class="p-6 space-y-4">
              <div>
                <label class="form-label">Name *</label>
                <input v-model="taxForm.name" required class="form-input" placeholder="e.g. VAT, GST" />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Rate (%) *</label>
                  <input v-model.number="taxForm.rate" type="number" min="0" max="100" step="0.01" required class="form-input" />
                </div>
                <div>
                  <label class="form-label">Applies To *</label>
                  <select v-model="taxForm.applies_to" required class="form-input">
                    <option value="all">All</option>
                    <option value="gold_only">Gold Only</option>
                    <option value="gemstone_only">Gemstone Only</option>
                    <option value="making_charges">Making Charges</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="form-label">Description</label>
                <input v-model="taxForm.description" class="form-input" />
              </div>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" v-model="taxForm.is_active" class="w-4 h-4 rounded" />
                <span class="text-sm font-medium text-gray-700">Active (available at POS)</span>
              </label>
              <p v-if="taxError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ taxError }}</p>
              <div class="flex gap-3">
                <button type="button" @click="showTaxModal = false" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" :disabled="taxSaving" class="btn-primary flex-1">
                  {{ taxSaving ? 'Saving…' : 'Save' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </template>

    </div>
  </div>

  <!-- ══════════ Cashbook Entry Modal ══════════ -->
  <teleport to="body">
    <div v-if="showEntryModal" class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
          <div>
            <p class="font-semibold text-gray-800">{{ entryDetail?.entry_number }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ fmt(entryDetail?.entry_date) }} · {{ entryDetail?.status }}</p>
          </div>
          <button @click="showEntryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>
        <!-- Body -->
        <div v-if="entryLoading" class="flex-1 flex items-center justify-center text-gray-400 py-12">Loading…</div>
        <div v-else-if="entryDetail" class="flex-1 overflow-y-auto p-6 space-y-4">
          <!-- Description & reference -->
          <div>
            <p class="text-sm font-medium text-gray-700">{{ entryDetail.description }}</p>
            <p v-if="entryDetail.reference_type" class="text-xs text-gray-400 mt-0.5">
              {{ entryDetail.reference_type }} #{{ entryDetail.reference_id }}
            </p>
          </div>
          <!-- Lines table -->
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="bg-gray-50 border-b border-t">
                <th class="text-left px-3 py-2 font-semibold text-gray-600">Account</th>
                <th class="text-left px-3 py-2 font-semibold text-gray-600">Description</th>
                <th class="text-right px-3 py-2 font-semibold text-gray-600">DR (LKR)</th>
                <th class="text-right px-3 py-2 font-semibold text-gray-600">CR (LKR)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="line in entryDetail.lines" :key="line.id" class="hover:bg-gray-50">
                <td class="px-3 py-2">
                  <span class="font-mono text-xs text-gray-400 mr-1">{{ line.account.code }}</span>
                  <span class="font-medium">{{ line.account.name }}</span>
                </td>
                <td class="px-3 py-2 text-gray-500 text-xs">{{ line.description ?? '—' }}</td>
                <td class="px-3 py-2 text-right font-mono font-semibold text-green-700">
                  {{ line.debit > 0 ? lkr(line.debit) : '—' }}
                </td>
                <td class="px-3 py-2 text-right font-mono font-semibold text-red-600">
                  {{ line.credit > 0 ? lkr(line.credit) : '—' }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-gray-800 text-white font-bold">
                <td colspan="2" class="px-3 py-2">TOTAL</td>
                <td class="px-3 py-2 text-right font-mono text-green-300">
                  {{ lkr(entryDetail.lines?.reduce((s, l) => s + (+l.debit), 0)) }}
                </td>
                <td class="px-3 py-2 text-right font-mono text-red-300">
                  {{ lkr(entryDetail.lines?.reduce((s, l) => s + (+l.credit), 0)) }}
                </td>
              </tr>
            </tfoot>
          </table>
          <p class="text-xs text-gray-400">Created by {{ entryDetail.created_by?.name ?? '—' }}</p>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted, h } from 'vue'
import axios from 'axios'
import { fmtDate } from '../utils/date.js'

// ── Tiny render-function components (no runtime compiler needed) ────────────

const StatTile = {
  props: { label: String, value: [String, Number], color: String, plain: Boolean, sub: String },
  setup(props) {
    return () => h('div', { class: 'card text-center py-3' }, [
      h('p', { class: 'text-xs text-gray-500 uppercase tracking-wider' }, props.label),
      h('p', {
        class: 'text-xl font-bold mt-1 ' + (
          props.color === 'gold'  ? 'text-gold-700'  :
          props.color === 'green' ? 'text-green-600' :
          props.color === 'red'   ? 'text-red-600'   :
          props.color === 'blue'  ? 'text-blue-600'  : 'text-gray-800'
        ),
      }, props.plain ? props.value : 'LKR ' + props.value),
      props.sub ? h('p', { class: 'text-xs text-gray-400 mt-0.5' }, props.sub) : null,
    ].filter(Boolean))
  },
}

const ReportTable = {
  props: ['headers', 'rows'],
  setup(props) {
    return () => h('div', { class: 'card p-0 overflow-hidden overflow-x-auto', id: 'report-table' },
      h('table', { class: 'w-full text-sm' }, [
        h('thead', { class: 'bg-gray-50 border-b' },
          h('tr', {}, props.headers.map(hdr => h('th', { class: 'table-th whitespace-nowrap', key: hdr }, hdr)))
        ),
        h('tbody', { class: 'divide-y divide-gray-100' },
          props.rows.length
            ? props.rows.map((row, i) =>
                h('tr', { class: 'hover:bg-gray-50', key: i },
                  row.map((cell, j) => h('td', { class: 'table-td whitespace-nowrap', key: j }, String(cell ?? '—')))
                )
              )
            : [h('tr', {}, h('td', { colspan: props.headers.length, class: 'table-td text-center text-gray-400 py-8' }, 'No data for this period'))]
        ),
      ])
    )
  },
}

// ── Report definitions ──────────────────────────────────────────────────────

const reportList = [
  { key: 'sales',      label: 'Sales',            icon: '🛍', hasDateFilter: true,  endpoint: '/api/reports/sales-summary' },
  { key: 'purchases',  label: 'Purchases',        icon: '📦', hasDateFilter: true,  endpoint: '/api/reports/purchases' },
  { key: 'gold-rates', label: 'Gold Rate History',icon: '📈', hasDateFilter: true,  endpoint: '/api/reports/gold-rate-history' },
  { key: 'buybacks',   label: 'Old Gold',         icon: '♻️', hasDateFilter: true,  endpoint: '/api/reports/buybacks' },
  { key: 'salary',     label: 'Salary Payments',  icon: '👥', hasDateFilter: true,  endpoint: '/api/reports/salary' },
  { key: 'expenses',   label: 'Expenses',         icon: '💸', hasDateFilter: true,  endpoint: '/api/reports/expenses' },
  { key: 'loans',      label: 'Gold Loans',       icon: '🏦', hasDateFilter: false, endpoint: '/api/reports/gold-loans' },
  { key: 'metal',          label: 'Metal Balance',       icon: '⚖️', hasDateFilter: false, endpoint: '/api/reports/metal-balance' },
  { key: 'pnl',            label: 'Rate P&L',            icon: '📊', hasDateFilter: false, endpoint: '/api/reports/rate-pnl' },
  { key: 'category-stock', label: 'Category Stock Value', icon: '🏷️', hasDateFilter: false, endpoint: '/api/reports/category-stock' },
  { key: 'revenue',        label: 'Revenue Check',       icon: '💰', hasDateFilter: true,  endpoint: '/api/reports/revenue-check' },
  { key: 'cashbook',        label: 'Cash Book',           icon: '💵', hasDateFilter: true,  endpoint: '/api/reports/cashbook' },
  { key: 'tax',            label: 'Tax Settings',        icon: '🧾', hasDateFilter: false, endpoint: null },
]

// ── State ───────────────────────────────────────────────────────────────────

const active       = ref('sales')
const data         = ref(null)
const loading      = ref(false)
const expandedCats = ref(new Set())

const today     = new Date().toISOString().split('T')[0]
const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]
const dateFrom  = ref(monthStart)
const dateTo    = ref(today)
const loanStatus = ref('all')

const currentReport = computed(() => reportList.find(r => r.key === active.value) ?? reportList[0])

// ── Helpers ─────────────────────────────────────────────────────────────────

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const fmt = fmtDate
const formatDate = fmtDate

// ── Load ─────────────────────────────────────────────────────────────────────

async function generate() {
  const report = currentReport.value
  if (!report.endpoint) return
  loading.value = true
  data.value    = null
  try {
    const params = {}
    if (report.hasDateFilter) { params.date_from = dateFrom.value; params.date_to = dateTo.value }
    if (active.value === 'loans') params.status = loanStatus.value
    const { data: d } = await axios.get(report.endpoint, { params })
    data.value = d
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function toggleCat(id) {
  if (expandedCats.value.has(id)) expandedCats.value.delete(id)
  else expandedCats.value.add(id)
  expandedCats.value = new Set(expandedCats.value)
}

async function switchReport(key) {
  active.value = key
  data.value   = null
  if (key === 'tax') { loadTaxes(); return }
  await generate()
}

// ── Export helpers ───────────────────────────────────────────────────────────

function getTableData() {
  const el = document.querySelector('#report-table table')
  if (!el) return { headers: [], rows: [] }
  const headers = [...el.querySelectorAll('thead th')].map(th => th.innerText.trim())
  const rows = [...el.querySelectorAll('tbody tr')].map(tr =>
    [...tr.querySelectorAll('td')].map(td => td.innerText.trim())
  )
  return { headers, rows }
}

function doCSV() {
  const { headers, rows } = getTableData()
  if (!headers.length) return
  const lines = [headers, ...rows].map(r =>
    r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')
  )
  const blob = new Blob([lines.join('\n')], { type: 'text/csv' })
  const a = Object.assign(document.createElement('a'), {
    href: URL.createObjectURL(blob),
    download: `${currentReport.value.label.replace(/\s+/g, '_')}_${dateFrom.value || today}.csv`,
  })
  a.click(); URL.revokeObjectURL(a.href)
}

function doPrint() {
  const { headers, rows } = getTableData()
  const title = currentReport.value.label
  const tHead = `<tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>`
  const tBody = rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')
  const periodLine = currentReport.value.hasDateFilter
    ? `<p style="margin:4px 0 12px;color:#666;font-size:13px">Period: ${dateFrom.value} → ${dateTo.value}</p>` : ''
  const win = window.open('', '_blank')
  win.document.write(`<!DOCTYPE html><html><head><title>${title}</title><style>
    *{font-family:Arial,sans-serif;box-sizing:border-box}
    body{margin:24px;color:#111}
    h2{margin:0 0 4px;font-size:18px}
    table{border-collapse:collapse;width:100%;font-size:12px;margin-top:12px}
    th,td{border:1px solid #ddd;padding:5px 8px;text-align:left}
    th{background:#f5f5f5;font-weight:600}
    tr:nth-child(even){background:#fafafa}
    @media print{@page{margin:15mm}button{display:none!important}}
  </style></head><body>
    <h2>${title}</h2>${periodLine}
    <table><thead>${tHead}</thead><tbody>${tBody}</tbody></table>
    <script>setTimeout(()=>window.print(),300)<\/script>
  </body></html>`)
  win.document.close()
}

// ── Cashbook entry modal ─────────────────────────────────────────────────────

const showEntryModal = ref(false)
const entryDetail    = ref(null)
const entryLoading   = ref(false)

async function openCashbookEntry(journalEntryId) {
  showEntryModal.value = true
  entryDetail.value    = null
  entryLoading.value   = true
  try {
    const { data: d } = await axios.get(`/api/journal-entries/${journalEntryId}`)
    entryDetail.value = d
  } catch { /* ignore */ } finally {
    entryLoading.value = false
  }
}

// ── Tax settings (inline CRUD) ───────────────────────────────────────────────

const taxList      = ref([])
const showTaxModal = ref(false)
const editingTax   = ref(null)
const taxSaving    = ref(false)
const taxError     = ref('')
const taxForm = reactive({ name: '', rate: 0, applies_to: 'all', is_active: true, description: '' })

async function loadTaxes() {
  const { data: d } = await axios.get('/api/tax-settings')
  taxList.value = d
}

function openTaxModal(t) {
  editingTax.value = t; taxError.value = ''
  Object.assign(taxForm, { name: t?.name ?? '', rate: t?.rate ?? 0, applies_to: t?.applies_to ?? 'all', is_active: t?.is_active ?? true, description: t?.description ?? '' })
  showTaxModal.value = true
}

async function saveTax() {
  taxSaving.value = true; taxError.value = ''
  try {
    if (editingTax.value) await axios.put(`/api/tax-settings/${editingTax.value.id}`, taxForm)
    else                  await axios.post('/api/tax-settings', taxForm)
    showTaxModal.value = false; loadTaxes()
  } catch (e) {
    taxError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { taxSaving.value = false }
}

// ── Init ─────────────────────────────────────────────────────────────────────

onMounted(() => generate())
</script>
