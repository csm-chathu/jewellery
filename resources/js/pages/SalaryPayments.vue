<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Salary Payments</h2>
        <p class="text-sm text-gray-500 mt-0.5">Process payroll — every payment auto-posts a journal entry to the General Ledger</p>
      </div>
      <button @click="openPay" class="btn-primary flex items-center gap-2">
        <BanknotesIcon class="w-4 h-4" /> Pay Salary
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <select v-model="empFilter" class="form-input w-52" @change="fetchPayments">
        <option value="">All employees</option>
        <option v-for="e in allEmployees" :key="e.id" :value="e.id">{{ e.employee_number }} – {{ e.name }}</option>
      </select>
      <input v-model="dateFrom" type="date" class="form-input w-36" @change="fetchPayments" title="From" />
      <span class="text-gray-400 text-xs">to</span>
      <input v-model="dateTo" type="date" class="form-input w-36" @change="fetchPayments" title="To" />
      <button @click="activeTab = 'payments'" :class="tabCls('payments')">Payments</button>
      <button @click="activeTab = 'summary'; fetchSummary()" :class="tabCls('summary')">Payroll Summary</button>
    </div>

    <!-- ── Payments tab ── -->
    <template v-if="activeTab === 'payments'">
      <!-- Summary cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
            <DocumentTextIcon class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Payments</p>
            <p class="text-2xl font-bold text-gray-800">{{ payments.total ?? 0 }}</p>
          </div>
        </div>
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
            <BanknotesIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Paid</p>
            <p class="text-lg font-bold text-amber-700">LKR {{ lkr(totalPaid) }}</p>
          </div>
        </div>
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
            <CheckCircleIcon class="w-5 h-5 text-green-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">GL Posted</p>
            <p class="text-2xl font-bold text-green-700">{{ glPostedCount }}</p>
          </div>
        </div>
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
            <UserGroupIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Employees Paid</p>
            <p class="text-2xl font-bold text-purple-700">{{ uniqueEmployees }}</p>
          </div>
        </div>
      </div>

      <!-- Payments table -->
      <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[800px]">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th w-36">Payment #</th>
                <th class="table-th">Employee</th>
                <th class="table-th w-44">Period</th>
                <th class="table-th w-24">Date</th>
                <th class="table-th w-28 text-right">Basic</th>
                <th class="table-th w-24 text-right">Allow.</th>
                <th class="table-th w-24 text-right">Deduct.</th>
                <th class="table-th w-28 text-right">Net</th>
                <th class="table-th w-24 text-center">GL</th>
                <th class="table-th w-20">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="10" class="table-td text-center py-10 text-gray-400">
                  <div class="flex items-center justify-center gap-2">
                    <ArrowPathIcon class="w-4 h-4 animate-spin" /> Loading…
                  </div>
                </td>
              </tr>
              <template v-else>
                <tr v-for="p in payments.data" :key="p.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs font-semibold text-gray-700 bg-gray-50">{{ p.payment_number }}</td>
                  <td class="table-td">
                    <p class="font-medium text-gray-800 text-sm">{{ p.employee?.name }}</p>
                    <p class="text-xs text-gray-400">{{ p.employee?.designation }}</p>
                  </td>
                  <td class="table-td text-xs text-gray-500">{{ fmtDate(p.period_from) }} – {{ fmtDate(p.period_to) }}</td>
                  <td class="table-td text-xs text-gray-600">{{ fmtDate(p.payment_date) }}</td>
                  <td class="table-td text-right font-mono text-sm">{{ lkr(p.basic_salary) }}</td>
                  <td class="table-td text-right font-mono text-sm text-green-600">
                    {{ p.allowances > 0 ? '+' + lkr(p.allowances) : '—' }}
                  </td>
                  <td class="table-td text-right font-mono text-sm text-red-600">
                    {{ p.deductions > 0 ? '-' + lkr(p.deductions) : '—' }}
                  </td>
                  <td class="table-td text-right font-bold text-gray-800">LKR {{ lkr(p.net_salary) }}</td>
                  <td class="table-td text-center">
                    <span v-if="p.journal_entry_id"
                      class="badge bg-green-100 text-green-700 text-xs cursor-pointer hover:bg-green-200"
                      @click="goToGL(p)"
                      title="View in General Ledger">
                      ✓ Posted
                    </span>
                    <span v-else class="badge bg-yellow-100 text-yellow-700 text-xs">Draft</span>
                  </td>
                  <td class="table-td">
                    <button v-if="p.status !== 'paid'"
                      @click="deletePayment(p)"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                    <span v-else class="text-xs text-gray-300">—</span>
                  </td>
                </tr>
                <tr v-if="!payments.data?.length">
                  <td colspan="10" class="table-td text-center text-gray-400 py-10">No salary payments found</td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
          <span>{{ payments.total ?? 0 }} records</span>
          <div class="flex gap-2">
            <button @click="page--; fetchPayments()" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
            <button @click="page++; fetchPayments()" :disabled="page >= payments.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
          </div>
        </div>
      </div>
    </template>

    <!-- ── Payroll Summary tab ── -->
    <template v-else-if="activeTab === 'summary'">
      <div v-if="summaryData" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div class="card text-center bg-amber-50">
            <p class="text-xs text-amber-600 uppercase tracking-wide">Total Payroll</p>
            <p class="text-2xl font-bold text-amber-800">LKR {{ lkr(summaryData.grand_total) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Period</p>
            <p class="text-sm font-semibold text-gray-700">{{ summaryData.from }} to {{ summaryData.to }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Employees</p>
            <p class="text-2xl font-bold text-gray-800">{{ summaryData.rows?.length }}</p>
          </div>
        </div>

        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50">
            <span class="font-semibold text-gray-700 text-sm">Payroll Summary by Employee</span>
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Employee</th>
                <th class="table-th">Designation</th>
                <th class="table-th">Department</th>
                <th class="table-th w-20 text-center">Payments</th>
                <th class="table-th w-32 text-right">Total Basic</th>
                <th class="table-th w-28 text-right">Allowances</th>
                <th class="table-th w-28 text-right">Deductions</th>
                <th class="table-th w-32 text-right">Net Paid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in summaryData.rows" :key="row.id" class="hover:bg-gray-50">
                <td class="table-td">
                  <p class="font-semibold text-gray-800">{{ row.name }}</p>
                  <p class="text-xs font-mono text-gray-400">{{ row.employee_number }}</p>
                </td>
                <td class="table-td text-gray-500 text-xs">{{ row.designation ?? '—' }}</td>
                <td class="table-td text-gray-500 text-xs">{{ row.department ?? '—' }}</td>
                <td class="table-td text-center">
                  <span class="badge bg-gray-100 text-gray-600">{{ row.payment_count }}</span>
                </td>
                <td class="table-td text-right font-mono">{{ lkr(row.total_basic) }}</td>
                <td class="table-td text-right font-mono text-green-600">{{ lkr(row.total_allowances) }}</td>
                <td class="table-td text-right font-mono text-red-600">{{ lkr(row.total_deductions) }}</td>
                <td class="table-td text-right font-bold text-gray-800">LKR {{ lkr(row.total_net) }}</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-bold">
              <tr>
                <td class="table-td" colspan="4">Grand Total</td>
                <td class="table-td text-right font-mono">{{ lkr(summaryData.rows?.reduce((s,r) => s + Number(r.total_basic), 0)) }}</td>
                <td class="table-td text-right font-mono text-green-600">{{ lkr(summaryData.rows?.reduce((s,r) => s + Number(r.total_allowances), 0)) }}</td>
                <td class="table-td text-right font-mono text-red-600">{{ lkr(summaryData.rows?.reduce((s,r) => s + Number(r.total_deductions), 0)) }}</td>
                <td class="table-td text-right text-amber-700">LKR {{ lkr(summaryData.grand_total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div v-else class="card text-center py-16 text-gray-400">
        <ArrowPathIcon class="w-6 h-6 animate-spin mx-auto mb-2" /> Loading summary…
      </div>
    </template>

    <!-- ── Pay Salary Modal ── -->
    <div v-if="payModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
          <h3 class="font-semibold text-gray-800">Process Salary Payment</h3>
          <button @click="payModal = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
        </div>
        <form @submit.prevent="submitPayment" class="overflow-y-auto flex-1 p-6 space-y-4">
          <div>
            <label class="form-label">Employee *</label>
            <select v-model="payForm.employee_id" class="form-input" required @change="prefillSalary">
              <option value="">— Select Employee —</option>
              <option v-for="e in allEmployees" :key="e.id" :value="e.id">
                {{ e.employee_number }} – {{ e.name }} (LKR {{ lkr(e.basic_salary) }})
              </option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Period From *</label>
              <input v-model="payForm.period_from" type="date" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Period To *</label>
              <input v-model="payForm.period_to" type="date" class="form-input" required />
            </div>
          </div>
          <div>
            <label class="form-label">Payment Date *</label>
            <input v-model="payForm.payment_date" type="date" class="form-input" required />
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="form-label">Basic Salary (LKR) *</label>
              <input v-model.number="payForm.basic_salary" type="number" min="0" step="0.01" class="form-input" required @input="calcNet" />
            </div>
            <div>
              <label class="form-label">Allowances</label>
              <input v-model.number="payForm.allowances" type="number" min="0" step="0.01" class="form-input" @input="calcNet" />
            </div>
            <div>
              <label class="form-label">Deductions</label>
              <input v-model.number="payForm.deductions" type="number" min="0" step="0.01" class="form-input" @input="calcNet" />
            </div>
          </div>

          <!-- Net salary preview -->
          <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center justify-between">
            <span class="text-sm font-medium text-amber-700">Net Salary</span>
            <span class="text-xl font-bold text-amber-800">LKR {{ lkr(netSalary) }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Payment Method *</label>
              <select v-model="payForm.payment_method" class="form-input" required>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>
            <div>
              <label class="form-label">Pay From Account *</label>
              <select v-model="payForm.paid_from_account_id" class="form-input" required>
                <option value="">— Select —</option>
                <option v-for="a in cashAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
              </select>
            </div>
          </div>
          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="payForm.notes" rows="2" class="form-input" placeholder="Optional notes…"></textarea>
          </div>

          <!-- GL Info box -->
          <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-700 space-y-1">
            <p class="font-semibold">Auto GL Posting on Submit:</p>
            <p>DR 5210 Salaries &amp; Wages → LKR {{ lkr(netSalary) }}</p>
            <p>CR {{ payForm.paid_from_account_id ? cashAccounts.find(a=>a.id==payForm.paid_from_account_id)?.code : '????' }} {{ payForm.paid_from_account_id ? cashAccounts.find(a=>a.id==payForm.paid_from_account_id)?.name : 'Selected Account' }} → LKR {{ lkr(netSalary) }}</p>
          </div>

          <p v-if="payError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ payError }}</p>
        </form>
        <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
          <button type="button" @click="payModal = false" class="btn-secondary">Cancel</button>
          <button type="button" @click="submitPayment" :disabled="paying || netSalary <= 0" class="btn-primary disabled:opacity-50">
            {{ paying ? 'Processing…' : 'Post & Pay' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { fmtDate as _fmtDate } from '../utils/date.js'
import { useRouter } from 'vue-router'
import {
  BanknotesIcon, TrashIcon, XMarkIcon, ArrowPathIcon,
  UserGroupIcon, DocumentTextIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()

const payments      = ref({ data: [] })
const allEmployees  = ref([])
const cashAccounts  = ref([])
const summaryData   = ref(null)
const loading       = ref(false)
const activeTab     = ref('payments')
const empFilter     = ref('')
const dateFrom      = ref('')
const dateTo        = ref('')
const page          = ref(1)
const payModal      = ref(false)
const paying        = ref(false)
const payError      = ref('')

const defaultPayForm = () => ({
  employee_id:          '',
  period_from:          firstOfMonth(),
  period_to:            lastOfMonth(),
  payment_date:         today(),
  basic_salary:         0,
  allowances:           0,
  deductions:           0,
  payment_method:       'bank_transfer',
  paid_from_account_id: '',
  notes:                '',
})
const payForm = ref(defaultPayForm())

const netSalary       = computed(() => Math.max(0, (payForm.value.basic_salary || 0) + (payForm.value.allowances || 0) - (payForm.value.deductions || 0)))
const totalPaid       = computed(() => payments.value.data?.reduce((s, p) => s + Number(p.net_salary), 0) ?? 0)
const glPostedCount   = computed(() => payments.value.data?.filter(p => p.journal_entry_id).length ?? 0)
const uniqueEmployees = computed(() => new Set(payments.value.data?.map(p => p.employee_id)).size)

function tabCls(t) {
  return ['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border',
    activeTab.value === t ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'].join(' ')
}

async function fetchPayments() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/salary-payments', {
      params: { page: page.value, employee_id: empFilter.value, from: dateFrom.value, to: dateTo.value },
    })
    payments.value = data
  } finally {
    loading.value = false
  }
}

async function fetchSummary() {
  summaryData.value = null
  const { data } = await axios.get('/api/salary-payments/summary', {
    params: { from: dateFrom.value || firstOfYear(), to: dateTo.value || today() },
  })
  summaryData.value = data
}

function openPay() {
  payForm.value = defaultPayForm()
  payError.value = ''
  payModal.value = true
}

function prefillSalary() {
  const emp = allEmployees.value.find(e => e.id == payForm.value.employee_id)
  if (emp) payForm.value.basic_salary = emp.basic_salary
}

function calcNet() {}  // reactive via computed

async function submitPayment() {
  paying.value   = true
  payError.value = ''
  try {
    await axios.post('/api/salary-payments', payForm.value)
    payModal.value = false
    fetchPayments()
  } catch (e) {
    payError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0] ?? 'Failed to process payment.'
  } finally {
    paying.value = false
  }
}

async function deletePayment(p) {
  if (!confirm(`Delete payment ${p.payment_number}?`)) return
  try {
    await axios.delete(`/api/salary-payments/${p.id}`)
    fetchPayments()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Cannot delete this payment.')
  }
}

function goToGL(p) {
  router.push('/general-ledger')
}

function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDate(d) { return _fmtDate(d) }
function today() { return new Date().toISOString().slice(0, 10) }
function firstOfMonth() { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-01` }
function lastOfMonth() { const d = new Date(new Date().getFullYear(), new Date().getMonth()+1, 0); return d.toISOString().slice(0,10) }
function firstOfYear() { return `${new Date().getFullYear()}-01-01` }

onMounted(async () => {
  const [empRes, accRes] = await Promise.all([
    axios.get('/api/employees/all'),
    axios.get('/api/accounts/all'),
  ])
  allEmployees.value = empRes.data
  cashAccounts.value = accRes.data.filter(a => a.type === 'asset' && ['current_asset'].includes(a.sub_type))
  fetchPayments()
})
</script>
