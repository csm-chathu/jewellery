<template>
  <div class="space-y-5">
    <!-- Header + Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">General Ledger</h2>
        <p class="text-sm text-gray-500 mt-0.5">Trial balance · Balance sheet · Income statement · Account ledger</p>
      </div>
      <div class="flex gap-1 flex-wrap">
        <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key; loadTab()"
          :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border',
            activeTab === t.key ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']">
          {{ t.label }}
        </button>
      </div>
    </div>

    <!-- Date range controls (shared) -->
    <div class="card py-3 flex flex-wrap items-center gap-4" v-if="activeTab !== 'balance-sheet'">
      <div class="flex items-center gap-2">
        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">From</label>
        <input v-model="dateFrom" type="date" class="form-input w-36" @change="loadTab" />
      </div>
      <div class="flex items-center gap-2">
        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">To</label>
        <input v-model="dateTo" type="date" class="form-input w-36" @change="loadTab" />
      </div>
      <button @click="resetDates" class="text-xs text-gray-400 hover:text-gray-600 underline">Reset to YTD</button>
    </div>

    <!-- Balance sheet as-of control -->
    <div class="card py-3 flex items-center gap-4" v-if="activeTab === 'balance-sheet'">
      <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">As of</label>
      <input v-model="asOf" type="date" class="form-input w-36" @change="loadTab" />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card text-center py-16 text-gray-400">
      <div class="flex items-center justify-center gap-2">
        <ArrowPathIcon class="w-5 h-5 animate-spin" /> Loading…
      </div>
    </div>

    <!-- ── Trial Balance ── -->
    <div v-else-if="activeTab === 'trial-balance' && trialData" class="space-y-4">
      <div class="grid grid-cols-3 gap-4">
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Debits</p>
          <p class="text-xl font-bold text-gray-800 mt-1">LKR {{ lkr(trialData.totals.total_debit) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Credits</p>
          <p class="text-xl font-bold text-gray-800 mt-1">LKR {{ lkr(trialData.totals.total_credit) }}</p>
        </div>
        <div class="card text-center" :class="isTrialBalanced ? 'bg-green-50' : 'bg-red-50'">
          <p class="text-xs uppercase tracking-wide" :class="isTrialBalanced ? 'text-green-600' : 'text-red-600'">Status</p>
          <p class="text-lg font-bold mt-1" :class="isTrialBalanced ? 'text-green-700' : 'text-red-700'">
            {{ isTrialBalanced ? '✓ Balanced' : '⚠ Out of Balance' }}
          </p>
        </div>
      </div>

      <div class="card p-0 overflow-hidden">
        <div class="px-5 py-3 border-b bg-gray-50">
          <span class="font-semibold text-gray-700 text-sm">Trial Balance — {{ trialData.from }} to {{ trialData.to }}</span>
        </div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th w-20">Code</th>
              <th class="table-th">Account Name</th>
              <th class="table-th w-28 text-center">Type</th>
              <th class="table-th w-36 text-right">Debit (LKR)</th>
              <th class="table-th w-36 text-right">Credit (LKR)</th>
              <th class="table-th w-36 text-right">Balance (LKR)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in trialData.rows" :key="row.id" class="hover:bg-gray-50">
              <td class="table-td font-mono text-xs text-gray-500">{{ row.code }}</td>
              <td class="table-td font-medium">{{ row.name }}</td>
              <td class="table-td text-center">
                <span :class="typeClass(row.type)" class="badge text-xs">{{ row.type }}</span>
              </td>
              <td class="table-td text-right font-mono">{{ lkr(row.total_debit) }}</td>
              <td class="table-td text-right font-mono">{{ lkr(row.total_credit) }}</td>
              <td class="table-td text-right font-semibold font-mono" :class="row.balance >= 0 ? 'text-gray-800' : 'text-red-600'">
                {{ row.balance < 0 ? '(' + lkr(Math.abs(row.balance)) + ')' : lkr(row.balance) }}
              </td>
            </tr>
          </tbody>
          <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
            <tr>
              <td class="table-td" colspan="3">Totals</td>
              <td class="table-td text-right font-mono">{{ lkr(trialData.totals.total_debit) }}</td>
              <td class="table-td text-right font-mono">{{ lkr(trialData.totals.total_credit) }}</td>
              <td class="table-td"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- ── Balance Sheet ── -->
    <div v-else-if="activeTab === 'balance-sheet' && bsData" class="space-y-4">
      <!-- Check -->
      <div class="grid grid-cols-3 gap-4">
        <div class="card text-center bg-blue-50">
          <p class="text-xs text-blue-600 uppercase tracking-wide">Total Assets</p>
          <p class="text-2xl font-bold text-blue-800 mt-1">LKR {{ lkr(bsData.totals.total_assets) }}</p>
        </div>
        <div class="card text-center bg-red-50">
          <p class="text-xs text-red-600 uppercase tracking-wide">Total Liabilities</p>
          <p class="text-2xl font-bold text-red-800 mt-1">LKR {{ lkr(bsData.totals.total_liabilities) }}</p>
        </div>
        <div class="card text-center bg-purple-50">
          <p class="text-xs text-purple-600 uppercase tracking-wide">Total Equity</p>
          <p class="text-2xl font-bold text-purple-800 mt-1">LKR {{ lkr(bsData.totals.total_equity) }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Assets -->
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-blue-50 text-blue-800 font-semibold text-sm flex items-center gap-2">
            <BanknotesIcon class="w-4 h-4" /> Assets
          </div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in bsData.assets" :key="row.id" class="hover:bg-gray-50">
                <td class="table-td font-mono text-xs text-gray-400 w-20">{{ row.code }}</td>
                <td class="table-td">{{ row.name }}</td>
                <td class="table-td text-xs text-gray-400 w-28 capitalize">{{ row.sub_type?.replace(/_/g,' ') }}</td>
                <td class="table-td text-right font-semibold w-32">{{ lkr(row.balance) }}</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-blue-200 bg-blue-50 font-bold text-blue-800">
              <tr>
                <td class="table-td" colspan="3">Total Assets</td>
                <td class="table-td text-right">{{ lkr(bsData.totals.total_assets) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Liabilities + Equity -->
        <div class="space-y-4">
          <div class="card p-0 overflow-hidden">
            <div class="px-5 py-3 border-b bg-red-50 text-red-800 font-semibold text-sm flex items-center gap-2">
              <ScaleIcon class="w-4 h-4" /> Liabilities
            </div>
            <table class="w-full text-sm">
              <tbody class="divide-y divide-gray-100">
                <tr v-for="row in bsData.liabilities" :key="row.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs text-gray-400 w-20">{{ row.code }}</td>
                  <td class="table-td">{{ row.name }}</td>
                  <td class="table-td text-right font-semibold w-32">{{ lkr(row.balance) }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t-2 border-red-200 bg-red-50 font-bold text-red-800">
                <tr>
                  <td class="table-td" colspan="2">Total Liabilities</td>
                  <td class="table-td text-right">{{ lkr(bsData.totals.total_liabilities) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div class="card p-0 overflow-hidden">
            <div class="px-5 py-3 border-b bg-purple-50 text-purple-800 font-semibold text-sm flex items-center gap-2">
              <BuildingLibraryIcon class="w-4 h-4" /> Equity
            </div>
            <table class="w-full text-sm">
              <tbody class="divide-y divide-gray-100">
                <tr v-for="row in bsData.equity" :key="row.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs text-gray-400 w-20">{{ row.code }}</td>
                  <td class="table-td">{{ row.name }}</td>
                  <td class="table-td text-right font-semibold w-32">{{ lkr(row.balance) }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t-2 border-purple-200 bg-purple-50 font-bold text-purple-800">
                <tr>
                  <td class="table-td" colspan="2">Total Equity</td>
                  <td class="table-td text-right">{{ lkr(bsData.totals.total_equity) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div class="card py-3 px-5 flex items-center justify-between text-sm font-semibold bg-gray-50 border-2"
            :class="bsCheck ? 'border-green-300 text-green-800' : 'border-red-300 text-red-800'">
            <span>Liabilities + Equity = Assets?</span>
            <span>{{ bsCheck ? '✓ ' : '⚠ ' }} LKR {{ lkr(bsData.totals.total_liabilities + bsData.totals.total_equity) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Income Statement ── -->
    <div v-else-if="activeTab === 'income-statement' && isData" class="space-y-4">
      <div class="grid grid-cols-3 gap-4">
        <div class="card text-center bg-green-50">
          <p class="text-xs text-green-600 uppercase tracking-wide">Total Revenue</p>
          <p class="text-2xl font-bold text-green-800 mt-1">LKR {{ lkr(isData.total_revenue) }}</p>
        </div>
        <div class="card text-center bg-amber-50">
          <p class="text-xs text-amber-600 uppercase tracking-wide">Total Expenses</p>
          <p class="text-2xl font-bold text-amber-800 mt-1">LKR {{ lkr(isData.total_expenses) }}</p>
        </div>
        <div class="card text-center" :class="isData.net_income >= 0 ? 'bg-emerald-50' : 'bg-red-50'">
          <p class="text-xs uppercase tracking-wide" :class="isData.net_income >= 0 ? 'text-emerald-600' : 'text-red-600'">Net Income</p>
          <p class="text-2xl font-bold mt-1" :class="isData.net_income >= 0 ? 'text-emerald-800' : 'text-red-700'">
            {{ isData.net_income < 0 ? '(' : '' }}LKR {{ lkr(Math.abs(isData.net_income)) }}{{ isData.net_income < 0 ? ')' : '' }}
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-green-50 text-green-800 font-semibold text-sm flex items-center gap-2">
            <ArrowTrendingUpIcon class="w-4 h-4" /> Revenue
          </div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in isData.revenues" :key="row.id" class="hover:bg-gray-50">
                <td class="table-td font-mono text-xs text-gray-400 w-20">{{ row.code }}</td>
                <td class="table-td">{{ row.name }}</td>
                <td class="table-td text-right font-semibold w-36 text-green-700">{{ lkr(row.balance) }}</td>
              </tr>
              <tr v-if="!isData.revenues.length">
                <td colspan="3" class="table-td text-center text-gray-400 py-4">No revenue recorded</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-green-200 bg-green-50 font-bold text-green-800">
              <tr>
                <td class="table-td" colspan="2">Total Revenue</td>
                <td class="table-td text-right">{{ lkr(isData.total_revenue) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-amber-50 text-amber-800 font-semibold text-sm flex items-center gap-2">
            <ReceiptPercentIcon class="w-4 h-4" /> Expenses
          </div>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in isData.expenses" :key="row.id" class="hover:bg-gray-50">
                <td class="table-td font-mono text-xs text-gray-400 w-20">{{ row.code }}</td>
                <td class="table-td">{{ row.name }}</td>
                <td class="table-td text-xs text-gray-400 w-20 capitalize">{{ row.sub_type }}</td>
                <td class="table-td text-right font-semibold w-36 text-amber-800">{{ lkr(row.balance) }}</td>
              </tr>
              <tr v-if="!isData.expenses.length">
                <td colspan="4" class="table-td text-center text-gray-400 py-4">No expenses recorded</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-amber-200 bg-amber-50 font-bold text-amber-800">
              <tr>
                <td class="table-td" colspan="3">Total Expenses</td>
                <td class="table-td text-right">{{ lkr(isData.total_expenses) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Account Ledger ── -->
    <div v-else-if="activeTab === 'ledger'" class="space-y-4">
      <div class="flex items-center gap-4">
        <div class="flex-1 max-w-sm">
          <label class="form-label">Select Account</label>
          <select v-model="ledgerAccountId" class="form-input" @change="loadTab">
            <option value="">— Choose an account —</option>
            <optgroup v-for="grp in accountGroups" :key="grp.type" :label="grp.label">
              <option v-for="a in grp.items" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
            </optgroup>
          </select>
        </div>
      </div>

      <div v-if="ledgerData" class="space-y-4">
        <!-- Summary -->
        <div class="card flex items-center gap-8 py-3">
          <div>
            <p class="text-xs text-gray-500 uppercase">Account</p>
            <p class="font-semibold text-gray-800">{{ ledgerData.account.code }} – {{ ledgerData.account.name }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">Type</p>
            <span :class="typeClass(ledgerData.account.type)" class="badge">{{ ledgerData.account.type }}</span>
          </div>
          <div class="ml-auto text-right">
            <p class="text-xs text-gray-500 uppercase">Closing Balance</p>
            <p class="text-xl font-bold" :class="ledgerData.closing_balance >= 0 ? 'text-gray-800' : 'text-red-600'">
              LKR {{ lkr(Math.abs(ledgerData.closing_balance)) }}
              <span class="text-xs font-normal ml-1">{{ ledgerData.closing_balance >= 0 ? 'Dr' : 'Cr' }}</span>
            </p>
          </div>
        </div>

        <div class="card p-0 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th w-24">Date</th>
                <th class="table-th w-28">Entry #</th>
                <th class="table-th">Description</th>
                <th class="table-th w-32 text-right">Debit</th>
                <th class="table-th w-32 text-right">Credit</th>
                <th class="table-th w-36 text-right">Balance</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="line in ledgerData.lines" :key="line.entry_id + '-' + line.entry_number" class="hover:bg-gray-50">
                <td class="table-td text-xs text-gray-500">{{ fmtDate(line.entry_date) }}</td>
                <td class="table-td font-mono text-xs text-gray-600">{{ line.entry_number }}</td>
                <td class="table-td text-gray-700">
                  {{ line.line_description || line.entry_description }}
                </td>
                <td class="table-td text-right font-mono" :class="line.debit > 0 ? 'text-gray-800' : 'text-gray-300'">
                  {{ line.debit > 0 ? lkr(line.debit) : '—' }}
                </td>
                <td class="table-td text-right font-mono" :class="line.credit > 0 ? 'text-gray-800' : 'text-gray-300'">
                  {{ line.credit > 0 ? lkr(line.credit) : '—' }}
                </td>
                <td class="table-td text-right font-semibold font-mono" :class="line.running_balance >= 0 ? 'text-gray-800' : 'text-red-600'">
                  {{ lkr(Math.abs(line.running_balance)) }}
                  <span class="text-xs font-normal ml-0.5">{{ line.running_balance >= 0 ? 'Dr' : 'Cr' }}</span>
                </td>
              </tr>
              <tr v-if="!ledgerData.lines?.length">
                <td colspan="6" class="table-td text-center text-gray-400 py-8">No transactions for this account in the selected period</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="!loading" class="card text-center py-16 text-gray-400">
        Select an account to view its ledger
      </div>
    </div>

    <!-- No data -->
    <div v-else-if="!loading" class="card text-center py-16 text-gray-400">
      No data available for the selected period
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import {
  ArrowPathIcon, BanknotesIcon, ScaleIcon, BuildingLibraryIcon,
  ArrowTrendingUpIcon, ReceiptPercentIcon,
} from '@heroicons/vue/24/outline'

const activeTab      = ref('trial-balance')
const loading        = ref(false)
const dateFrom       = ref(new Date().getFullYear() + '-01-01')
const dateTo         = ref(new Date().toISOString().slice(0, 10))
const asOf           = ref(new Date().toISOString().slice(0, 10))
const ledgerAccountId = ref('')
const allAccounts    = ref([])

const trialData = ref(null)
const bsData    = ref(null)
const isData    = ref(null)
const ledgerData = ref(null)

const tabs = [
  { key: 'trial-balance',     label: 'Trial Balance' },
  { key: 'balance-sheet',     label: 'Balance Sheet' },
  { key: 'income-statement',  label: 'Income Statement' },
  { key: 'ledger',            label: 'Account Ledger' },
]

const typeOrder = ['asset', 'liability', 'equity', 'revenue', 'expense']
const typeLabel = { asset: 'Assets', liability: 'Liabilities', equity: 'Equity', revenue: 'Revenue', expense: 'Expenses' }

const accountGroups = computed(() =>
  typeOrder.map(type => ({
    type,
    label: typeLabel[type],
    items: allAccounts.value.filter(a => a.type === type),
  })).filter(g => g.items.length)
)

const isTrialBalanced = computed(() =>
  trialData.value && Math.abs(trialData.value.totals.total_debit - trialData.value.totals.total_credit) < 0.01
)

const bsCheck = computed(() =>
  bsData.value && Math.abs(bsData.value.totals.total_assets - (bsData.value.totals.total_liabilities + bsData.value.totals.total_equity)) < 0.01
)

function typeClass(t) {
  return {
    asset:     'bg-blue-100 text-blue-700',
    liability: 'bg-red-100 text-red-700',
    equity:    'bg-purple-100 text-purple-700',
    revenue:   'bg-green-100 text-green-700',
    expense:   'bg-amber-100 text-amber-700',
  }[t] ?? 'bg-gray-100 text-gray-600'
}

async function loadTab() {
  loading.value = true
  try {
    if (activeTab.value === 'trial-balance') {
      const { data } = await axios.get('/api/gl/trial-balance', { params: { from: dateFrom.value, to: dateTo.value } })
      trialData.value = data
    } else if (activeTab.value === 'balance-sheet') {
      const { data } = await axios.get('/api/gl/balance-sheet', { params: { as_of: asOf.value } })
      bsData.value = data
    } else if (activeTab.value === 'income-statement') {
      const { data } = await axios.get('/api/gl/income-statement', { params: { from: dateFrom.value, to: dateTo.value } })
      isData.value = data
    } else if (activeTab.value === 'ledger' && ledgerAccountId.value) {
      const { data } = await axios.get(`/api/gl/ledger/${ledgerAccountId.value}`, { params: { from: dateFrom.value, to: dateTo.value } })
      ledgerData.value = data
    }
  } finally {
    loading.value = false
  }
}

function resetDates() {
  dateFrom.value = new Date().getFullYear() + '-01-01'
  dateTo.value   = new Date().toISOString().slice(0, 10)
  loadTab()
}

function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-GB') : '—' }

onMounted(async () => {
  const { data } = await axios.get('/api/accounts/all')
  allAccounts.value = data
  loadTab()
})
</script>
