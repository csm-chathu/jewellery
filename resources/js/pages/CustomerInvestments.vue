<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Owner Investments</h2>
        <p class="text-sm text-gray-500 mt-0.5">Capital injected by the owner — tracked as a liability until repaid, with automatic GL posting</p>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Investment
      </button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-3 gap-3">
      <div class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Active</p>
        <p class="text-lg font-bold text-gray-800 mt-1">{{ loans.data?.filter(l => l.status === 'active').length ?? 0 }}</p>
      </div>
      <div class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Total Invested</p>
        <p class="text-lg font-bold text-gray-800 mt-1">LKR {{ lkr(totalReceived) }}</p>
      </div>
      <div class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Outstanding Balance</p>
        <p class="text-lg font-bold text-red-700 mt-1">LKR {{ lkr(totalOutstanding) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Ref #</th>
            <th class="table-th">Owner / Investor</th>
            <th class="table-th">Invested On</th>
            <th class="table-th">Due Date</th>
            <th class="table-th text-right">Principal</th>
            <th class="table-th text-right">Interest %</th>
            <th class="table-th text-right">Outstanding</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="loan in loans.data" :key="loan.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs text-gray-500">{{ loan.loan_number }}</td>
            <td class="table-td font-medium text-sm">{{ loan.lender_name }}</td>
            <td class="table-td text-sm text-gray-600">{{ fmtDate(loan.start_date) }}</td>
            <td class="table-td text-sm" :class="isOverdue(loan) ? 'text-red-600 font-medium' : 'text-gray-500'">
              {{ loan.due_date ? fmtDate(loan.due_date) : '—' }}
              <span v-if="isOverdue(loan)" class="block text-xs">Overdue</span>
            </td>
            <td class="table-td text-right font-mono">{{ lkr(loan.principal_amount) }}</td>
            <td class="table-td text-right text-sm text-gray-500">
              {{ loan.interest_rate ? loan.interest_rate + '%' : '—' }}
            </td>
            <td class="table-td text-right font-semibold" :class="loan.outstanding_balance > 0 ? 'text-red-700' : 'text-green-600'">
              {{ lkr(loan.outstanding_balance) }}
            </td>
            <td class="table-td">
              <span class="badge text-xs" :class="loan.status === 'active' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'">
                {{ loan.status }}
              </span>
            </td>
            <td class="table-td">
              <div class="flex gap-1.5">
                <button v-if="loan.status === 'active'" @click="openRepay(loan)"
                  class="px-2.5 py-1 rounded bg-blue-100 text-blue-700 text-xs hover:bg-blue-200">
                  Repay
                </button>
                <button @click="viewHistory(loan)"
                  class="px-2.5 py-1 rounded bg-gray-100 text-gray-600 text-xs hover:bg-gray-200">
                  History
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!loans.data?.length">
            <td colspan="9" class="table-td text-center text-gray-400 py-10">No owner investments recorded</td>
          </tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ loans.total ?? 0 }} investments</span>
        <div class="flex gap-2">
          <button @click="page--; load()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">← Prev</button>
          <button @click="page++; load()" :disabled="page>=loans.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>

    <teleport to="body">
      <!-- Create modal -->
      <div v-if="showCreate" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showCreate=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
            <h3 class="font-semibold text-gray-800">Record Owner Investment</h3>
            <button @click="showCreate=false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-6 space-y-4">

            <div>
              <label class="form-label">Owner / Investor Name *</label>
              <input v-model="createForm.lender_name" class="form-input" placeholder="e.g. Kamal Perera (Owner)" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Amount Invested (LKR) *</label>
                <input v-model.number="createForm.principal_amount" type="number" min="0" step="0.01" class="form-input" />
              </div>
              <div>
                <label class="form-label">Interest Rate (% p.a.)</label>
                <input v-model.number="createForm.interest_rate" type="number" min="0" step="0.01" class="form-input" placeholder="0 = interest-free" />
              </div>
              <div>
                <label class="form-label">Invested On *</label>
                <input v-model="createForm.start_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Repayment Due Date</label>
                <input v-model="createForm.due_date" type="date" class="form-input" />
              </div>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="createForm.notes" rows="2" class="form-input" placeholder="Purpose, agreed repayment terms…"></textarea>
            </div>

            <!-- GL toggle -->
            <div class="border rounded-xl p-4 space-y-3">
              <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <input type="checkbox" v-model="createForm.post_to_gl" class="rounded text-gold-600 w-4 h-4" />
                <span class="text-sm font-medium text-gray-700">Post to General Ledger (accounting)</span>
              </label>
              <p class="text-xs text-gray-400 -mt-1">Turn this on if you want the investment to appear in your accounts and reports.</p>

              <template v-if="createForm.post_to_gl">
                <div class="grid grid-cols-2 gap-4 pt-1">
                  <div>
                    <label class="form-label">Liability Account <span class="text-gray-400 font-normal">(owed back to owner)</span></label>
                    <select v-model="createForm.liability_account_id" class="form-input">
                      <option value="">Select…</option>
                      <option v-for="a in liabilityAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Received Into Account <span class="text-gray-400 font-normal">(cash / bank)</span></label>
                    <select v-model="createForm.received_to_account_id" class="form-input">
                      <option value="">Select…</option>
                      <option v-for="a in assetAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
                    </select>
                  </div>
                </div>
                <div class="bg-blue-50 rounded-lg px-3 py-2 text-xs text-blue-700">
                  <strong>Dr</strong> Cash / Bank &nbsp;|&nbsp; <strong>Cr</strong> Owner Investment Liability
                </div>
              </template>
            </div>

            <p v-if="createError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ createError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 sticky bottom-0">
            <button @click="showCreate=false" class="btn-secondary">Cancel</button>
            <button @click="submitCreate" :disabled="creating" class="btn-primary">
              {{ creating ? 'Saving…' : 'Record Investment' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Repay modal -->
      <div v-if="repayLoan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="repayLoan=null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
            <div>
              <h3 class="font-semibold text-gray-800">Post Repayment</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ repayLoan.loan_number }} · {{ repayLoan.lender_name }}</p>
            </div>
            <button @click="repayLoan=null" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2 bg-amber-50 rounded-lg px-4 py-3 text-sm">
                Outstanding balance: <strong class="text-red-700">LKR {{ lkr(repayLoan.outstanding_balance) }}</strong>
              </div>
              <div>
                <label class="form-label">Payment Date *</label>
                <input v-model="repayForm.payment_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Paid From Account *</label>
                <select v-model="repayForm.paid_from_account_id" class="form-input">
                  <option value="">Select…</option>
                  <option v-for="a in assetAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
                </select>
              </div>
              <div>
                <label class="form-label">Principal Repaid (LKR)</label>
                <input v-model.number="repayForm.principal_amount" type="number" min="0" step="0.01" class="form-input" />
              </div>
              <div>
                <label class="form-label">Interest Paid (LKR)</label>
                <input v-model.number="repayForm.interest_amount" type="number" min="0" step="0.01" class="form-input" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Notes</label>
                <input v-model="repayForm.notes" class="form-input" />
              </div>
            </div>
            <div class="bg-blue-50 rounded-lg px-4 py-3 text-xs text-blue-700">
              Total payment: <strong>LKR {{ lkr((repayForm.principal_amount || 0) + (repayForm.interest_amount || 0)) }}</strong>
            </div>
            <p v-if="repayError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ repayError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 sticky bottom-0">
            <button @click="repayLoan=null" class="btn-secondary">Cancel</button>
            <button @click="submitRepay" :disabled="repaying" class="btn-primary">{{ repaying ? 'Posting…' : 'Post Repayment' }}</button>
          </div>
        </div>
      </div>

      <!-- History modal -->
      <div v-if="historyLoan" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="historyLoan=null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[85vh] overflow-y-auto">
          <div class="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
            <div>
              <h3 class="font-semibold text-gray-800">Repayment History</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ historyLoan.loan_number }} · {{ historyLoan.lender_name }}</p>
            </div>
            <button @click="historyLoan=null" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-6">
            <div v-if="!historyData" class="text-center text-gray-400 py-8">Loading…</div>
            <template v-else>
              <div v-if="!historyData.repayments?.length" class="text-center text-gray-400 py-8">No repayments yet</div>
              <table v-else class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                  <tr>
                    <th class="table-th">Payment #</th>
                    <th class="table-th">Date</th>
                    <th class="table-th text-right">Principal</th>
                    <th class="table-th text-right">Interest</th>
                    <th class="table-th text-right">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="r in historyData.repayments" :key="r.id">
                    <td class="table-td font-mono text-xs text-gray-500">{{ r.payment_number }}</td>
                    <td class="table-td">{{ fmtDate(r.payment_date) }}</td>
                    <td class="table-td text-right">{{ lkr(r.principal_amount) }}</td>
                    <td class="table-td text-right">{{ lkr(r.interest_amount) }}</td>
                    <td class="table-td text-right font-semibold">{{ lkr(r.total_amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </template>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon } from '@heroicons/vue/24/outline'

const loans    = ref({ data: [], total: 0, last_page: 1 })
const accounts = ref([])
const page     = ref(1)

const showCreate  = ref(false)
const creating    = ref(false)
const createError = ref('')

const repayLoan  = ref(null)
const repaying   = ref(false)
const repayError = ref('')

const historyLoan = ref(null)
const historyData = ref(null)

const liabilityAccounts = computed(() => accounts.value.filter(a => a.type === 'liability'))
const assetAccounts     = computed(() => accounts.value.filter(a => a.type === 'asset'))

const totalReceived    = computed(() => (loans.value.data ?? []).reduce((s, l) => s + (l.principal_amount || 0), 0))
const totalOutstanding = computed(() => (loans.value.data ?? []).reduce((s, l) => s + (l.outstanding_balance || 0), 0))

const blankCreate = () => ({
  lender_name: '', principal_amount: 0, interest_rate: 0,
  start_date: today(), due_date: '', notes: '',
  post_to_gl: false, liability_account_id: '', received_to_account_id: '',
})
const createForm = reactive(blankCreate())

const blankRepay = () => ({ payment_date: today(), principal_amount: 0, interest_amount: 0, paid_from_account_id: '', notes: '' })
const repayForm  = reactive(blankRepay())

function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function today() { return new Date().toISOString().slice(0, 10) }
function fmtDate(v) {
  if (!v) return ''
  return new Date(v).toLocaleDateString('en-LK', { year: 'numeric', month: 'short', day: 'numeric' })
}
function isOverdue(loan) {
  return loan.status === 'active' && loan.due_date && new Date(loan.due_date) < new Date()
}

async function load() {
  const { data } = await axios.get('/api/loans', { params: { source: 'owner', page: page.value } })
  loans.value = data
}

function openCreate() {
  Object.assign(createForm, blankCreate())
  createError.value = ''
  showCreate.value = true
}

async function submitCreate() {
  creating.value = true; createError.value = ''
  try {
    await axios.post('/api/loans', { ...createForm, source: 'owner', post_to_gl: true })
    showCreate.value = false
    load()
  } catch (e) {
    createError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { creating.value = false }
}

function openRepay(loan) {
  repayLoan.value = loan
  repayError.value = ''
  Object.assign(repayForm, { ...blankRepay(), principal_amount: Number(loan.outstanding_balance || 0) })
}

async function submitRepay() {
  repaying.value = true; repayError.value = ''
  try {
    await axios.post(`/api/loans/${repayLoan.value.id}/repay`, repayForm)
    repayLoan.value = null
    load()
  } catch (e) {
    repayError.value = e.response?.data?.message ?? 'Failed to post repayment.'
  } finally { repaying.value = false }
}

async function viewHistory(loan) {
  historyLoan.value = loan
  historyData.value = null
  const { data } = await axios.get(`/api/loans/${loan.id}`)
  historyData.value = data
}

onMounted(async () => {
  const { data } = await axios.get('/api/accounts/all')
  accounts.value = data
  load()
})
</script>
