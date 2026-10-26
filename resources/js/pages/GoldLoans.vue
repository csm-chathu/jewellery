<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Gold Loans</h2>
        <p class="text-sm text-gray-500 mt-0.5">Issue loans against pledged gold, track maturity, and post repayments to GL</p>
      </div>
      <button @click="openCreate" class="btn-primary">New Gold Loan</button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="card lg:col-span-2 p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Loan #</th>
              <th class="table-th">Customer</th>
              <th class="table-th">Maturity</th>
              <th class="table-th text-right">Principal</th>
              <th class="table-th text-right">Outstanding</th>
              <th class="table-th">Status</th>
              <th class="table-th">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-if="loading">
              <tr v-for="i in 6" :key="i" class="animate-pulse">
                <td class="table-td"><div class="h-3.5 w-16 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-3.5 w-24 bg-gray-200 rounded mb-1"></div><div class="h-3 w-16 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded"></div></td>
                <td class="table-td text-right"><div class="h-3.5 w-20 bg-gray-200 rounded ml-auto"></div></td>
                <td class="table-td text-right"><div class="h-3.5 w-20 bg-gray-200 rounded ml-auto"></div></td>
                <td class="table-td"><div class="h-5 w-14 bg-gray-200 rounded-full"></div></td>
                <td class="table-td"><div class="flex gap-2"><div class="h-6 w-14 bg-gray-200 rounded-md"></div></div></td>
              </tr>
            </template>
            <template v-else>
            <tr v-for="loan in loans.data" :key="loan.id">
              <td class="table-td font-mono text-xs">{{ loan.loan_number }}</td>
              <td class="table-td">
                <p class="font-medium text-sm">{{ loan.customer?.name }}</p>
                <p class="text-xs text-gray-400">{{ loan.customer?.phone || '—' }}</p>
              </td>
              <td class="table-td text-xs text-gray-500">{{ fmtDate(loan.maturity_date) }}</td>
              <td class="table-td text-right">{{ lkr(loan.loan_amount) }}</td>
              <td class="table-td text-right font-semibold" :class="loan.outstanding_principal > 0 ? 'text-red-700' : 'text-green-700'">
                {{ lkr(loan.outstanding_principal) }}
              </td>
              <td class="table-td">
                <span class="badge text-xs" :class="statusClass(loan.status)">{{ loan.status }}</span>
              </td>
              <td class="table-td">
                <div class="flex gap-1.5 items-center">
                  <button @click="openRepay(loan)" class="px-2.5 py-1 rounded bg-blue-100 text-blue-700 text-xs">Repay</button>
                  <button @click="printLoan(loan)" :disabled="printing === loan.id" class="px-2.5 py-1 rounded bg-gray-100 text-gray-600 text-xs hover:bg-gray-200">
                    {{ printing === loan.id ? '…' : '🖨' }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loans.data?.length"><td colspan="7" class="table-td text-center py-8 text-gray-400">No gold loans</td></tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="card">
        <h3 class="font-semibold text-gray-800 mb-2">Due Reminders</h3>
        <div class="space-y-2 max-h-96 overflow-auto">
          <div v-for="r in reminders" :key="r.id" class="border rounded-lg px-3 py-2">
            <p class="text-sm font-medium">{{ r.loan_number }} · {{ r.customer_name }}</p>
            <p class="text-xs text-gray-500">Due {{ fmtDate(r.maturity_date) }} · {{ r.days_left < 0 ? Math.abs(r.days_left) + ' days overdue' : r.days_left + ' days left' }}</p>
            <p class="text-xs text-gray-600">Outstanding: LKR {{ lkr(Number(r.outstanding_principal) + Number(r.accrued_interest)) }}</p>
          </div>
          <p v-if="!reminders.length" class="text-sm text-gray-400">No upcoming reminders</p>
        </div>
      </div>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 z-50 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl p-6 space-y-4 max-h-[90vh] overflow-auto">
        <h3 class="font-semibold text-gray-800">New Gold Loan</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Customer *</label>
            <select v-model="createForm.customer_id" class="form-input">
              <option value="">Select customer</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} {{ c.phone ? `· ${c.phone}` : '' }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Pledge Description *</label>
            <input v-model="createForm.pledge_description" class="form-input" placeholder="22K chain with pendant" />
          </div>
          <div>
            <label class="form-label">Item Type *</label>
            <select v-model="createForm.item_type" class="form-input">
              <option value="jewelry">Jewelry</option>
              <option value="coin">Coin</option>
              <option value="bar">Bar</option>
              <option value="scrap">Scrap</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="form-label">Declared Karat *</label>
            <select v-model="createForm.declared_karat" class="form-input">
              <option value="unknown">Unknown</option>
              <option value="9k">9K</option>
              <option value="14k">14K</option>
              <option value="18k">18K</option>
              <option value="22k">22K</option>
              <option value="24k">24K</option>
            </select>
          </div>
          <div>
            <label class="form-label">Gross Weight (g)</label>
            <input v-model.number="createForm.gross_weight" type="number" min="0" step="0.001" class="form-input" />
          </div>
          <div>
            <label class="form-label">Deduction Weight (g)</label>
            <input v-model.number="createForm.deduction_weight" type="number" min="0" step="0.001" class="form-input" />
          </div>
          <div>
            <label class="form-label">Net Weight (g)</label>
            <input v-model.number="createForm.net_weight" type="number" min="0" step="0.001" class="form-input" />
          </div>
          <div>
            <label class="form-label">Loan Amount (LKR) *</label>
            <input v-model.number="createForm.loan_amount" type="number" min="1" step="0.01" class="form-input" />
          </div>
          <div>
            <label class="form-label">Interest % per month *</label>
            <input v-model.number="createForm.interest_rate_monthly" type="number" min="0" step="0.01" class="form-input" />
          </div>
          <div>
            <label class="form-label">Disbursed Date *</label>
            <input v-model="createForm.disbursed_date" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Maturity Date</label>
            <input v-model="createForm.maturity_date" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Loan Receivable Account</label>
            <select v-model="createForm.loan_receivable_account_id" class="form-input">
              <option value="">Default (1110)</option>
              <option v-for="a in assetAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Disburse From Account *</label>
            <select v-model="createForm.disbursed_from_account_id" class="form-input">
              <option value="">Select</option>
              <option v-for="a in cashBankAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
        </div>
        <div>
          <label class="form-label">Notes</label>
          <textarea v-model="createForm.notes" rows="2" class="form-input"></textarea>
        </div>
        <p v-if="createError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ createError }}</p>
        <div class="flex justify-end gap-2">
          <button @click="showCreate = false" class="btn-secondary">Cancel</button>
          <button @click="createLoan" :disabled="creating" class="btn-primary">{{ creating ? 'Saving…' : 'Create Loan' }}</button>
        </div>
      </div>
    </div>

    <div v-if="repayModal" class="fixed inset-0 bg-black/40 z-50 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Repay Gold Loan</h3>
        <p class="text-sm text-gray-500">{{ selectedLoan?.loan_number }} · Outstanding principal: LKR {{ lkr(selectedLoan?.outstanding_principal) }}</p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Payment Date *</label>
            <input v-model="repayForm.payment_date" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Amount *</label>
            <input v-model.number="repayForm.amount" type="number" min="0.01" step="0.01" class="form-input" />
          </div>
          <div class="col-span-2">
            <label class="form-label">Received To Account *</label>
            <select v-model="repayForm.received_to_account_id" class="form-input">
              <option value="">Select</option>
              <option v-for="a in cashBankAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
          <input v-model="repayForm.close_loan" type="checkbox" class="rounded" />
          Close loan after this payment
        </label>
        <p v-if="repayError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ repayError }}</p>
        <div class="flex justify-end gap-2">
          <button @click="repayModal = false" class="btn-secondary">Cancel</button>
          <button @click="submitRepayment" :disabled="repaying" class="btn-primary">{{ repaying ? 'Posting…' : 'Post Repayment' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { fmtDate } from '../utils/date.js'

const loans = ref({ data: [] })
const reminders = ref([])
const customers = ref([])
const accounts = ref([])
const loading = ref(false)

const showCreate = ref(false)
const creating = ref(false)
const createError = ref('')

const repayModal = ref(false)
const repaying = ref(false)
const repayError = ref('')
const selectedLoan = ref(null)

const printing = ref(null)

const createForm = ref(defaultCreateForm())
const repayForm = ref(defaultRepayForm())

const assetAccounts = computed(() => accounts.value.filter(a => a.type === 'asset'))
const cashBankAccounts = computed(() => accounts.value.filter(a => ['1000', '1010'].includes(a.code)))

function defaultCreateForm() {
  return {
    customer_id: '',
    pledge_description: '',
    item_type: 'jewelry',
    gross_weight: 0,
    deduction_weight: 0,
    net_weight: 0,
    declared_karat: 'unknown',
    loan_amount: 0,
    interest_rate_monthly: 3,
    disbursed_date: today(),
    maturity_date: addMonths(today(), 3),
    loan_receivable_account_id: '',
    disbursed_from_account_id: '',
    notes: '',
  }
}

function defaultRepayForm() {
  return {
    payment_date: today(),
    amount: 0,
    received_to_account_id: '',
    close_loan: false,
  }
}

function openCreate() {
  createError.value = ''
  createForm.value = defaultCreateForm()
  showCreate.value = true
}

function openRepay(loan) {
  selectedLoan.value = loan
  repayError.value = ''
  repayForm.value = {
    payment_date: today(),
    amount: Number(loan.outstanding_principal || 0),
    received_to_account_id: '',
    close_loan: false,
  }
  repayModal.value = true
}

async function load() {
  loading.value = true
  try {
    const [loansRes, remindersRes, customersRes, accountsRes] = await Promise.all([
      axios.get('/api/gold-loans'),
      axios.get('/api/gold-loans/reminders', { params: { within_days: 10 } }),
      axios.get('/api/customers/all'),
      axios.get('/api/accounts/all'),
    ])

    loans.value = loansRes.data
    reminders.value = remindersRes.data.rows
    customers.value = customersRes.data
    accounts.value = accountsRes.data
  } finally {
    loading.value = false
  }
}

async function createLoan() {
  creating.value = true
  createError.value = ''
  try {
    await axios.post('/api/gold-loans', createForm.value)
    showCreate.value = false
    await load()
  } catch (e) {
    createError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Failed to create gold loan'
  } finally {
    creating.value = false
  }
}

async function submitRepayment() {
  if (!selectedLoan.value) return

  repaying.value = true
  repayError.value = ''
  try {
    await axios.post(`/api/gold-loans/${selectedLoan.value.id}/repay`, repayForm.value)
    repayModal.value = false
    selectedLoan.value = null
    await load()
  } catch (e) {
    repayError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Failed to post repayment'
  } finally {
    repaying.value = false
  }
}

function statusClass(status) {
  return {
    active: 'bg-blue-100 text-blue-700',
    overdue: 'bg-red-100 text-red-700',
    closed: 'bg-green-100 text-green-700',
    forfeited: 'bg-gray-100 text-gray-700',
  }[status] ?? 'bg-gray-100 text-gray-700'
}

function today() {
  return new Date().toISOString().slice(0, 10)
}

function addMonths(dateString, months) {
  const d = new Date(dateString)
  d.setMonth(d.getMonth() + months)
  return d.toISOString().slice(0, 10)
}

function lkr(value) {
  return Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function printLoan(loan) {
  printing.value = loan.id
  try {
    const { data } = await axios.get(`/api/gold-loans/${loan.id}`)
    const l = data
    const repayments = l.repayments ?? []

    const repayRows = repayments.length
      ? repayments.map(r => `
          <tr>
            <td>${fmtDate(r.payment_date)}</td>
            <td style="text-align:right">LKR ${lkr(r.amount)}</td>
            <td>${r.notes ?? '—'}</td>
          </tr>`).join('')
      : '<tr><td colspan="3" style="text-align:center;color:#888">No repayments yet</td></tr>'

    const statusColor = { active: '#1d4ed8', overdue: '#dc2626', closed: '#16a34a', forfeited: '#6b7280' }[l.status] ?? '#374151'

    const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>Gold Loan Receipt — ${l.loan_number}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #111; padding: 24px; max-width: 680px; margin: auto; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
  .shop-name { font-size: 20px; font-weight: 700; }
  .shop-sub { font-size: 11px; color: #555; margin-top: 2px; }
  .doc-meta { text-align: right; font-size: 12px; }
  .doc-meta .ln { font-size: 16px; font-weight: 700; color: #b45309; }
  hr { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0; }
  .section { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; margin-bottom: 14px; }
  .row { display: flex; flex-direction: column; }
  .label { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: .5px; margin-bottom: 1px; }
  .value { font-size: 13px; font-weight: 600; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th { background: #1f2937; color: #fff; padding: 6px 10px; text-align: left; font-size: 12px; }
  th:last-child, td:last-child { text-align: left; }
  td { padding: 6px 10px; font-size: 12px; border-bottom: 1px solid #f3f4f6; }
  .status-badge { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; color: #fff; background: ${statusColor}; }
  .footer { margin-top: 24px; text-align: center; font-size: 11px; color: #9ca3af; }
  @media print { body { padding: 0; } }
</style>
</head>
<body>
<div class="header">
  <div>
    <div class="shop-name">රන් අත්වැල් GOLD HOUSE</div>
    <div class="shop-sub">Private Gold Book — Gold Loan Receipt</div>
  </div>
  <div class="doc-meta">
    <div class="ln">${l.loan_number}</div>
    <div>Issued: ${fmtDate(l.disbursed_date)}</div>
    <div>Maturity: ${fmtDate(l.maturity_date)}</div>
    <div style="margin-top:4px"><span class="status-badge">${l.status?.toUpperCase()}</span></div>
  </div>
</div>
<hr/>

<div class="section">
  <div class="row"><span class="label">Borrower</span><span class="value">${l.customer?.name ?? '—'}</span></div>
  <div class="row"><span class="label">Phone</span><span class="value">${l.customer?.phone ?? '—'}</span></div>
  <div class="row"><span class="label">Pledge Item</span><span class="value">${l.pledge_description ?? '—'}</span></div>
  <div class="row"><span class="label">Item Type</span><span class="value">${l.item_type ?? '—'}</span></div>
  <div class="row"><span class="label">Declared Karat</span><span class="value">${l.declared_karat ?? '—'}</span></div>
  <div class="row"><span class="label">Gross / Net Weight</span><span class="value">${l.gross_weight ?? '0'}g / ${l.net_weight ?? '0'}g</span></div>
</div>
<hr/>

<div class="section">
  <div class="row"><span class="label">Loan Amount</span><span class="value" style="color:#1d4ed8">LKR ${lkr(l.loan_amount)}</span></div>
  <div class="row"><span class="label">Interest Rate</span><span class="value">${l.interest_rate_monthly ?? 0}% / month</span></div>
  <div class="row"><span class="label">Outstanding Principal</span><span class="value" style="color:${Number(l.outstanding_principal) > 0 ? '#dc2626' : '#16a34a'}">LKR ${lkr(l.outstanding_principal)}</span></div>
  <div class="row"><span class="label">Notes</span><span class="value" style="font-weight:400">${l.notes ?? '—'}</span></div>
</div>
<hr/>

<div style="font-weight:600; font-size:12px; margin-bottom:4px; color:#374151">Repayment History</div>
<table>
  <thead><tr><th>Date</th><th style="text-align:right">Amount</th><th>Notes</th></tr></thead>
  <tbody>${repayRows}</tbody>
</table>

<div class="footer">*** This is a Private Gold Book record — not linked to main accounts ***</div>
</body>
</html>`

    const w = window.open('', '_blank', 'width=720,height=900')
    w.document.write(html)
    w.document.close()
    w.onload = () => { w.focus(); w.print() }
  } catch (e) {
    alert('Could not load loan details for printing.')
  } finally {
    printing.value = null
  }
}

onMounted(load)
</script>
