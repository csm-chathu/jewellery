<template>
  <div class="space-y-5">

    <!-- Gold Rate Warning Overlay -->
    <div v-if="showRateWarning"
      class="absolute inset-0 z-[200] flex items-center justify-center bg-black/60"
      style="position:fixed;top:0;left:0;right:0;bottom:0;">
      <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 text-center">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <ExclamationTriangleIcon class="w-9 h-9 text-amber-500" />
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Today's Gold Rate Not Set</h2>
        <p class="text-gray-500 text-sm mb-6">
          No gold rate has been entered for today. Set the rate before recording transactions to ensure correct totals.
        </p>
        <p v-if="copyRateError" class="text-red-600 text-xs mb-4">{{ copyRateError }}</p>
        <div class="flex flex-col gap-3">
          <button @click="copyPreviousRates" :disabled="copyingRates"
            class="w-full px-4 py-3 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors">
            {{ copyingRates ? 'Copying…' : 'Use Same as Previous Day' }}
          </button>
          <button @click="router.push('/gold-rates')"
            class="w-full px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-xl transition-colors">
            Add Today's Rate Now
          </button>
          <button @click="showRateWarning = false; rateWarningDismissed = true"
            class="w-full px-4 py-2 text-gray-400 hover:text-gray-600 text-sm transition-colors">
            Continue without setting rate
          </button>
        </div>
      </div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <LockClosedIcon class="w-5 h-5 text-gray-400" />
          Private Gold Book
        </h2>
        <p class="text-sm text-gray-400 mt-0.5">Off-record purchases &amp; sales — not linked to main accounts</p>
      </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-4 gap-4">
      <div class="card text-center py-4 border-l-4 border-green-400">
        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Cash In (Sales)</p>
        <p class="text-xl font-bold text-green-700">LKR {{ lkr(summary.total_in) }}</p>
      </div>
      <div class="card text-center py-4 border-l-4 border-red-400">
        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Cash Out (Purchases)</p>
        <p class="text-xl font-bold text-red-700">LKR {{ lkr(summary.total_purchases) }}</p>
      </div>
      <div class="card text-center py-4 border-l-4 border-orange-400">
        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Expenses</p>
        <p class="text-xl font-bold text-orange-700">LKR {{ lkr(summary.total_expenses) }}</p>
      </div>
      <div class="card text-center py-4 border-l-4" :class="summary.balance >= 0 ? 'border-blue-400' : 'border-rose-500'">
        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Net Balance</p>
        <p class="text-xl font-bold" :class="summary.balance >= 0 ? 'text-blue-700' : 'text-rose-700'">
          LKR {{ lkr(summary.balance) }}
        </p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex gap-1">
        <button v-for="t in tabs" :key="t.id" @click="activeTab = t.id"
          class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors"
          :class="activeTab === t.id
            ? 'border-amber-500 text-amber-700'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
          {{ t.label }}
        </button>
      </nav>
    </div>

    <!-- ════════════════════════════════════════
         TAB 1 — CASHBOOK
    ════════════════════════════════════════ -->
    <template v-if="activeTab === 'cashbook'">
      <div class="card flex gap-3 flex-wrap items-end">
        <div>
          <label class="text-xs text-gray-500 block mb-1">From</label>
          <input v-model="cbFilters.date_from" type="date" class="form-input w-36" @change="loadCashbook" />
        </div>
        <div>
          <label class="text-xs text-gray-500 block mb-1">To</label>
          <input v-model="cbFilters.date_to" type="date" class="form-input w-36" @change="loadCashbook" />
        </div>
        <button @click="cbFilters.date_from=''; cbFilters.date_to=''; loadCashbook()"
          class="btn-secondary text-sm">Clear</button>
        <div class="ml-auto flex gap-2 flex-wrap items-center">
          <button @click="exportCSV()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm shadow-sm">
            <ArrowDownTrayIcon class="w-4 h-4" /> CSV
          </button>
          <button @click="printCashbook()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg font-medium text-sm shadow-sm">
            <PrinterIcon class="w-4 h-4" /> PDF
          </button>
          <div class="h-6 w-px bg-gray-300 mx-1"></div>
          <button @click="openAdjModal('add')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm shadow-sm">
            <PlusIcon class="w-4 h-4" /> Add Cash
          </button>
          <button @click="openAdjModal('withdraw')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium text-sm shadow-sm">
            <MinusIcon class="w-4 h-4" /> Withdraw
          </button>
        </div>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-800 text-white">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Date</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Ref #</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Description</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Karat / Wt</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-green-300">Cash In</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-red-300">Cash Out</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Balance</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loadingCb" class="text-center">
              <td colspan="7" class="py-8 text-gray-400">Loading…</td>
            </tr>
            <tr v-else-if="!cashbookEntries.length">
              <td colspan="7" class="py-8 text-center text-gray-400">No entries found</td>
            </tr>
            <tr v-for="e in cashbookEntries" :key="`${e.kind}-${e.id}`"
              class="hover:bg-gray-50"
              :class="{
                'bg-green-50/40':  e.kind === 'sale',
                'bg-red-50/30':    e.kind === 'purchase',
                'bg-orange-50/30': e.kind === 'expense',
                'bg-indigo-50/40': e.kind === 'adjustment',
                'bg-amber-50/40':  e.kind === 'custom_order',
              }">
              <td class="px-4 py-2.5 text-gray-600 text-xs whitespace-nowrap">{{ fmtDate(e.entry_date) }}</td>
              <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ e.reference_number }}</td>
              <td class="px-4 py-2.5">
                <span class="inline-flex items-center gap-1.5">
                  <span class="inline-block w-2 h-2 rounded-full shrink-0"
                    :class="{
                      'bg-green-500':  e.kind === 'sale',
                      'bg-red-500':    e.kind === 'purchase',
                      'bg-orange-400': e.kind === 'expense',
                      'bg-indigo-500': e.kind === 'adjustment',
                      'bg-amber-500':  e.kind === 'custom_order',
                    }"></span>
                  {{ e.description || (e.kind === 'sale' ? 'Gold Sale' : e.kind === 'purchase' ? 'Gold Purchase' : e.kind === 'expense' ? 'Expense' : e.kind === 'custom_order' ? (e.sub_kind === 'advance' ? 'Custom Order Advance' : 'Custom Order Balance') : e.type === 'add' ? 'Cash Added' : 'Cash Withdrawn') }}
                  <span v-if="e.kind === 'custom_order'"
                    class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                    :class="e.sub_kind === 'advance' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'">
                    {{ e.sub_kind === 'advance' ? 'Advance' : 'Balance' }}
                  </span>
                  <span v-if="e.buyer_name" class="text-xs text-gray-400">· {{ e.buyer_name }}</span>
                  <span v-if="e.kind === 'expense' && e.category"
                    class="text-xs px-1.5 py-0.5 rounded-full bg-orange-100 text-orange-700 capitalize">{{ e.category }}</span>
                  <span v-if="e.kind === 'adjustment'"
                    class="text-xs px-1.5 py-0.5 rounded-full capitalize font-medium"
                    :class="e.type === 'add' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700'">
                    {{ e.type === 'add' ? 'Added' : 'Withdrawn' }}
                  </span>
                </span>
              </td>
              <td class="px-4 py-2.5 text-xs text-gray-500">
                <template v-if="e.kind === 'purchase' || e.kind === 'sale'">
                  {{ e.declared_karat }} · {{ Number(e.net_weight || 0).toFixed(3) }}g
                </template>
                <template v-else>—</template>
              </td>
              <td class="px-4 py-2.5 text-right font-semibold text-green-700">
                {{ e.cash_in > 0 ? 'LKR ' + lkr(e.cash_in) : '—' }}
              </td>
              <td class="px-4 py-2.5 text-right font-semibold text-red-700">
                {{ e.cash_out > 0 ? 'LKR ' + lkr(e.cash_out) : '—' }}
              </td>
              <td class="px-4 py-2.5 text-right font-bold"
                :class="e.balance >= 0 ? 'text-blue-700' : 'text-orange-700'">
                LKR {{ lkr(e.balance) }}
              </td>
            </tr>
          </tbody>
          <tfoot v-if="cashbookEntries.length" class="bg-gray-100 border-t-2 border-gray-300">
            <tr>
              <td colspan="4" class="px-4 py-2.5 text-xs font-bold text-gray-600 uppercase">Totals</td>
              <td class="px-4 py-2.5 text-right font-bold text-green-700">LKR {{ lkr(summary.total_in) }}</td>
              <td class="px-4 py-2.5 text-right font-bold text-red-700">LKR {{ lkr(summary.total_out) }}</td>
              <td class="px-4 py-2.5 text-right font-bold"
                :class="summary.balance >= 0 ? 'text-blue-700' : 'text-orange-700'">
                LKR {{ lkr(summary.balance) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </template>

    <!-- ════════════════════════════════════════
         TAB 2 — PURCHASES (buy gold)
    ════════════════════════════════════════ -->
    <template v-if="activeTab === 'purchases'">
      <div class="flex justify-end">
        <button @click="openPurchaseModal(null)"
          class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm shadow-sm">
          <PlusIcon class="w-4 h-4" /> Record Purchase (Buy Gold)
        </button>
      </div>

      <div class="card flex gap-3 flex-wrap items-end">
        <input v-model="pFilters.search" placeholder="Ref, description…" class="form-input flex-1 min-w-48" @input="loadPurchases" />
        <input v-model="pFilters.date_from" type="date" class="form-input w-36" @change="loadPurchases" />
        <input v-model="pFilters.date_to" type="date" class="form-input w-36" @change="loadPurchases" />
        <button @click="pFilters.search=''; pFilters.date_from=''; pFilters.date_to=''; loadPurchases()"
          class="btn-secondary text-sm">Clear</button>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Ref #</th>
              <th class="table-th">Date</th>
              <th class="table-th">Description</th>
              <th class="table-th">Karat</th>
              <th class="table-th">Net Wt (g)</th>
              <th class="table-th">Rate/g</th>
              <th class="table-th text-right text-red-700">Amount Paid</th>
              <th class="table-th">Method</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loadingP">
              <td colspan="9" class="py-8 text-center text-gray-400">Loading…</td>
            </tr>
            <tr v-else-if="!purchases.length">
              <td colspan="9" class="py-8 text-center text-gray-400">No purchase records found</td>
            </tr>
            <tr v-for="p in purchases" :key="p.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs text-gray-500">{{ p.reference_number }}</td>
              <td class="table-td text-xs text-gray-600">{{ fmtDate(p.purchase_date) }}</td>
              <td class="table-td">{{ p.description || '—' }}</td>
              <td class="table-td text-xs font-medium">{{ p.declared_karat }}</td>
              <td class="table-td text-right">{{ Number(p.net_weight).toFixed(3) }}</td>
              <td class="table-td text-right text-xs text-gray-600">{{ lkr(p.rate_per_gram) }}</td>
              <td class="table-td text-right font-bold text-red-700">LKR {{ lkr(p.final_price) }}</td>
              <td class="table-td text-xs capitalize">{{ p.payment_method.replace('_',' ') }}</td>
              <td class="table-td text-right">
                <div class="flex justify-end gap-1">
                  <button @click="printPurchaseInvoice(p)" title="Print Invoice"
                    class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700 hover:bg-gray-200">🖨</button>
                  <button @click="openPurchaseModal(p)"
                    class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                  <button @click="deletePurchase(p)"
                    class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">Del</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ════════════════════════════════════════
         TAB 3 — SALES (sell gold)
    ════════════════════════════════════════ -->
    <template v-if="activeTab === 'sales'">
      <div class="flex justify-end">
        <button @click="openSaleModal(null)"
          class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm shadow-sm">
          <PlusIcon class="w-4 h-4" /> Record Sale (Sell Gold)
        </button>
      </div>

      <div class="card flex gap-3 flex-wrap items-end">
        <input v-model="sFilters.search" placeholder="Ref, description, buyer…" class="form-input flex-1 min-w-48" @input="loadSales" />
        <input v-model="sFilters.date_from" type="date" class="form-input w-36" @change="loadSales" />
        <input v-model="sFilters.date_to" type="date" class="form-input w-36" @change="loadSales" />
        <button @click="sFilters.search=''; sFilters.date_from=''; sFilters.date_to=''; loadSales()"
          class="btn-secondary text-sm">Clear</button>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Ref #</th>
              <th class="table-th">Date</th>
              <th class="table-th">Buyer</th>
              <th class="table-th">Description</th>
              <th class="table-th">Karat</th>
              <th class="table-th">Net Wt (g)</th>
              <th class="table-th">Rate/g</th>
              <th class="table-th text-right text-green-700">Amount Received</th>
              <th class="table-th">Method</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loadingS">
              <td colspan="10" class="py-8 text-center text-gray-400">Loading…</td>
            </tr>
            <tr v-else-if="!salesList.length">
              <td colspan="10" class="py-8 text-center text-gray-400">No sale records found</td>
            </tr>
            <tr v-for="s in salesList" :key="s.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs text-gray-500">{{ s.reference_number }}</td>
              <td class="table-td text-xs text-gray-600">{{ fmtDate(s.sale_date) }}</td>
              <td class="table-td text-sm">{{ s.buyer_name || '—' }}</td>
              <td class="table-td">{{ s.description || '—' }}</td>
              <td class="table-td text-xs font-medium">{{ s.declared_karat }}</td>
              <td class="table-td text-right">{{ Number(s.net_weight).toFixed(3) }}</td>
              <td class="table-td text-right text-xs text-gray-600">{{ lkr(s.rate_per_gram) }}</td>
              <td class="table-td text-right font-bold text-green-700">LKR {{ lkr(s.total_amount) }}</td>
              <td class="table-td text-xs capitalize">{{ s.payment_method.replace('_',' ') }}</td>
              <td class="table-td text-right">
                <div class="flex justify-end gap-1">
                  <button @click="printSaleInvoice(s)" title="Print Invoice"
                    class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700 hover:bg-gray-200">🖨</button>
                  <button @click="openPrivateSmsModal(s)" title="Send SMS"
                    class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">SMS</button>
                  <button @click="openSaleModal(s)"
                    class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                  <button @click="deleteSale(s)"
                    class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">Del</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ════════════════════════════════════════
         TAB 4 — EXPENSES
    ════════════════════════════════════════ -->
    <template v-if="activeTab === 'expenses'">
      <div class="flex justify-end">
        <button @click="openExpenseModal(null)"
          class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium text-sm shadow-sm">
          <PlusIcon class="w-4 h-4" /> Add Expense
        </button>
      </div>

      <div class="card flex gap-3 flex-wrap items-end">
        <input v-model="eFilters.search" placeholder="Description…" class="form-input flex-1 min-w-48" @input="loadExpenses" />
        <select v-model="eFilters.category" class="form-input w-36" @change="loadExpenses">
          <option value="">All Categories</option>
          <option value="transport">Transport</option>
          <option value="fees">Fees</option>
          <option value="commission">Commission</option>
          <option value="testing">Testing</option>
          <option value="tools">Tools</option>
          <option value="misc">Misc</option>
        </select>
        <input v-model="eFilters.date_from" type="date" class="form-input w-36" @change="loadExpenses" />
        <input v-model="eFilters.date_to" type="date" class="form-input w-36" @change="loadExpenses" />
        <button @click="eFilters.search=''; eFilters.category=''; eFilters.date_from=''; eFilters.date_to=''; loadExpenses()"
          class="btn-secondary text-sm">Clear</button>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Ref #</th>
              <th class="table-th">Date</th>
              <th class="table-th">Category</th>
              <th class="table-th">Description</th>
              <th class="table-th">Method</th>
              <th class="table-th text-right text-orange-700">Amount</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loadingE">
              <td colspan="7" class="py-8 text-center text-gray-400">Loading…</td>
            </tr>
            <tr v-else-if="!expensesList.length">
              <td colspan="7" class="py-8 text-center text-gray-400">No expense records found</td>
            </tr>
            <tr v-for="e in expensesList" :key="e.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs text-gray-500">{{ e.reference_number }}</td>
              <td class="table-td text-xs text-gray-600">{{ fmtDate(e.expense_date) }}</td>
              <td class="table-td">
                <span class="text-xs px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 capitalize font-medium">
                  {{ e.category }}
                </span>
              </td>
              <td class="table-td">{{ e.description }}</td>
              <td class="table-td text-xs capitalize">{{ e.payment_method.replace('_', ' ') }}</td>
              <td class="table-td text-right font-bold text-orange-700">LKR {{ lkr(e.amount) }}</td>
              <td class="table-td text-right">
                <div class="flex justify-end gap-1">
                  <button @click="openExpenseModal(e)"
                    class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">Edit</button>
                  <button @click="deleteExpense(e)"
                    class="px-2 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200">Del</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ══════════════════════════════════════
         PURCHASE MODAL
    ══════════════════════════════════════ -->
    <teleport to="body">
      <div v-if="purchaseModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
              {{ editingPurchase ? 'Edit Purchase' : 'Record Gold Purchase (Cash Out)' }}
            </h3>
            <button @click="closePurchaseModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
          <form @submit.prevent="savePurchase" class="p-6 space-y-4">
            <!-- 2-column main fields -->
            <div class="grid grid-cols-2 gap-6">
              <!-- LEFT COLUMN -->
              <div class="space-y-3">
                <div>
                  <label class="form-label">Purchase Date *</label>
                  <input v-model="pForm.purchase_date" type="date" required class="form-input" />
                </div>
                <div>
                  <label class="form-label">Item Type</label>
                  <select v-model="pForm.item_type" class="form-input">
                    <option value="jewelry">Jewelry</option>
                    <option value="coin">Coin</option>
                    <option value="bar">Bar</option>
                    <option value="scrap">Scrap</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Karat</label>
                  <select v-model="pForm.declared_karat" class="form-input">
                    <option value="24K">24K</option>
                    <option value="23K">23K</option>
                    <option value="22K">22K</option>
                    <option value="21K">21K</option>
                    <option value="20K">20K</option>
                    <option value="19K">19K</option>
                    <option value="18K">18K</option>
                    <option value="17K">17K</option>
                    <option value="16K">16K</option>
                    <option value="15K">15K</option>
                    <option value="14K">14K</option>
                    <option value="13K">13K</option>
                    <option value="12K">12K</option>
                    <option value="11K">11K</option>
                    <option value="10K">10K</option>
                    <option value="9K">9K</option>
                    <option value="8K">8K</option>
                    <option value="unknown">Unknown</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Gross Wt (g)</label>
                  <input v-model.number="pForm.gross_weight" type="number" step="0.001" min="0" class="form-input" @input="pCalc" />
                </div>
                <div>
                  <label class="form-label">Deduction (g)</label>
                  <input v-model.number="pForm.deduction_weight" type="number" step="0.001" min="0" class="form-input" @input="pCalc" />
                </div>
                <div>
                  <label class="form-label">Net Wt (g)</label>
                  <input v-model.number="pForm.net_weight" type="number" step="0.001" min="0" class="form-input bg-gray-50" readonly />
                </div>
              </div>
              <!-- RIGHT COLUMN -->
              <div class="space-y-3">
                <div>
                  <label class="form-label">Description</label>
                  <input v-model="pForm.description" type="text" placeholder="e.g. Gold necklace 22K" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Rate / gram (LKR)</label>
                  <input v-model.number="pForm.rate_per_gram" type="number" step="0.01" min="0" class="form-input" @input="pCalc" />
                </div>
                <div>
                  <label class="form-label">Total Amount (LKR)</label>
                  <input v-model.number="pForm.final_price" type="number" step="0.01" min="0" class="form-input font-bold" />
                </div>
                <div>
                  <label class="form-label">Payment Method</label>
                  <select v-model="pForm.payment_method" class="form-input">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Notes</label>
                  <textarea v-model="pForm.notes" rows="4" class="form-input resize-none" placeholder="Seller name, condition, remarks…"></textarea>
                </div>
              </div>
            </div>

            <!-- Photo capture section - full width -->
            <div class="border-t pt-4 space-y-3">
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference Photos</p>
              <div class="grid grid-cols-4 gap-3">
                <!-- NIC Front -->
                <div class="flex flex-col gap-1.5">
                  <p class="text-xs font-medium text-gray-500 text-center">NIC Front</p>
                  <div class="relative border-2 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center cursor-pointer"
                    style="aspect-ratio:3/4"
                    @click="!pForm.nic_front_url && !photoUploading.nic_front && $refs.fNicFront.click()">
                    <img v-if="pForm.nic_front_url" :src="pForm.nic_front_url" class="w-full h-full object-cover" />
                    <div v-else-if="photoUploading.nic_front" class="flex flex-col items-center gap-1 text-gray-400">
                      <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                      <span class="text-xs">Uploading…</span>
                    </div>
                    <div v-else class="flex flex-col items-center gap-1 text-gray-300 select-none">
                      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 9.75h18M4.5 19.5h15a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H4.5A1.5 1.5 0 003 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
                      <span class="text-xs">Tap to add</span>
                    </div>
                    <button v-if="pForm.nic_front_url" type="button" @click.stop="pForm.nic_front_url = ''"
                      class="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600">&times;</button>
                  </div>
                  <input ref="fNicFront" type="file" accept="image/*" class="hidden" @change="onFileSelect($event, 'nic_front')" />
                  <div class="grid grid-cols-2 gap-1">
                    <button type="button" @click="$refs.fNicFront.click()" :disabled="photoUploading.nic_front"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📁 File</button>
                    <button type="button" @click="openCamera('nic_front')" :disabled="photoUploading.nic_front"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📷 Cam</button>
                  </div>
                </div>

                <!-- NIC Back -->
                <div class="flex flex-col gap-1.5">
                  <p class="text-xs font-medium text-gray-500 text-center">NIC Back</p>
                  <div class="relative border-2 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center cursor-pointer"
                    style="aspect-ratio:3/4"
                    @click="!pForm.nic_back_url && !photoUploading.nic_back && $refs.fNicBack.click()">
                    <img v-if="pForm.nic_back_url" :src="pForm.nic_back_url" class="w-full h-full object-cover" />
                    <div v-else-if="photoUploading.nic_back" class="flex flex-col items-center gap-1 text-gray-400">
                      <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                      <span class="text-xs">Uploading…</span>
                    </div>
                    <div v-else class="flex flex-col items-center gap-1 text-gray-300 select-none">
                      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 9.75h18M4.5 19.5h15a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H4.5A1.5 1.5 0 003 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
                      <span class="text-xs">Tap to add</span>
                    </div>
                    <button v-if="pForm.nic_back_url" type="button" @click.stop="pForm.nic_back_url = ''"
                      class="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600">&times;</button>
                  </div>
                  <input ref="fNicBack" type="file" accept="image/*" class="hidden" @change="onFileSelect($event, 'nic_back')" />
                  <div class="grid grid-cols-2 gap-1">
                    <button type="button" @click="$refs.fNicBack.click()" :disabled="photoUploading.nic_back"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📁 File</button>
                    <button type="button" @click="openCamera('nic_back')" :disabled="photoUploading.nic_back"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📷 Cam</button>
                  </div>
                </div>

                <!-- Invoice -->
                <div class="flex flex-col gap-1.5">
                  <p class="text-xs font-medium text-gray-500 text-center">Invoice</p>
                  <div class="relative border-2 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center cursor-pointer"
                    style="aspect-ratio:3/4"
                    @click="!pForm.invoice_photo_url && !photoUploading.invoice && $refs.fInvoice.click()">
                    <img v-if="pForm.invoice_photo_url" :src="pForm.invoice_photo_url" class="w-full h-full object-cover" />
                    <div v-else-if="photoUploading.invoice" class="flex flex-col items-center gap-1 text-gray-400">
                      <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                      <span class="text-xs">Uploading…</span>
                    </div>
                    <div v-else class="flex flex-col items-center gap-1 text-gray-300 select-none">
                      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 9.75h18M4.5 19.5h15a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H4.5A1.5 1.5 0 003 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
                      <span class="text-xs">Tap to add</span>
                    </div>
                    <button v-if="pForm.invoice_photo_url" type="button" @click.stop="pForm.invoice_photo_url = ''"
                      class="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600">&times;</button>
                  </div>
                  <input ref="fInvoice" type="file" accept="image/*" class="hidden" @change="onFileSelect($event, 'invoice')" />
                  <div class="grid grid-cols-2 gap-1">
                    <button type="button" @click="$refs.fInvoice.click()" :disabled="photoUploading.invoice"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📁 File</button>
                    <button type="button" @click="openCamera('invoice')" :disabled="photoUploading.invoice"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📷 Cam</button>
                  </div>
                </div>

                <!-- Weight Scale -->
                <div class="flex flex-col gap-1.5">
                  <p class="text-xs font-medium text-gray-500 text-center">Weight Scale</p>
                  <div class="relative border-2 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center cursor-pointer"
                    style="aspect-ratio:3/4"
                    @click="!pForm.weight_photo_url && !photoUploading.weight && $refs.fWeight.click()">
                    <img v-if="pForm.weight_photo_url" :src="pForm.weight_photo_url" class="w-full h-full object-cover" />
                    <div v-else-if="photoUploading.weight" class="flex flex-col items-center gap-1 text-gray-400">
                      <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                      <span class="text-xs">Uploading…</span>
                    </div>
                    <div v-else class="flex flex-col items-center gap-1 text-gray-300 select-none">
                      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 9.75h18M4.5 19.5h15a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H4.5A1.5 1.5 0 003 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
                      <span class="text-xs">Tap to add</span>
                    </div>
                    <button v-if="pForm.weight_photo_url" type="button" @click.stop="pForm.weight_photo_url = ''"
                      class="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600">&times;</button>
                  </div>
                  <input ref="fWeight" type="file" accept="image/*" class="hidden" @change="onFileSelect($event, 'weight')" />
                  <div class="grid grid-cols-2 gap-1">
                    <button type="button" @click="$refs.fWeight.click()" :disabled="photoUploading.weight"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📁 File</button>
                    <button type="button" @click="openCamera('weight')" :disabled="photoUploading.weight"
                      class="text-xs py-1 px-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40">📷 Cam</button>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="pError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ pError }}</p>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closePurchaseModal" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="pSaving || anyPhotoUploading"
                class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-lg font-medium text-sm">
                {{ pSaving ? 'Saving…' : anyPhotoUploading ? 'Uploading photos…' : (editingPurchase ? 'Update' : 'Record Purchase') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- ══════════════════════════════════════
         CAMERA MODAL
    ══════════════════════════════════════ -->
    <teleport to="body">
      <div v-if="cameraOpen" class="fixed inset-0 z-[60] bg-black/80 flex items-center justify-center p-4">
        <div class="bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <div class="flex items-center justify-between px-5 py-3 border-b border-gray-700">
            <span class="text-white font-semibold text-sm">
              Camera — {{ cameraLabels[cameraField] }}
            </span>
            <button @click="closeCamera" class="text-gray-400 hover:text-white text-xl leading-none">&times;</button>
          </div>

          <!-- Video preview -->
          <div class="relative bg-black" style="aspect-ratio:4/3;">
            <video ref="videoEl" autoplay playsinline
              class="w-full h-full object-cover" style="transform:scaleX(-1)"></video>
            <canvas ref="canvasEl" class="hidden"></canvas>
            <!-- Flash overlay -->
            <div v-if="cameraFlash" class="absolute inset-0 bg-white pointer-events-none" style="opacity:0.7;"></div>
          </div>

          <!-- Controls -->
          <div class="flex items-center justify-center gap-4 px-5 py-4">
            <button @click="closeCamera"
              class="px-4 py-2 text-sm text-gray-300 border border-gray-600 rounded-lg hover:bg-gray-700">
              Cancel
            </button>
            <button @click="capturePhoto"
              class="w-14 h-14 rounded-full bg-white hover:bg-gray-100 border-4 border-gray-300 shadow-lg flex items-center justify-center transition-transform active:scale-95">
              <div class="w-10 h-10 rounded-full bg-gray-800"></div>
            </button>
            <button @click="switchCamera"
              class="px-4 py-2 text-sm text-gray-300 border border-gray-600 rounded-lg hover:bg-gray-700">
              Flip
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- ══════════════════════════════════════
         SALE MODAL
    ══════════════════════════════════════ -->
    <teleport to="body">
      <div v-if="saleModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>
              {{ editingSale ? 'Edit Sale' : 'Record Gold Sale (Cash In)' }}
            </h3>
            <button @click="saleModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
          <form @submit.prevent="saveSale" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Sale Date *</label>
                <input v-model="sForm.sale_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Item Type</label>
                <select v-model="sForm.item_type" class="form-input">
                  <option value="jewelry">Jewelry</option>
                  <option value="coin">Coin</option>
                  <option value="bar">Bar</option>
                  <option value="scrap">Scrap</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>

            <!-- Buyer combobox -->
            <div>
              <label class="form-label">Buyer</label>
              <div class="relative">
                <input
                  v-model="buyerSearch"
                  type="text"
                  placeholder="Search or type buyer name…"
                  class="form-input w-full"
                  autocomplete="off"
                  @input="onBuyerInput"
                  @focus="openBuyerDropdown"
                  @blur="closeBuyerDropdown"
                />
                <!-- Dropdown -->
                <div v-if="buyerDropdownOpen"
                  class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto"
                  @mousedown="_buyerMouseInDropdown = true">
                  <!-- Existing buyers list -->
                  <button
                    v-for="b in filteredBuyers" :key="b.id"
                    type="button"
                    class="w-full text-left px-3 py-2 hover:bg-amber-50 flex items-center justify-between gap-2"
                    @mousedown.prevent="selectBuyer(b)">
                    <span class="font-medium text-sm text-gray-800">{{ b.name }}</span>
                    <span v-if="b.phone" class="text-xs text-gray-400">{{ b.phone }}</span>
                  </button>
                  <div v-if="!filteredBuyers.length" class="px-3 py-2 text-xs text-gray-400">
                    No buyers found.
                  </div>
                  <!-- Add new buyer -->
                  <div class="border-t border-gray-100">
                    <button v-if="!buyerAddFormOpen" type="button"
                      class="w-full text-left px-3 py-2 text-xs text-green-600 font-medium hover:bg-green-50 flex items-center gap-1"
                      @mousedown.prevent="buyerAddFormOpen = true; newBuyerName = buyerSearch">
                      + Add New Buyer
                    </button>
                    <div v-else class="p-2 space-y-2">
                      <p class="text-xs text-gray-500 font-medium px-1">New Buyer:</p>
                      <div class="flex gap-2">
                        <input v-model="newBuyerName" type="text" placeholder="Name" class="form-input flex-1 text-sm py-1" @mousedown.stop />
                        <input v-model="newBuyerPhone" type="text" placeholder="Phone (opt.)" class="form-input w-28 text-sm py-1" @mousedown.stop />
                      </div>
                      <div class="flex gap-2">
                        <input v-model="newBuyerAddress" type="text" placeholder="Address (opt.)" class="form-input flex-1 text-sm py-1" @mousedown.stop />
                        <button type="button" :disabled="!newBuyerName.trim() || buyerCreating"
                          class="px-3 py-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-xs rounded-lg font-medium"
                          @mousedown.prevent="createAndSelectBuyer">
                          {{ buyerCreating ? '…' : 'Save' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Phone</label>
                <input v-model="sForm.buyer_phone" type="text" placeholder="e.g. 0771234567" class="form-input" />
              </div>
              <div>
                <label class="form-label">Address</label>
                <input v-model="sForm.buyer_address" type="text" placeholder="Customer address" class="form-input" />
              </div>
            </div>
            <div>
              <label class="form-label">Description</label>
              <input v-model="sForm.description" type="text" placeholder="e.g. Gold chain 22K, 15g" class="form-input" />
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="form-label">Karat</label>
                <select v-model="sForm.declared_karat" class="form-input">
                  <option value="24K">24K</option>
                  <option value="23K">23K</option>
                  <option value="22K">22K</option>
                  <option value="21K">21K</option>
                  <option value="20K">20K</option>
                  <option value="19K">19K</option>
                  <option value="18K">18K</option>
                  <option value="17K">17K</option>
                  <option value="16K">16K</option>
                  <option value="15K">15K</option>
                  <option value="14K">14K</option>
                  <option value="13K">13K</option>
                  <option value="12K">12K</option>
                  <option value="11K">11K</option>
                  <option value="10K">10K</option>
                  <option value="9K">9K</option>
                  <option value="8K">8K</option>
                  <option value="unknown">Unknown</option>
                </select>
              </div>
              <div>
                <label class="form-label">Gross Wt (g)</label>
                <input v-model.number="sForm.gross_weight" type="number" step="0.001" min="0" class="form-input" @input="sCalc" />
              </div>
              <div>
                <label class="form-label">Net Wt (g)</label>
                <input v-model.number="sForm.net_weight" type="number" step="0.001" min="0" class="form-input" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Rate / gram (LKR)</label>
                <input v-model.number="sForm.rate_per_gram" type="number" step="0.01" min="0" class="form-input" @input="sCalc" />
              </div>
              <div>
                <label class="form-label">Total Amount (LKR)</label>
                <input v-model.number="sForm.total_amount" type="number" step="0.01" min="0" class="form-input font-bold" />
              </div>
            </div>
            <div>
              <label class="form-label">Payment Method</label>
              <select v-model="sForm.payment_method" class="form-input">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>
            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="sForm.notes" rows="2" class="form-input resize-none" placeholder="Remarks…"></textarea>
            </div>
            <p v-if="sError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ sError }}</p>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="saleModal = false" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="sSaving"
                class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-lg font-medium text-sm">
                {{ sSaving ? 'Saving…' : (editingSale ? 'Update' : 'Record Sale') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- ══════════════════════════════════════
         EXPENSE MODAL
    ══════════════════════════════════════ -->
    <teleport to="body">
      <div v-if="expenseModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span>
              {{ editingExpense ? 'Edit Expense' : 'Add Expense' }}
            </h3>
            <button @click="expenseModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
          <form @submit.prevent="saveExpense" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Date *</label>
                <input v-model="eForm.expense_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Category *</label>
                <select v-model="eForm.category" class="form-input">
                  <option value="transport">Transport</option>
                  <option value="fees">Fees</option>
                  <option value="commission">Commission</option>
                  <option value="testing">Testing / Assay</option>
                  <option value="tools">Tools &amp; Equipment</option>
                  <option value="misc">Miscellaneous</option>
                </select>
              </div>
            </div>
            <div>
              <label class="form-label">Description *</label>
              <input v-model="eForm.description" type="text" required placeholder="What was this expense for?" class="form-input" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Amount (LKR) *</label>
                <input v-model.number="eForm.amount" type="number" step="0.01" min="0.01" required class="form-input font-bold" />
              </div>
              <div>
                <label class="form-label">Payment Method</label>
                <select v-model="eForm.payment_method" class="form-input">
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>
            </div>
            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="eForm.notes" rows="2" class="form-input resize-none" placeholder="Optional remarks…"></textarea>
            </div>
            <p v-if="eError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ eError }}</p>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="expenseModal = false" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="eSaving"
                class="flex-1 px-4 py-2 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white rounded-lg font-medium text-sm">
                {{ eSaving ? 'Saving…' : (editingExpense ? 'Update' : 'Add Expense') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- ══════════════════════════════════════
         PRIVATE SALE SMS MODAL
    ══════════════════════════════════════ -->
    <teleport to="body">
      <div v-if="privateSmsModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Send Invoice SMS</h3>
            <button @click="privateSmsModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
          <div class="px-6 py-4 space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
              <input v-model="privateSmsPhone" type="tel" placeholder="e.g. 0771234567" class="form-input w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
              <textarea v-model="privateSmsMessage" rows="4" class="form-input w-full resize-none text-sm"></textarea>
              <p class="text-xs text-gray-400 mt-1">{{ privateSmsMessage.length }} characters</p>
            </div>
            <p v-if="privateSmsError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ privateSmsError }}</p>
            <p v-if="privateSmsSent" class="text-sm text-green-700 bg-green-50 px-3 py-2 rounded">SMS sent successfully!</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t">
            <button @click="privateSmsModal = false; privateSmsSent = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
            <button @click="submitPrivateSms" :disabled="privateSmsLoading || privateSmsSent || !privateSmsPhone.trim()"
              class="px-4 py-2 text-sm bg-blue-500 hover:bg-blue-600 disabled:opacity-60 text-white rounded-lg font-medium">
              {{ privateSmsLoading ? 'Sending…' : privateSmsSent ? 'Sent' : 'Send SMS' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- ══════════════════════════════════════
         ADJUSTMENT MODAL
    ══════════════════════════════════════ -->
    <teleport to="body">
      <div v-if="adjModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full inline-block"
                :class="adjForm.type === 'add' ? 'bg-indigo-500' : 'bg-purple-600'"></span>
              {{ adjForm.type === 'add' ? 'Add Cash to Cashbook' : 'Withdraw Cash from Cashbook' }}
            </h3>
            <button @click="adjModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
          <form @submit.prevent="saveAdj" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Date *</label>
                <input v-model="adjForm.adjustment_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Type *</label>
                <select v-model="adjForm.type" class="form-input">
                  <option value="add">Add Cash</option>
                  <option value="withdraw">Withdraw Cash</option>
                </select>
              </div>
            </div>
            <div>
              <label class="form-label">Description *</label>
              <input v-model="adjForm.description" type="text" required placeholder="e.g. Boss added funds, Cash withdrawn…" class="form-input" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Amount (LKR) *</label>
                <input v-model.number="adjForm.amount" type="number" step="0.01" min="0.01" required class="form-input font-bold" />
              </div>
              <div>
                <label class="form-label">Payment Method</label>
                <select v-model="adjForm.payment_method" class="form-input">
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>
            </div>
            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="adjForm.notes" rows="2" class="form-input resize-none" placeholder="Optional remarks…"></textarea>
            </div>
            <p v-if="adjError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ adjError }}</p>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="adjModal = false" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="adjSaving"
                class="flex-1 px-4 py-2 disabled:opacity-50 text-white rounded-lg font-medium text-sm"
                :class="adjForm.type === 'add' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-purple-600 hover:bg-purple-700'">
                {{ adjSaving ? 'Saving…' : (adjForm.type === 'add' ? 'Add Cash' : 'Withdraw Cash') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { LockClosedIcon, PlusIcon, MinusIcon, ArrowDownTrayIcon, PrinterIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { fmtDate } from '../utils/date.js'

const route  = useRoute()
const router = useRouter()

// ── tabs ───────────────────────────────────────────────
const tabs = [
  { id: 'cashbook',  label: '📒 Cashbook' },
  { id: 'purchases', label: '🔴 Purchases (Buy)' },
  { id: 'sales',     label: '🟢 Sales (Sell)' },
  { id: 'expenses',  label: '🟠 Expenses' },
]

const VALID_TABS = tabs.map(t => t.id)
const activeTab = ref(VALID_TABS.includes(route.query.tab) ? route.query.tab : 'cashbook')

watch(activeTab, (tab) => {
  router.replace({ query: { ...route.query, tab } })
})

watch(() => route.query.tab, (tab) => {
  if (tab && VALID_TABS.includes(tab) && tab !== activeTab.value) {
    activeTab.value = tab
  }
})


function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// ── summary (from cashbook endpoint) ──────────────────
const summary = reactive({ total_in: 0, total_out: 0, total_purchases: 0, total_expenses: 0, balance: 0 })

// ── CASHBOOK ───────────────────────────────────────────
const cashbookEntries = ref([])
const loadingCb = ref(false)
const cbFilters = reactive({ date_from: '', date_to: '' })

async function loadCashbook() {
  loadingCb.value = true
  try {
    const { data } = await axios.get('/api/private-cashbook', { params: cbFilters })
    cashbookEntries.value     = data.entries
    summary.total_in          = data.total_in
    summary.total_out         = data.total_out
    summary.total_purchases   = data.total_out - (data.total_expenses ?? 0)
    summary.total_expenses    = data.total_expenses ?? 0
    summary.balance           = data.balance
  } finally { loadingCb.value = false }
}

// ── PURCHASES ──────────────────────────────────────────
const purchases = ref([])
const loadingP  = ref(false)
const pFilters  = reactive({ search: '', date_from: '', date_to: '' })
const purchaseModal  = ref(false)
const editingPurchase = ref(null)
const pSaving = ref(false)
const pError  = ref('')

const pForm = reactive({
  purchase_date: '', item_type: 'jewelry', description: '',
  declared_karat: '22K', gross_weight: 0, deduction_weight: 0, net_weight: 0,
  rate_per_gram: 0, final_price: 0, payment_method: 'cash', notes: '',
  nic_front_url: '', nic_back_url: '', invoice_photo_url: '', weight_photo_url: '',
})

// ── photo upload ───────────────────────────────────────
const photoUploading = reactive({ nic_front: false, nic_back: false, invoice: false, weight: false })
const anyPhotoUploading = computed(() => Object.values(photoUploading).some(Boolean))

const photoFieldMap = {
  nic_front: 'nic_front_url',
  nic_back:  'nic_back_url',
  invoice:   'invoice_photo_url',
  weight:    'weight_photo_url',
}

async function uploadToCloudinary(file) {
  const fd = new FormData()
  fd.append('file', file)
  fd.append('folder', 'private-purchases')
  const { data } = await axios.post('/api/uploads/cloudinary', fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.url
}

async function onFileSelect(event, field) {
  const file = event.target.files?.[0]
  if (!file) return
  photoUploading[field] = true
  try {
    pForm[photoFieldMap[field]] = await uploadToCloudinary(file)
  } catch {
    alert('Photo upload failed. Please try again.')
  } finally {
    photoUploading[field] = false
    event.target.value = ''
  }
}

// ── camera ────────────────────────────────────────────
const cameraOpen   = ref(false)
const cameraField  = ref('')
const cameraFlash  = ref(false)
const cameraFacing = ref('environment')
const videoEl      = ref(null)
const canvasEl     = ref(null)
let   cameraStream = null

const cameraLabels = { nic_front: 'NIC Front', nic_back: 'NIC Back', invoice: 'Invoice', weight: 'Weight Scale' }

async function openCamera(field) {
  cameraField.value = field
  cameraOpen.value  = true
  await nextTick()
  await startStream()
}

async function startStream() {
  if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()) }
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: cameraFacing.value, width: { ideal: 1280 }, height: { ideal: 960 } },
    })
    if (videoEl.value) videoEl.value.srcObject = cameraStream
  } catch {
    alert('Could not access camera. Please use the file picker instead.')
    closeCamera()
  }
}

async function switchCamera() {
  cameraFacing.value = cameraFacing.value === 'environment' ? 'user' : 'environment'
  await startStream()
}

async function capturePhoto() {
  const video  = videoEl.value
  const canvas = canvasEl.value
  if (!video || !canvas) return

  canvas.width  = video.videoWidth
  canvas.height = video.videoHeight
  const ctx = canvas.getContext('2d')
  // Mirror if front camera
  if (cameraFacing.value === 'user') {
    ctx.translate(canvas.width, 0)
    ctx.scale(-1, 1)
  }
  ctx.drawImage(video, 0, 0)

  // Flash effect
  cameraFlash.value = true
  setTimeout(() => { cameraFlash.value = false }, 200)

  const field = cameraField.value
  closeCamera()

  // Convert canvas to blob and upload
  canvas.toBlob(async (blob) => {
    photoUploading[field] = true
    try {
      const file = new File([blob], `${field}-${Date.now()}.jpg`, { type: 'image/jpeg' })
      pForm[photoFieldMap[field]] = await uploadToCloudinary(file)
    } catch {
      alert('Photo upload failed. Please try again.')
    } finally {
      photoUploading[field] = false
    }
  }, 'image/jpeg', 0.88)
}

function closeCamera() {
  cameraOpen.value = false
  if (cameraStream) {
    cameraStream.getTracks().forEach(t => t.stop())
    cameraStream = null
  }
}

function pCalc() {
  pForm.net_weight = Math.max(0, (pForm.gross_weight || 0) - (pForm.deduction_weight || 0))
  pForm.final_price = Math.round((pForm.net_weight || 0) * (pForm.rate_per_gram || 0) * 100) / 100
}

async function loadPurchases() {
  loadingP.value = true
  try {
    const { data } = await axios.get('/api/informal-purchases', { params: pFilters })
    purchases.value = data.data ?? data
  } finally { loadingP.value = false }
}

function openPurchaseModal(p) {
  editingPurchase.value = p
  pError.value = ''
  Object.assign(pForm, {
    purchase_date:    p?.purchase_date ?? new Date().toISOString().slice(0, 10),
    item_type:        p?.item_type ?? 'jewelry',
    description:      p?.description ?? '',
    declared_karat:   p?.declared_karat ?? '22K',
    gross_weight:     p?.gross_weight ?? 0,
    deduction_weight: p?.deduction_weight ?? 0,
    net_weight:       p?.net_weight ?? 0,
    rate_per_gram:    p?.rate_per_gram ?? todayRateMap.value[p?.declared_karat ?? '22K'] ?? 0,
    final_price:      p?.final_price ?? 0,
    payment_method:   p?.payment_method ?? 'cash',
    notes:            p?.notes ?? '',
    nic_front_url:     p?.nic_front_url ?? '',
    nic_back_url:      p?.nic_back_url ?? '',
    invoice_photo_url: p?.invoice_photo_url ?? '',
    weight_photo_url:  p?.weight_photo_url ?? '',
  })
  purchaseModal.value = true
}

async function savePurchase() {
  pSaving.value = true; pError.value = ''
  try {
    if (editingPurchase.value) {
      await axios.put(`/api/informal-purchases/${editingPurchase.value.id}`, pForm)
    } else {
      await axios.post('/api/informal-purchases', pForm)
    }
    purchaseModal.value = false
    await Promise.all([loadPurchases(), loadCashbook()])
  } catch (e) {
    pError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { pSaving.value = false }
}

function closePurchaseModal() {
  purchaseModal.value = false
  closeCamera()
}

async function deletePurchase(p) {
  if (!confirm(`Delete purchase ${p.reference_number}?`)) return
  await axios.delete(`/api/informal-purchases/${p.id}`)
  await Promise.all([loadPurchases(), loadCashbook()])
}

// ── TODAY'S GOLD RATES ─────────────────────────────────
const todayRateMap         = ref({})
const showRateWarning      = ref(false)
const rateWarningDismissed = ref(false)
const copyingRates         = ref(false)
const copyRateError        = ref('')

async function loadTodayRates() {
  try {
    const { data } = await axios.get('/api/gold-rates/today')
    const map = {}
    ;(Array.isArray(data) ? data : Object.values(data)).forEach(r => {
      if (r.carat?.label) map[r.carat.label] = r.rate_per_gram
    })
    todayRateMap.value = map
  } catch {}
  if (!rateWarningDismissed.value) {
    showRateWarning.value = Object.keys(todayRateMap.value).length === 0
  }
}

async function copyPreviousRates() {
  copyingRates.value = true; copyRateError.value = ''
  try {
    const { data } = await axios.get('/api/gold-rates')
    const dates = Object.keys(data.history).sort().reverse()
    if (!dates.length) { copyRateError.value = 'No previous rates found.'; return }
    const previousRates = data.history[dates[0]]
    const rates = previousRates.map(r => ({ carat_id: r.carat_id, rate_per_gram: r.rate_per_gram }))
    await axios.post('/api/gold-rates', { date: new Date().toISOString().split('T')[0], rates })
    await loadTodayRates()
    showRateWarning.value = false
  } catch (e) {
    copyRateError.value = e.response?.data?.message ?? 'Failed to copy rates. You may not have permission.'
  } finally {
    copyingRates.value = false
  }
}

// ── PRIVATE BUYERS ─────────────────────────────────────
const buyersList        = ref([])
const buyerSearch       = ref('')
const buyerDropdownOpen = ref(false)
const buyerAddFormOpen  = ref(false)
const buyerCreating     = ref(false)
const newBuyerName      = ref('')
const newBuyerPhone     = ref('')
const newBuyerAddress   = ref('')
let _buyerMouseInDropdown = false

async function loadBuyers(search = '') {
  const { data } = await axios.get('/api/private-buyers', { params: search ? { search } : {} })
  buyersList.value = data
}

const filteredBuyers = computed(() => {
  const q = buyerSearch.value.toLowerCase()
  return q ? buyersList.value.filter(b => b.name.toLowerCase().includes(q) || (b.phone || '').includes(q)) : buyersList.value
})

function selectBuyer(buyer) {
  sForm.buyer_name    = buyer.name
  sForm.buyer_phone   = buyer.phone || ''
  sForm.buyer_address = buyer.address || ''
  buyerSearch.value       = buyer.name
  buyerDropdownOpen.value = false
  buyerAddFormOpen.value  = false
}

function openBuyerDropdown() {
  buyerDropdownOpen.value = true
  newBuyerName.value = buyerSearch.value
}

function onBuyerInput() {
  sForm.buyer_name = buyerSearch.value
  newBuyerName.value = buyerSearch.value
  buyerDropdownOpen.value = true
  buyerAddFormOpen.value  = false
}

function closeBuyerDropdown() {
  setTimeout(() => {
    if (_buyerMouseInDropdown) { _buyerMouseInDropdown = false; return }
    buyerDropdownOpen.value = false
    buyerAddFormOpen.value  = false
  }, 180)
}

async function createAndSelectBuyer() {
  if (!newBuyerName.value.trim()) return
  buyerCreating.value = true
  try {
    const { data } = await axios.post('/api/private-buyers', {
      name:    newBuyerName.value.trim(),
      phone:   newBuyerPhone.value.trim() || null,
      address: newBuyerAddress.value.trim() || null,
    })
    buyersList.value.unshift(data)
    selectBuyer(data)
    newBuyerName.value    = ''
    newBuyerPhone.value   = ''
    newBuyerAddress.value = ''
    buyerCreating.value = false
    buyerAddFormOpen.value = false
  } catch { buyerCreating.value = false }
}

// ── SALES ──────────────────────────────────────────────
const salesList  = ref([])
const loadingS   = ref(false)
const sFilters   = reactive({ search: '', date_from: '', date_to: '' })
const saleModal  = ref(false)
const editingSale = ref(null)
const sSaving = ref(false)
const sError  = ref('')

const sForm = reactive({
  sale_date: '', item_type: 'jewelry', buyer_name: '', buyer_phone: '', buyer_address: '', description: '',
  declared_karat: '22K', gross_weight: 0, net_weight: 0,
  rate_per_gram: 0, total_amount: 0, payment_method: 'cash', notes: '',
})

function sCalc() {
  sForm.net_weight  = sForm.gross_weight || 0
  sForm.total_amount = Math.round((sForm.net_weight || 0) * (sForm.rate_per_gram || 0) * 100) / 100
}

// Auto-update rate when karat changes on new records
watch(() => sForm.declared_karat, (karat) => {
  if (!editingSale.value) { sForm.rate_per_gram = todayRateMap.value[karat] ?? 0; sCalc() }
})
watch(() => pForm.declared_karat, (karat) => {
  if (!editingPurchase.value) { pForm.rate_per_gram = todayRateMap.value[karat] ?? 0; pCalc() }
})

async function loadSales() {
  loadingS.value = true
  try {
    const { data } = await axios.get('/api/private-sales', { params: sFilters })
    salesList.value = data.data ?? data
  } finally { loadingS.value = false }
}

function openSaleModal(s) {
  editingSale.value = s
  sError.value = ''
  buyerDropdownOpen.value = false
  buyerAddFormOpen.value  = false
  newBuyerName.value  = ''
  newBuyerPhone.value = ''
  Object.assign(sForm, {
    sale_date:      s?.sale_date ?? new Date().toISOString().slice(0, 10),
    item_type:      s?.item_type ?? 'jewelry',
    buyer_name:     s?.buyer_name ?? '',
    buyer_phone:    s?.buyer_phone ?? '',
    buyer_address:  s?.buyer_address ?? '',
    description:    s?.description ?? '',
    declared_karat: s?.declared_karat ?? '22K',
    gross_weight:   s?.gross_weight ?? 0,
    net_weight:     s?.net_weight ?? 0,
    rate_per_gram:  s?.rate_per_gram ?? todayRateMap.value[s?.declared_karat ?? '22K'] ?? 0,
    total_amount:   s?.total_amount ?? 0,
    payment_method: s?.payment_method ?? 'cash',
    notes:          s?.notes ?? '',
  })
  buyerSearch.value = sForm.buyer_name
  saleModal.value = true
}

async function saveSale() {
  sSaving.value = true; sError.value = ''
  try {
    if (editingSale.value) {
      await axios.put(`/api/private-sales/${editingSale.value.id}`, sForm)
    } else {
      await axios.post('/api/private-sales', sForm)
    }
    saleModal.value = false
    await Promise.all([loadSales(), loadCashbook()])
  } catch (e) {
    sError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { sSaving.value = false }
}

async function deleteSale(s) {
  if (!confirm(`Delete sale ${s.reference_number}?`)) return
  await axios.delete(`/api/private-sales/${s.id}`)
  await Promise.all([loadSales(), loadCashbook()])
}

// ── EXPENSES ───────────────────────────────────────────
const expensesList  = ref([])
const loadingE      = ref(false)
const eFilters      = reactive({ search: '', category: '', date_from: '', date_to: '' })
const expenseModal  = ref(false)
const editingExpense = ref(null)
const eSaving = ref(false)
const eError  = ref('')

const eForm = reactive({
  expense_date: '', category: 'misc', description: '',
  amount: 0, payment_method: 'cash', notes: '',
})

async function loadExpenses() {
  loadingE.value = true
  try {
    const { data } = await axios.get('/api/private-expenses', { params: eFilters })
    expensesList.value = data.data ?? data
  } finally { loadingE.value = false }
}

function openExpenseModal(e) {
  editingExpense.value = e
  eError.value = ''
  Object.assign(eForm, {
    expense_date:   e?.expense_date ?? new Date().toISOString().slice(0, 10),
    category:       e?.category ?? 'misc',
    description:    e?.description ?? '',
    amount:         e?.amount ?? 0,
    payment_method: e?.payment_method ?? 'cash',
    notes:          e?.notes ?? '',
  })
  expenseModal.value = true
}

async function saveExpense() {
  eSaving.value = true; eError.value = ''
  try {
    if (editingExpense.value) {
      await axios.put(`/api/private-expenses/${editingExpense.value.id}`, eForm)
    } else {
      await axios.post('/api/private-expenses', eForm)
    }
    expenseModal.value = false
    await Promise.all([loadExpenses(), loadCashbook()])
  } catch (e) {
    eError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { eSaving.value = false }
}

async function deleteExpense(e) {
  if (!confirm(`Delete expense "${e.description}"?`)) return
  await axios.delete(`/api/private-expenses/${e.id}`)
  await Promise.all([loadExpenses(), loadCashbook()])
}

// ── SHOP SETTINGS ─────────────────────────────────────
const shopSettings = ref({})
async function loadShopSettings() {
  try { const { data } = await axios.get('/api/shop-branding'); shopSettings.value = data } catch { /* non-critical */ }
}

// ── EXPORT / PRINT HELPERS ────────────────────────────
function openPrint(html) {
  const win = window.open('', '_blank', 'width=800,height=900')
  win.document.write(html)
  win.document.close()
  win.addEventListener('load', () => { win.focus(); win.print() })
}

function a5Css() {
  return `
    @media print { @page { size: A5; margin: 10mm 12mm; } }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 12px 16px; }
    .hdr { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; padding-bottom:10px; border-bottom:2px solid #1a1a1a; }
    .logo { max-height:52px; max-width:80px; object-fit:contain; }
    .shop-name { font-size:15px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:2px; }
    .shop-sub { font-size:10px; color:#555; line-height:1.5; }
    .meta-r { text-align:right; min-width:140px; }
    .inv-title { font-size:17px; font-weight:900; letter-spacing:2px; margin-bottom:6px; color:#1a1a1a; }
    .meta-table { font-size:10px; border-collapse:collapse; margin-left:auto; }
    .meta-table td { padding:1px 4px; }
    .meta-table td:first-child { color:#888; text-align:right; }
    .meta-table td:last-child { font-size:11px; text-align:left; }
    .cust { font-size:11px; background:#f9f9f9; border:1px solid #e5e7eb; padding:6px 10px; border-radius:4px; margin-bottom:10px; }
    table.items { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:10px; }
    table.items thead tr { background:#1a1a1a; color:#fff; }
    table.items th { padding:5px 6px; font-size:10px; font-weight:700; letter-spacing:0.3px; }
    table.items tbody tr { border-bottom:1px solid #e5e7eb; }
    table.items tbody tr:nth-child(even) { background:#fafafa; }
    table.items td { padding:5px 6px; vertical-align:top; }
    .totals { display:flex; justify-content:flex-end; margin-top:8px; }
    .totals-box { min-width:220px; }
    .tline { display:flex; justify-content:space-between; font-size:11px; padding:3px 0; border-bottom:1px dashed #e5e7eb; }
    .grand { display:flex; justify-content:space-between; font-size:11px; font-weight:800; border-top:2px solid #1a1a1a; border-bottom:2px solid #1a1a1a; padding:4px 0; margin:2px 0; }
    .footer { text-align:center; margin-top:16px; padding-top:10px; border-top:1px dashed #ccc; font-size:11px; }
    .sigs { display:flex; justify-content:space-between; margin-top:32px; }
    .sig { border-top:1px solid #374151; width:160px; text-align:center; padding-top:4px; font-size:10px; color:#6b7280; }
  `
}

function fmtLkr(val) {
  return 'LKR ' + Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// ── CSV EXPORT ────────────────────────────────────────
function exportCSV() {
  const headers = ['Date', 'Ref #', 'Type', 'Description', 'Cash In (LKR)', 'Cash Out (LKR)', 'Balance (LKR)', 'Payment Method']
  const kindLabel = (e) => e.kind === 'sale' ? 'Sale' : e.kind === 'purchase' ? 'Purchase' : e.kind === 'expense' ? 'Expense' : e.kind === 'custom_order' ? (e.sub_kind === 'advance' ? 'Custom Order Advance' : 'Custom Order Balance') : (e.type === 'add' ? 'Cash Added' : 'Cash Withdrawn')
  const rows = cashbookEntries.value.map(e => [
    e.entry_date, e.reference_number, kindLabel(e), e.description || '',
    e.cash_in > 0 ? Number(e.cash_in).toFixed(2) : '',
    e.cash_out > 0 ? Number(e.cash_out).toFixed(2) : '',
    Number(e.balance).toFixed(2),
    (e.payment_method || '').replace('_', ' '),
  ])
  const csv = [headers, ...rows]
    .map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(','))
    .join('\n')
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `private-cashbook-${new Date().toISOString().slice(0, 10)}.csv`
  document.body.appendChild(a); a.click(); document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

// ── CASHBOOK PDF PRINT ────────────────────────────────
function printCashbook() {
  const shop = shopSettings.value
  const from = cbFilters.date_from || 'All time'
  const to   = cbFilters.date_to   || 'All time'
  const today = new Date().toLocaleDateString('en-LK')
  const kindLabel = (e) => e.kind === 'sale' ? 'Sale' : e.kind === 'purchase' ? 'Purchase' : e.kind === 'expense' ? 'Expense' : e.kind === 'custom_order' ? (e.sub_kind === 'advance' ? 'Custom Order Advance' : 'Custom Order Balance') : (e.type === 'add' ? 'Cash Added' : 'Cash Withdrawn')
  const kindColor = (e) => e.kind === 'sale' ? '#15803d' : e.kind === 'purchase' ? '#dc2626' : e.kind === 'expense' ? '#c2410c' : e.kind === 'custom_order' ? '#b45309' : '#4338ca'
  const rowBg     = (e) => e.kind === 'sale' ? '#f0fdf4' : e.kind === 'purchase' ? '#fef2f2' : e.kind === 'expense' ? '#fff7ed' : e.kind === 'custom_order' ? '#fffbeb' : '#eef2ff'

  const tableRows = cashbookEntries.value.map(e => `
    <tr style="background:${rowBg(e)}">
      <td>${e.entry_date}</td>
      <td style="font-family:monospace;font-size:10px">${e.reference_number}</td>
      <td><span style="color:${kindColor(e)};font-weight:600">${kindLabel(e)}</span></td>
      <td>${e.description || '—'}</td>
      <td style="text-align:right;color:#15803d;font-weight:600">${e.cash_in > 0 ? fmtLkr(e.cash_in) : '—'}</td>
      <td style="text-align:right;color:#dc2626;font-weight:600">${e.cash_out > 0 ? fmtLkr(e.cash_out) : '—'}</td>
      <td style="text-align:right;font-weight:700;color:${e.balance >= 0 ? '#1d4ed8' : '#b45309'}">${fmtLkr(e.balance)}</td>
    </tr>`).join('')

  openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Private Cashbook</title>
  <style>
    body{font-family:Arial,sans-serif;font-size:11px;margin:0;padding:20px;color:#111}
    .hdr{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #1f2937;padding-bottom:12px;margin-bottom:16px}
    .shop{font-size:18px;font-weight:700}.meta{font-size:10px;color:#6b7280;margin-top:2px}
    .title{font-size:15px;font-weight:700;text-align:right}.ref{font-size:10px;color:#6b7280;text-align:right;margin-top:3px}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th{background:#1f2937;color:#fff;padding:6px 8px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.04em}
    td{padding:5px 8px;border-bottom:1px solid #e5e7eb}
    tfoot td{background:#f3f4f6;font-weight:700;border-top:2px solid #6b7280}
    .foot{margin-top:20px;font-size:9px;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:6px}
    @media print{@page{size:A4 landscape;margin:10mm}}
  </style></head><body>
  <div class="hdr">
    <div><div class="shop">${shop.shop_name || 'Private Gold Book'}</div>
      <div class="meta">${[shop.address, shop.phone].filter(Boolean).join(' · ')}</div></div>
    <div><div class="title">PRIVATE CASHBOOK</div>
      <div class="ref">Period: ${from} → ${to}</div>
      <div class="ref">Printed: ${today}</div></div>
  </div>
  <table>
    <thead><tr>
      <th>Date</th><th>Ref #</th><th>Type</th><th>Description</th>
      <th style="text-align:right;color:#86efac">Cash In</th>
      <th style="text-align:right;color:#fca5a5">Cash Out</th>
      <th style="text-align:right">Balance</th>
    </tr></thead>
    <tbody>${tableRows}</tbody>
    <tfoot><tr>
      <td colspan="4" style="font-weight:700;font-size:11px">TOTALS</td>
      <td style="text-align:right;color:#15803d">${fmtLkr(summary.total_in)}</td>
      <td style="text-align:right;color:#dc2626">${fmtLkr(summary.total_out)}</td>
      <td style="text-align:right;color:${summary.balance >= 0 ? '#1d4ed8' : '#b45309'}">${fmtLkr(summary.balance)}</td>
    </tr></tfoot>
  </table>
  <div class="foot">CONFIDENTIAL — Private record only. Not linked to official accounts.</div>
  </body></html>`)
}

// ── PURCHASE INVOICE PRINT ────────────────────────────
async function printPurchaseInvoice(p) {
  let shop = shopSettings.value
  if (!shop.shop_name) {
    try { const { data } = await axios.get('/api/shop-branding'); shop = data; shopSettings.value = data } catch { /* non-critical */ }
  }
  const today = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
  const purchaseDate = p.purchase_date
    ? new Date(p.purchase_date).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'
  const css = a5Css()
  openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Purchase — ${p.reference_number}</title>
  <style>${css}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${shop.logo_url ? `<img src="${shop.logo_url}" class="logo">` : ''}
      <div>
        <div class="shop-name">${shop.shop_name || ''}</div>
        ${shop.address ? `<div class="shop-sub" style="white-space:pre-line">${shop.address}</div>` : ''}
        ${shop.phone ? `<div class="shop-sub">Tel: ${shop.phone}</div>` : ''}
        ${shop.br_number ? `<div class="shop-sub">BR No: ${shop.br_number}</div>` : ''}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title" style="color:#dc2626">PURCHASE</div>
      <table class="meta-table">
        <tr><td>Ref No</td><td><strong>${p.reference_number}</strong></td></tr>
        <tr><td>Date</td><td>${purchaseDate}</td></tr>
        <tr><td>Printed</td><td>${today}</td></tr>
      </table>
    </div>
  </div>
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Description</th>
      <th style="text-align:center;width:55px">Karat</th>
      <th style="text-align:right;width:75px">Gross Wt</th>
      <th style="text-align:right;width:75px">Net Wt</th>
      <th style="text-align:right;width:110px">Amount</th>
    </tr></thead>
    <tbody>
      <tr>
        <td>
          <div style="font-weight:600">${p.description || '—'}</div>
          <div style="font-size:10px;color:#666;text-transform:capitalize">${(p.item_type || '').replace(/_/g, ' ')}</div>
          ${p.notes ? `<div style="font-size:10px;color:#888;margin-top:2px">Note: ${p.notes}</div>` : ''}
        </td>
        <td style="text-align:center;font-weight:700">${p.declared_karat}</td>
        <td style="text-align:right">${Number(p.gross_weight || 0).toFixed(3)} g</td>
        <td style="text-align:right;font-weight:700">${Number(p.net_weight || 0).toFixed(3)} g</td>
        <td style="text-align:right;font-weight:700">${fmtLkr(p.final_price)}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      ${Number(p.deduction_weight) > 0 ? `<div class="tline"><span>Deduction</span><span>${Number(p.deduction_weight).toFixed(3)} g</span></div>` : ''}
      <div class="grand"><span>TOTAL PAID</span><span style="color:#dc2626">${fmtLkr(p.final_price)}</span></div>
      <div class="tline"><span>Payment Method</span><span style="text-transform:capitalize">${(p.payment_method || '').replace('_', ' ')}</span></div>
    </div>
  </div>
  <div class="sigs">
    <div class="sig">Seller Signature</div>
    <div class="sig">Buyer / Recorder</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you!</div>
    ${shop.shop_name ? `<div style="font-size:10px;color:#888;margin-top:2px">${shop.shop_name}</div>` : ''}
  </div>
  </body></html>`)
}

// ── SALE INVOICE PRINT ────────────────────────────────
function printSaleInvoice(s) {
  const shop = shopSettings.value
  const today = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
  const saleDate = s.sale_date
    ? new Date(s.sale_date).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—'
  const css = a5Css()
  openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Sale — ${s.reference_number}</title>
  <style>${css}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${shop.logo_url ? `<img src="${shop.logo_url}" class="logo">` : ''}
      <div>
        <div class="shop-name">${shop.shop_name || ''}</div>
        ${shop.address ? `<div class="shop-sub" style="white-space:pre-line">${shop.address}</div>` : ''}
        ${shop.phone ? `<div class="shop-sub">Tel: ${shop.phone}</div>` : ''}
        ${shop.br_number ? `<div class="shop-sub">BR No: ${shop.br_number}</div>` : ''}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title" style="color:#16a34a">SALE RECEIPT</div>
      <table class="meta-table">
        <tr><td>Ref No</td><td><strong>${s.reference_number}</strong></td></tr>
        <tr><td>Date</td><td>${saleDate}</td></tr>
        <tr><td>Printed</td><td>${today}</td></tr>
      </table>
    </div>
  </div>
  ${(s.buyer_name || s.buyer_phone || s.buyer_address) ? `
  <div class="cust">
    ${s.buyer_name ? `<strong>${s.buyer_name}</strong>` : ''}
    ${s.buyer_phone ? `<span style="margin-left:8px;color:#555">${s.buyer_phone}</span>` : ''}
    ${s.buyer_address ? `<div style="font-size:10px;color:#666;margin-top:2px">${s.buyer_address}</div>` : ''}
  </div>` : ''}
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Description</th>
      <th style="text-align:center;width:55px">Karat</th>
      <th style="text-align:right;width:75px">Gross Wt</th>
      <th style="text-align:right;width:75px">Net Wt</th>
      <th style="text-align:right;width:110px">Amount</th>
    </tr></thead>
    <tbody>
      <tr>
        <td>
          <div style="font-weight:600">${s.description || '—'}</div>
          <div style="font-size:10px;color:#666;text-transform:capitalize">${(s.item_type || '').replace(/_/g, ' ')}</div>
          ${s.notes ? `<div style="font-size:10px;color:#888;margin-top:2px">Note: ${s.notes}</div>` : ''}
        </td>
        <td style="text-align:center;font-weight:700">${s.declared_karat}</td>
        <td style="text-align:right">${Number(s.gross_weight || 0).toFixed(3)} g</td>
        <td style="text-align:right;font-weight:700">${Number(s.net_weight || 0).toFixed(3)} g</td>
        <td style="text-align:right;font-weight:700">${fmtLkr(s.total_amount)}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      <div class="grand"><span>TOTAL RECEIVED</span><span style="color:#16a34a">${fmtLkr(s.total_amount)}</span></div>
      <div class="tline"><span>Payment Method</span><span style="text-transform:capitalize">${(s.payment_method || '').replace('_', ' ')}</span></div>
    </div>
  </div>
  <div class="sigs">
    <div class="sig">Seller / Recorder</div>
    <div class="sig">Buyer Signature</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you!</div>
    ${shop.shop_name ? `<div style="font-size:10px;color:#888;margin-top:2px">${shop.shop_name}</div>` : ''}
  </div>
  </body></html>`)
}

// ── PRIVATE SALE SMS ───────────────────────────────────
const privateSmsModal   = ref(false)
const privateSmsPhone   = ref('')
const privateSmsMessage = ref('')
const privateSmsError   = ref('')
const privateSmsLoading = ref(false)
const privateSmsSent    = ref(false)

function openPrivateSmsModal(s) {
  privateSmsError.value = ''
  privateSmsSent.value = false
  privateSmsLoading.value = false
  privateSmsPhone.value = s.buyer_phone || ''
  const shop = shopSettings.value
  const name = s.buyer_name || 'Valued Customer'
  const amount = Number(s.total_amount).toLocaleString('en-LK', { minimumFractionDigits: 2 })
  privateSmsMessage.value = `Dear ${name}, your sale receipt ${s.reference_number} of LKR ${amount} has been recorded. Thank you! — ${shop.shop_name || 'Lumac'} | www.lumac.lk | 0764643050`
  privateSmsModal.value = true
}

async function submitPrivateSms() {
  privateSmsLoading.value = true; privateSmsError.value = ''
  try {
    await axios.post('/api/sms/send-custom', {
      contacts: [privateSmsPhone.value.trim()],
      message:  privateSmsMessage.value,
    })
    privateSmsSent.value = true
  } catch (e) {
    privateSmsError.value = e.response?.data?.message ?? 'Failed to send SMS'
  } finally {
    privateSmsLoading.value = false
  }
}

// ── ADJUSTMENTS ────────────────────────────────────────
const adjModal  = ref(false)
const adjSaving = ref(false)
const adjError  = ref('')

const adjForm = reactive({
  adjustment_date: '', type: 'add', description: '',
  amount: 0, payment_method: 'cash', notes: '',
})

function openAdjModal(type) {
  adjError.value = ''
  Object.assign(adjForm, {
    adjustment_date: new Date().toISOString().slice(0, 10),
    type,
    description: '',
    amount: 0,
    payment_method: 'cash',
    notes: '',
  })
  adjModal.value = true
}

async function saveAdj() {
  adjSaving.value = true; adjError.value = ''
  try {
    await axios.post('/api/private-cash-adjustments', adjForm)
    adjModal.value = false
    await loadCashbook()
  } catch (e) {
    adjError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { adjSaving.value = false }
}

// ── init ───────────────────────────────────────────────
onMounted(() => Promise.all([loadShopSettings(), loadCashbook(), loadPurchases(), loadSales(), loadExpenses(), loadBuyers(), loadTodayRates()]))
</script>
