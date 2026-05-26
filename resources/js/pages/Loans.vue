<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Business Loans</h2>
        <p class="text-sm text-gray-500 mt-0.5">Track loans, outstanding balances, and repayments with automatic GL posting</p>
      </div>
      <button @click="showCreate = true" class="btn-primary">New Loan</button>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Loan #</th>
            <th class="table-th">Lender</th>
            <th class="table-th">Start</th>
            <th class="table-th">Next Payment</th>
            <th class="table-th text-right">Principal</th>
            <th class="table-th text-right">Outstanding</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="loan in loans.data" :key="loan.id">
            <td class="table-td font-mono text-xs">{{ loan.loan_number }}</td>
            <td class="table-td">{{ loan.lender_name }}</td>
            <td class="table-td text-xs text-gray-500">{{ fmtDate(loan.start_date) }}</td>
            <td class="table-td text-xs">
              <span v-if="loan.next_payment_date"
                :class="isDueSoon(loan.next_payment_date) ? 'text-red-600 font-semibold' : 'text-gray-500'">
                {{ fmtDate(loan.next_payment_date) }}
              </span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="table-td text-right">{{ lkr(loan.principal_amount) }}</td>
            <td class="table-td text-right font-semibold" :class="loan.outstanding_balance > 0 ? 'text-red-700' : 'text-green-700'">
              {{ lkr(loan.outstanding_balance) }}
            </td>
            <td class="table-td">
              <span class="badge text-xs" :class="loan.status === 'active' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'">
                {{ loan.status }}
              </span>
            </td>
            <td class="table-td flex gap-1.5">
              <button @click="openRepay(loan)" class="px-2.5 py-1 rounded bg-blue-100 text-blue-700 text-xs font-medium">Repay</button>
              <button @click="openHistory(loan)" class="px-2.5 py-1 rounded bg-gray-100 text-gray-600 text-xs font-medium">History</button>
            </td>
          </tr>
          <tr v-if="!loans.data?.length"><td colspan="7" class="table-td text-center text-gray-400 py-8">No loans yet</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Repayment History Panel -->
    <div v-if="historyLoan" class="card p-0 overflow-hidden border-t-4 border-t-blue-400">
      <div class="flex items-center justify-between px-5 py-3 bg-blue-50 border-b border-blue-100">
        <div>
          <h3 class="font-semibold text-blue-800">Repayment History — {{ historyLoan.loan_number }}</h3>
          <p class="text-xs text-blue-600 mt-0.5">{{ historyLoan.lender_name }} · Outstanding: LKR {{ lkr(historyLoan.outstanding_balance) }}</p>
        </div>
        <button @click="historyLoan = null" class="text-blue-400 hover:text-blue-600 text-lg font-bold">✕</button>
      </div>
      <div v-if="historyLoading" class="py-8 text-center text-gray-400 text-sm">Loading…</div>
      <div v-else-if="!historyRepayments.length" class="py-8 text-center text-gray-400 text-sm">No repayments recorded yet.</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Payment #</th>
            <th class="table-th">Date</th>
            <th class="table-th text-right">Principal</th>
            <th class="table-th text-right">Interest</th>
            <th class="table-th text-right">Total Paid</th>
            <th class="table-th">Paid From</th>
            <th class="table-th">Notes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in historyRepayments" :key="r.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs">{{ r.payment_number }}</td>
            <td class="table-td whitespace-nowrap">{{ fmtDate(r.payment_date) }}</td>
            <td class="table-td text-right font-mono">{{ lkr(r.principal_amount) }}</td>
            <td class="table-td text-right font-mono text-red-600">{{ lkr(r.interest_amount) }}</td>
            <td class="table-td text-right font-mono font-semibold">{{ lkr(r.total_amount) }}</td>
            <td class="table-td text-xs text-gray-500">{{ r.paid_from_account?.name ?? '—' }}</td>
            <td class="table-td text-xs text-gray-400">{{ r.notes ?? '—' }}</td>
          </tr>
          <tr class="bg-gray-50 font-semibold text-sm">
            <td colspan="2" class="table-td text-right text-gray-500">Totals</td>
            <td class="table-td text-right">{{ lkr(historyRepayments.reduce((s, r) => s + r.principal_amount, 0)) }}</td>
            <td class="table-td text-right text-red-600">{{ lkr(historyRepayments.reduce((s, r) => s + r.interest_amount, 0)) }}</td>
            <td class="table-td text-right">{{ lkr(historyRepayments.reduce((s, r) => s + r.total_amount, 0)) }}</td>
            <td colspan="2" class="table-td"></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 z-50 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Create Loan</h3>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="form-label">Lender *</label><input v-model="createForm.lender_name" class="form-input" /></div>
          <div><label class="form-label">Original Principal *</label><input v-model.number="createForm.principal_amount" type="number" min="0" step="0.01" class="form-input" /></div>
          <div><label class="form-label">Start Date *</label><input v-model="createForm.start_date" type="date" class="form-input" /></div>
          <div><label class="form-label">Due Date</label><input v-model="createForm.due_date" type="date" class="form-input" /></div>
          <div><label class="form-label">Next Payment Date</label><input v-model="createForm.next_payment_date" type="date" class="form-input" /></div>
          <div><label class="form-label">Monthly Installment</label><input v-model.number="createForm.monthly_installment" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" /></div>
          <div><label class="form-label">Outstanding Balance *</label>
            <input v-model.number="createForm.outstanding_balance" type="number" min="0" step="0.01" class="form-input" placeholder="Remaining amount owed" />
            <p class="text-xs text-gray-400 mt-0.5">For new loans = principal. For existing loans = remaining balance.</p>
          </div>
          <div><label class="form-label">Liability Account *</label>
            <select v-model="createForm.liability_account_id" class="form-input">
              <option value="">Select</option>
              <option v-for="a in liabilityAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
          <div><label class="form-label">Received To Account</label>
            <select v-model="createForm.received_to_account_id" class="form-input">
              <option value="">Select (leave blank for existing loans)</option>
              <option v-for="a in assetAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
        </div>
        <div class="col-span-2 mt-1">
          <label class="flex items-start gap-2 cursor-pointer">
            <input type="checkbox" v-model="createForm.skip_gl" class="mt-0.5 rounded text-amber-500" />
            <span class="text-sm text-gray-700">
              <span class="font-medium">Skip GL posting</span>
              <span class="text-gray-400 ml-1">— tick this for existing/half-paid loans already entered in Opening Balances</span>
            </span>
          </label>
        </div>
        <div class="flex justify-end gap-2">
          <button @click="showCreate = false" :disabled="creating" class="btn-secondary">Cancel</button>
          <button @click="createLoan" :disabled="creating" class="btn-primary flex items-center gap-2">
            <svg v-if="creating" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ creating ? 'Saving…' : 'Save Loan' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="repayLoan" class="fixed inset-0 bg-black/40 z-50 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Repay {{ repayLoan.loan_number }}</h3>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="form-label">Payment Date *</label><input v-model="repayForm.payment_date" type="date" class="form-input" /></div>
          <div><label class="form-label">Paid From *</label>
            <select v-model="repayForm.paid_from_account_id" class="form-input">
              <option value="">Select</option>
              <option v-for="a in assetAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
          <div><label class="form-label">Principal</label><input v-model.number="repayForm.principal_amount" type="number" min="0" step="0.01" class="form-input" /></div>
          <div><label class="form-label">Interest</label><input v-model.number="repayForm.interest_amount" type="number" min="0" step="0.01" class="form-input" /></div>
        </div>
        <div class="flex justify-end gap-2">
          <button @click="repayLoan = null" :disabled="repaying" class="btn-secondary">Cancel</button>
          <button @click="submitRepayment" :disabled="repaying" class="btn-primary flex items-center gap-2">
            <svg v-if="repaying" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ repaying ? 'Posting…' : 'Post Repayment' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import axios from 'axios'
import { fmtDate as _fmtDate } from '../utils/date.js'

const loans = ref({ data: [] })
const accounts = ref([])
const showCreate = ref(false)
const repayLoan = ref(null)
const historyLoan = ref(null)
const historyRepayments = ref([])
const historyLoading = ref(false)

const creating = ref(false)
const repaying = ref(false)

const createForm = ref({
  lender_name: '', principal_amount: 0, outstanding_balance: null,
  monthly_installment: null, start_date: today(), due_date: '', next_payment_date: '',
  liability_account_id: '', received_to_account_id: '', skip_gl: false,
})
const repayForm = ref({ payment_date: today(), principal_amount: 0, interest_amount: 0, paid_from_account_id: '' })

const liabilityAccounts = computed(() => accounts.value.filter(a => a.type === 'liability'))
const assetAccounts = computed(() => accounts.value.filter(a => a.type === 'asset'))

async function load() {
  const [loanRes, accRes] = await Promise.all([
    axios.get('/api/loans'),
    axios.get('/api/accounts/all'),
  ])
  loans.value = loanRes.data
  accounts.value = accRes.data
}

async function createLoan() {
  creating.value = true
  try {
    const payload = {
      ...createForm.value,
      post_to_gl: !createForm.value.skip_gl,
      outstanding_balance: createForm.value.outstanding_balance ?? createForm.value.principal_amount,
    }
    await axios.post('/api/loans', payload)
    showCreate.value = false
    createForm.value = { lender_name: '', principal_amount: 0, outstanding_balance: null, monthly_installment: null, start_date: today(), due_date: '', next_payment_date: '', liability_account_id: '', received_to_account_id: '', skip_gl: false }
    load()
  } finally {
    creating.value = false
  }
}

function openRepay(loan) {
  repayLoan.value = loan
  historyLoan.value = null
  repayForm.value = {
    payment_date: today(),
    principal_amount: Number(loan.monthly_installment || loan.outstanding_balance || 0),
    interest_amount: 0,
    paid_from_account_id: '',
  }
}

async function submitRepayment() {
  repaying.value = true
  try {
    await axios.post(`/api/loans/${repayLoan.value.id}/repay`, repayForm.value)
    repayLoan.value = null
    load()
  } finally {
    repaying.value = false
  }
}

async function openHistory(loan) {
  repayLoan.value = null
  historyLoan.value = loan
  historyRepayments.value = []
  historyLoading.value = true
  try {
    const { data: d } = await axios.get(`/api/loans/${loan.id}`)
    historyRepayments.value = d.repayments ?? []
  } finally {
    historyLoading.value = false
  }
}

function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDate(v) { return _fmtDate(v) }
function today() { return new Date().toISOString().slice(0, 10) }
function isDueSoon(date) {
  const diff = (new Date(date) - new Date()) / (1000 * 60 * 60 * 24)
  return diff <= 3
}

onMounted(load)
</script>
