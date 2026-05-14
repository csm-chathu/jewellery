<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Monthly Rentals</h2>
        <p class="text-sm text-gray-500 mt-0.5">Track rent dues, post payments to GL, and monitor reminders</p>
      </div>
      <button @click="showCreate = true" class="btn-primary">Add Rent Due</button>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="card">
        <p class="text-xs text-gray-500 uppercase">Upcoming / Overdue Reminders</p>
        <div class="mt-3 space-y-2 max-h-48 overflow-auto">
          <div v-for="r in reminders" :key="r.id" class="border rounded-lg p-2 text-sm">
            <p class="font-medium">{{ r.property_name }} ({{ r.month }})</p>
            <p class="text-xs text-gray-500">Due {{ fmtDate(r.due_date) }} • {{ r.days_left < 0 ? Math.abs(r.days_left) + ' days overdue' : r.days_left + ' days left' }}</p>
          </div>
          <p v-if="!reminders.length" class="text-sm text-gray-400">No reminders</p>
        </div>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500 uppercase">Quick Tip</p>
        <p class="text-sm text-gray-600 mt-2">When you mark rent as paid, the system automatically posts:
          <br />DR Rent Expense (5200)
          <br />CR Cash/Bank account selected.
        </p>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Rent #</th>
            <th class="table-th">Month</th>
            <th class="table-th">Property</th>
            <th class="table-th">Due Date</th>
            <th class="table-th text-right">Amount</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in rents.data" :key="r.id">
            <td class="table-td font-mono text-xs">{{ r.rent_number }}</td>
            <td class="table-td">{{ r.month }}</td>
            <td class="table-td">{{ r.property_name }}</td>
            <td class="table-td text-xs text-gray-500">{{ fmtDate(r.due_date) }}</td>
            <td class="table-td text-right">{{ lkr(r.amount) }}</td>
            <td class="table-td">
              <span class="badge text-xs" :class="statusClass(r.status)">{{ r.status }}</span>
            </td>
            <td class="table-td">
              <button v-if="r.status !== 'paid'" @click="openPay(r)" class="px-2.5 py-1 rounded bg-blue-100 text-blue-700 text-xs">Mark Paid</button>
              <span v-else class="text-xs text-green-600">Posted</span>
            </td>
          </tr>
          <tr v-if="!rents.data?.length"><td colspan="7" class="table-td text-center text-gray-400 py-8">No rent records</td></tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 z-50 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Create Rent Due</h3>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="form-label">Month (YYYY-MM) *</label><input v-model="createForm.month" class="form-input" placeholder="2026-05" /></div>
          <div><label class="form-label">Due Date *</label><input v-model="createForm.due_date" type="date" class="form-input" /></div>
          <div><label class="form-label">Property Name *</label><input v-model="createForm.property_name" class="form-input" /></div>
          <div><label class="form-label">Landlord</label><input v-model="createForm.landlord_name" class="form-input" /></div>
          <div><label class="form-label">Amount *</label><input v-model.number="createForm.amount" type="number" min="0" step="0.01" class="form-input" /></div>
          <div><label class="form-label">Reminder Days Before</label><input v-model.number="createForm.reminder_days_before" type="number" min="1" max="30" class="form-input" /></div>
        </div>
        <div class="flex justify-end gap-2">
          <button @click="showCreate = false" class="btn-secondary">Cancel</button>
          <button @click="createRent" class="btn-primary">Save</button>
        </div>
      </div>
    </div>

    <div v-if="payTarget" class="fixed inset-0 bg-black/40 z-50 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Pay {{ payTarget.rent_number }}</h3>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="form-label">Payment Date *</label><input v-model="payForm.payment_date" type="date" class="form-input" /></div>
          <div><label class="form-label">Method *</label>
            <select v-model="payForm.payment_method" class="form-input">
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="cheque">Cheque</option>
            </select>
          </div>
          <div class="col-span-2"><label class="form-label">Paid From Account *</label>
            <select v-model="payForm.paid_from_account_id" class="form-input">
              <option value="">Select</option>
              <option v-for="a in assetAccounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
            </select>
          </div>
          <template v-if="payForm.payment_method === 'cheque'">
            <div><label class="form-label">Cheque Number *</label><input v-model="payForm.cheque_number" class="form-input" /></div>
            <div><label class="form-label">Cheque Date *</label><input v-model="payForm.cheque_date" type="date" class="form-input" /></div>
          </template>
        </div>
        <div class="flex justify-end gap-2">
          <button @click="payTarget = null" class="btn-secondary">Cancel</button>
          <button @click="submitPayment" class="btn-primary">Pay & Post</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { fmtDate as _fmtDate } from '../utils/date.js'

const rents = ref({ data: [] })
const reminders = ref([])
const accounts = ref([])
const showCreate = ref(false)
const payTarget = ref(null)

const createForm = ref({
  month: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`,
  due_date: today(), property_name: '', landlord_name: '', amount: 0, reminder_days_before: 5,
  payment_method: 'bank_transfer', status: 'due',
})

const payForm = ref({ payment_date: today(), payment_method: 'bank_transfer', paid_from_account_id: '', cheque_number: '', cheque_date: today(), cheque_bank_name: '' })

const assetAccounts = computed(() => accounts.value.filter(a => a.type === 'asset'))

async function load() {
  const [r, rem, acc] = await Promise.all([
    axios.get('/api/rent-payments'),
    axios.get('/api/rent-payments/reminders', { params: { within_days: 10 } }),
    axios.get('/api/accounts/all'),
  ])
  rents.value = r.data
  reminders.value = rem.data.rows
  accounts.value = acc.data
}

async function createRent() {
  await axios.post('/api/rent-payments', createForm.value)
  showCreate.value = false
  createForm.value = { month: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`, due_date: today(), property_name: '', landlord_name: '', amount: 0, reminder_days_before: 5, payment_method: 'bank_transfer', status: 'due' }
  load()
}

function openPay(r) {
  payTarget.value = r
  payForm.value = { payment_date: today(), payment_method: 'bank_transfer', paid_from_account_id: '', cheque_number: '', cheque_date: today(), cheque_bank_name: '' }
}

async function submitPayment() {
  await axios.post(`/api/rent-payments/${payTarget.value.id}/pay`, payForm.value)
  payTarget.value = null
  load()
}

function statusClass(s) {
  return s === 'paid' ? 'bg-green-100 text-green-700' : (s === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')
}
function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDate(v) { return _fmtDate(v) }
function today() { return new Date().toISOString().slice(0, 10) }

onMounted(load)
</script>
