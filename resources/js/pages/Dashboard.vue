<template>
  <div class="space-y-6">
    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard v-for="s in stats" :key="s.label" v-bind="s" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Revenue chart -->
      <div class="lg:col-span-2 card">
        <h3 class="font-semibold text-gray-700 mb-4">Revenue — Last 30 Days</h3>
        <Line v-if="chartData" :data="chartData" :options="chartOptions" class="max-h-64" />
        <div v-else-if="!loaded" class="h-64 flex items-center justify-center text-gray-400">
          <div class="flex items-center gap-2"><span class="animate-spin inline-block w-4 h-4 border-2 border-gray-300 border-t-amber-500 rounded-full"></span> Loading…</div>
        </div>
        <div v-else class="h-64 flex flex-col items-center justify-center text-gray-400 gap-2">
          <svg class="w-10 h-10 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 17l6-6 4 4 8-8" /></svg>
          <span class="text-sm">No sales in the last 30 days</span>
        </div>
      </div>

      <!-- Top products -->
      <div class="card">
        <h3 class="font-semibold text-gray-700 mb-4">Top Categories (This Month)</h3>
        <ul class="space-y-3">
          <li v-for="(c, i) in data.top_categories" :key="c.id" class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-gold-100 text-gold-700 flex items-center justify-center text-xs font-bold">{{ i+1 }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ c.name }}</p>
              <p class="text-xs text-gray-400">{{ c.total_sold }} sold</p>
            </div>
          </li>
          <li v-if="!data.top_categories?.length" class="text-sm text-gray-400">No sales this month</li>
        </ul>
      </div>
    </div>

    <!-- Cheque Reminders -->
    <div v-if="chequeReminders.length" class="card border-l-4 border-l-amber-400 p-0 overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3 bg-amber-50 border-b border-amber-100">
        <div class="flex items-center gap-2">
          <span class="text-amber-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
          </span>
          <h3 class="font-semibold text-amber-800">Cheque Reminders</h3>
          <span class="ml-1 text-xs font-bold bg-amber-200 text-amber-800 rounded-full px-2 py-0.5">{{ chequeReminders.length }}</span>
        </div>
        <router-link to="/purchases" class="text-xs text-amber-600 hover:underline font-medium">View all →</router-link>
      </div>
      <div class="divide-y divide-gray-100">
        <div v-for="c in chequeReminders" :key="c.id"
          class="flex items-center justify-between px-5 py-3 hover:bg-gray-50"
          :class="chequeBg(c)">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
              :class="chequeIconBg(c)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-800">{{ c.purchase_number }}
                <span class="font-normal text-gray-500 ml-1">· {{ c.supplier?.name }}</span>
              </p>
              <p class="text-xs text-gray-500">Cheque #{{ c.cheque_number }} · {{ c.cheque_bank_name }}</p>
            </div>
          </div>
          <div class="text-right shrink-0 ml-4">
            <p class="text-sm font-bold text-gray-800">LKR {{ Number(c.total).toLocaleString() }}</p>
            <p class="text-xs font-semibold mt-0.5" :class="chequeDueColor(c)">{{ chequeDueLabel(c) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loan Payment Reminders -->
    <div v-if="loansDueSoon.length" class="card border-l-4 border-l-red-400 p-0 overflow-hidden">
      <div class="flex items-center justify-between px-5 py-3 bg-red-50 border-b border-red-100">
        <div class="flex items-center gap-2">
          <span class="text-red-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </span>
          <h3 class="font-semibold text-red-800">Loan Installments Due</h3>
          <span class="ml-1 text-xs font-bold bg-red-200 text-red-800 rounded-full px-2 py-0.5">{{ loansDueSoon.length }}</span>
        </div>
        <router-link to="/loans" class="text-xs text-red-600 hover:underline font-medium">View all →</router-link>
      </div>
      <div class="divide-y divide-gray-100">
        <div v-for="loan in loansDueSoon" :key="loan.id"
          class="flex items-center justify-between px-5 py-3 hover:bg-gray-50"
          :class="loanDueBg(loan)">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 bg-red-100 text-red-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-800">{{ loan.loan_number }}
                <span class="font-normal text-gray-500 ml-1">· {{ loan.lender_name }}</span>
              </p>
              <p class="text-xs text-gray-500">Installment: LKR {{ Number(loan.monthly_installment || 0).toLocaleString() }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0 ml-4">
            <div class="text-right">
              <p class="text-xs font-semibold" :class="loanDueColor(loan)">{{ loanDueLabel(loan) }}</p>
              <p class="text-xs text-gray-400">{{ loan.next_payment_date }}</p>
            </div>
            <button @click="openPayInstallment(loan)"
              class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-colors">
              Pay
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pay Installment Modal -->
    <div v-if="payInstallmentLoan" class="fixed inset-0 bg-black/40 z-50 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Pay Installment — {{ payInstallmentLoan.loan_number }}</h3>
        <p class="text-sm text-gray-500">{{ payInstallmentLoan.lender_name }} · Due: {{ payInstallmentLoan.next_payment_date }}</p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Payment Date *</label>
            <input v-model="installmentForm.payment_date" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Paid From Account *</label>
            <select v-model="installmentForm.paid_from_account_id" class="form-input">
              <option value="">Select account</option>
              <option v-for="a in installmentAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Principal (LKR)</label>
            <input v-model.number="installmentForm.principal_amount" type="number" min="0" step="0.01" class="form-input" />
          </div>
          <div>
            <label class="form-label">Interest (LKR)</label>
            <input v-model.number="installmentForm.interest_amount" type="number" min="0" step="0.01" class="form-input" />
          </div>
        </div>
        <p v-if="installmentError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ installmentError }}</p>
        <div class="flex justify-end gap-2">
          <button @click="payInstallmentLoan = null" class="btn-secondary">Cancel</button>
          <button @click="submitInstallment" :disabled="installmentSaving" class="btn-primary">
            {{ installmentSaving ? 'Posting…' : 'Post Payment & GL' }}
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Cash on Hand (GL) -->
      <div class="card">
        <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-green-500"></span>
          Cash &amp; Bank Balances
          <span class="ml-auto text-xs text-gray-400 font-normal">As of today</span>
        </h3>
        <div v-if="glCashLoading" class="flex items-center justify-center py-8 text-gray-400 gap-2">
          <span class="animate-spin inline-block w-4 h-4 border-2 border-gray-300 border-t-amber-500 rounded-full"></span> Loading…
        </div>
        <div v-else-if="glCashAccounts.length" class="space-y-3">
          <div v-for="acc in glCashAccounts" :key="acc.id"
            class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ acc.name }}</p>
              <p class="text-xs text-gray-400 font-mono">{{ acc.code }}</p>
            </div>
            <p class="text-sm font-bold" :class="acc.balance >= 0 ? 'text-green-700' : 'text-red-600'">
              LKR {{ lkr(Math.abs(acc.balance)) }}
            </p>
          </div>
          <div class="flex items-center justify-between pt-2 border-t-2 border-gray-200">
            <p class="text-sm font-semibold text-gray-700">Total Cash &amp; Bank</p>
            <p class="text-base font-bold text-green-700">LKR {{ lkr(glCashTotal) }}</p>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 text-center py-8">No cash or bank accounts found</p>
      </div>

      <!-- Recent sales -->
      <div class="card">
        <h3 class="font-semibold text-gray-700 mb-4">Recent Sales</h3>
        <div class="space-y-3">
          <div v-for="sale in data.recent_sales" :key="sale.id"
            class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ sale.invoice_number }}</p>
              <p class="text-xs text-gray-400">{{ sale.customer?.name ?? 'Walk-in' }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-gray-800">LKR {{ Number(sale.total).toLocaleString() }}</p>
              <span :class="statusClass(sale.payment_status)" class="badge">{{ sale.payment_status }}</span>
            </div>
          </div>
          <p v-if="!data.recent_sales?.length" class="text-sm text-gray-400 text-center">No sales yet</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  PointElement, LineElement, Title, Tooltip, Legend, Filler,
} from 'chart.js'
import StatCard from '@/components/StatCard.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const data = ref({})
const loaded = ref(false)

const stats = computed(() => [
  { label: 'Total Products',    value: data.value.totals?.products        ?? '—', color: 'blue',   icon: '📦' },
  { label: 'Customers',         value: data.value.totals?.customers       ?? '—', color: 'purple', icon: '👥' },
  { label: "Today's Revenue",   value: 'LKR ' + Number(data.value.totals?.revenue_today   ?? 0).toLocaleString(), color: 'gold',  icon: '💰' },
  { label: 'Cash on Hand',      value: glCashLoading.value ? '…' : 'LKR ' + lkr(glCashOnHand.value), color: 'green',  icon: '💵' },
])

const chartData = computed(() => {
  const sales = data.value.sales_chart
  if (!sales?.length) return null
  return {
    labels: sales.map(s => s.date),
    datasets: [{
      label: 'Revenue',
      data: sales.map(s => s.revenue),
      borderColor: '#d97706',
      backgroundColor: 'rgba(217,119,6,0.1)',
      fill: true,
      tension: 0.4,
    }],
  }
})

const chartOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true } },
}

const chequeReminders = computed(() => data.value.cheque_reminders ?? [])
const loansDueSoon   = computed(() => data.value.loan_due_soon ?? [])

// ── GL Cash on Hand ──────────────────────────────────────────────────────────
const glCashLoading  = ref(false)
const glCashAccounts = ref([])
const glCashTotal    = computed(() => glCashAccounts.value.reduce((s, a) => s + Number(a.balance), 0))
const glCashOnHand   = computed(() => { const a = glCashAccounts.value.find(a => a.code === '1000'); return a ? Number(a.balance) : 0 })

function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }

// ── Pay Installment ──────────────────────────────────────────────────────────
const payInstallmentLoan  = ref(null)
const installmentAccounts = ref([])
const installmentSaving   = ref(false)
const installmentError    = ref('')
const installmentForm     = ref({ payment_date: '', principal_amount: 0, interest_amount: 0, paid_from_account_id: '' })

async function openPayInstallment(loan) {
  payInstallmentLoan.value = loan
  installmentError.value   = ''
  installmentForm.value    = {
    payment_date:        new Date().toISOString().slice(0, 10),
    principal_amount:    Number(loan.monthly_installment || 0),
    interest_amount:     0,
    paid_from_account_id: '',
  }
  if (!installmentAccounts.value.length) {
    const { data: accs } = await axios.get('/api/accounts/all')
    installmentAccounts.value = accs.filter(a => a.type === 'asset')
  }
}

async function submitInstallment() {
  installmentError.value = ''
  if (!installmentForm.value.paid_from_account_id) {
    installmentError.value = 'Please select an account to pay from.'
    return
  }
  installmentSaving.value = true
  try {
    await axios.post(`/api/loans/${payInstallmentLoan.value.id}/repay`, installmentForm.value)
    payInstallmentLoan.value = null
    const { data: d } = await axios.get('/api/dashboard')
    data.value = d
  } catch (e) {
    installmentError.value = e.response?.data?.message ?? 'Failed to post payment.'
  } finally {
    installmentSaving.value = false
  }
}

function loanDueDays(loan) {
  const today = new Date(); today.setHours(0, 0, 0, 0)
  const due   = new Date(loan.next_payment_date); due.setHours(0, 0, 0, 0)
  return Math.round((due - today) / 86400000)
}
function loanDueLabel(loan) {
  const d = loanDueDays(loan)
  if (d < 0)   return `Overdue by ${Math.abs(d)} day${Math.abs(d) > 1 ? 's' : ''}`
  if (d === 0) return 'Due today!'
  if (d === 1) return 'Due tomorrow'
  return `Due in ${d} days`
}
function loanDueColor(loan) {
  const d = loanDueDays(loan)
  if (d < 0)  return 'text-red-600'
  if (d <= 1) return 'text-red-500'
  return 'text-amber-600'
}
function loanDueBg(loan) {
  const d = loanDueDays(loan)
  if (d < 0)  return 'bg-red-50'
  if (d <= 1) return 'bg-amber-50'
  return ''
}

function chequeDays(c) {
  const today = new Date(); today.setHours(0,0,0,0)
  const due   = new Date(c.cheque_date); due.setHours(0,0,0,0)
  return Math.round((due - today) / 86400000)
}
function chequeDueLabel(c) {
  const d = chequeDays(c)
  if (d < 0)  return `Overdue by ${Math.abs(d)} day${Math.abs(d)>1?'s':''}`
  if (d === 0) return 'Due today!'
  if (d === 1) return 'Due tomorrow'
  return `Due in ${d} days  (${c.cheque_date})`
}
function chequeDueColor(c) {
  const d = chequeDays(c)
  if (d < 0)  return 'text-red-600'
  if (d <= 1) return 'text-red-500'
  if (d <= 3) return 'text-amber-600'
  return 'text-gray-500'
}
function chequeBg(c) {
  const d = chequeDays(c)
  if (d < 0)  return 'bg-red-50'
  if (d <= 1) return 'bg-amber-50'
  return ''
}
function chequeIconBg(c) {
  const d = chequeDays(c)
  if (d < 0)  return 'bg-red-100 text-red-600'
  if (d <= 1) return 'bg-amber-100 text-amber-600'
  return 'bg-gray-100 text-gray-500'
}

function statusClass(s) {
  return {
    paid:     'bg-green-100 text-green-700',
    pending:  'bg-yellow-100 text-yellow-700',
    partial:  'bg-blue-100 text-blue-700',
    refunded: 'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

onMounted(async () => {
  try {
    const { data: d } = await axios.get('/api/dashboard')
    data.value = d
  } finally {
    loaded.value = true
  }

  glCashLoading.value = true
  try {
    const today = new Date().toISOString().slice(0, 10)
    const { data: bs } = await axios.get('/api/gl/balance-sheet', { params: { as_of: today } })
    glCashAccounts.value = (bs.assets ?? []).filter(a => /(cash|bank)/i.test(a.name))
  } finally {
    glCashLoading.value = false
  }
})
</script>
