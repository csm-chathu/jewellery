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
        <ReportTable :headers="['Invoice','Customer','Date','Total','Paid','Discount','Tax','Method','Type']"
          :rows="data.rows.map(r => [r.invoice_number, r.customer?.name ?? '—', fmt(r.created_at),
            lkr(r.total), lkr(r.amount_paid), lkr(r.discount), lkr(r.tax), r.payment_method, r.sale_type])" />
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
              <span class="text-sm text-gray-700 capitalize">{{ cat.category.replace('_',' ') }}</span>
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
  { key: 'metal',      label: 'Metal Balance',    icon: '⚖️', hasDateFilter: false, endpoint: '/api/reports/metal-balance' },
  { key: 'pnl',        label: 'Rate P&L',         icon: '📊', hasDateFilter: false, endpoint: '/api/reports/rate-pnl' },
  { key: 'tax',        label: 'Tax Settings',     icon: '🧾', hasDateFilter: false, endpoint: null },
]

// ── State ───────────────────────────────────────────────────────────────────

const active  = ref('sales')
const data    = ref(null)
const loading = ref(false)

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
