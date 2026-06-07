<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Expense Management</h2>
        <p class="text-sm text-gray-500 mt-0.5">Track shop expenses: rent, utilities, supplies, maintenance, etc.</p>
      </div>
      <button @click="openModal(null)" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Expense
      </button>
    </div>

    <!-- Filters & Summary -->
    <div class="card space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
        <input v-model="search" placeholder="Search description..." class="form-input" @input="load" />
        <input v-model="filters.from_date" type="date" class="form-input" @change="load" />
        <input v-model="filters.to_date" type="date" class="form-input" @change="load" />
        <select v-model="filters.expense_account_id" class="form-input" @change="load">
          <option value="">All Accounts</option>
          <option v-for="a in expenseAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
        </select>
        <select v-model="filters.payment_method" class="form-input" @change="load">
          <option value="">All Payment Methods</option>
          <option value="cash">Cash</option>
          <option value="cheque">Cheque</option>
          <option value="bank_transfer">Bank Transfer</option>
          <option value="card">Card</option>
        </select>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
          <p class="text-xs text-gray-600 uppercase tracking-wide">Total Expenses</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">LKR {{ formatNumber(summary.grand_total) }}</p>
        </div>
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg p-4">
          <p class="text-xs text-gray-600 uppercase tracking-wide">Entries</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ expenses.length }}</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-lg p-4">
          <p class="text-xs text-gray-600 uppercase tracking-wide">Average</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">LKR {{ formatNumber(avgExpense) }}</p>
        </div>
      </div>
    </div>

    <!-- Category Breakdown (if summary available) -->
    <div v-if="summary.by_category?.length" class="card">
      <h3 class="font-semibold text-gray-800 mb-4">Expenses by Category</h3>
      <div class="space-y-3">
        <div v-for="item in summary.by_category" :key="item.expense_account_id" class="flex items-center justify-between pb-3 border-b last:border-b-0">
          <div class="flex-1">
            <p class="font-medium text-gray-700">{{ item.account_name || item.expense_account_id }}</p>
            <p class="text-xs text-gray-500">{{ item.count }} {{ item.count === 1 ? 'entry' : 'entries' }}</p>
          </div>
          <div class="text-right">
            <p class="font-semibold text-gray-900">LKR {{ formatNumber(item.total) }}</p>
            <p class="text-xs text-gray-500">{{ formatNumber((parseFloat(item.total || 0) / parseFloat(summary.grand_total || 1)) * 100, 1) }}%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Expenses Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Date</th>
              <th class="table-th">Category</th>
              <th class="table-th">Description</th>
              <th class="table-th">Amount</th>
              <th class="table-th">Payment Method</th>
              <th class="table-th">Paid By</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-if="loading">
              <tr v-for="n in 7" :key="n" class="animate-pulse">
                <td class="table-td"><div class="h-3.5 w-16 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-5 w-20 bg-gray-200 rounded-full"></div></td>
                <td class="table-td"><div class="h-3.5 w-40 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-3.5 w-24 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-5 w-16 bg-gray-200 rounded-full"></div></td>
                <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded"></div></td>
                <td class="table-td text-right">
                  <div class="flex justify-end gap-1.5">
                    <div class="h-6 w-14 bg-gray-200 rounded-md"></div>
                    <div class="h-6 w-16 bg-gray-200 rounded-md"></div>
                  </div>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr v-for="exp in expenses" :key="exp.id" class="hover:bg-gray-50">
                <td class="table-td text-sm">{{ fmtDate(exp.expense_date) }}</td>
                <td class="table-td">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ exp.expense_account?.name || '—' }}
                  </span>
                </td>
                <td class="table-td text-sm max-w-xs truncate">{{ exp.description }}</td>
                <td class="table-td font-semibold text-gray-900">LKR {{ parseFloat(exp.amount).toFixed(2) }}</td>
                <td class="table-td text-sm">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                    :class="paymentBadgeClass(exp.payment_method)">
                    {{ exp.payment_method?.replace('_', ' ') }}
                  </span>
                </td>
                <td class="table-td text-sm">{{ exp.paid_by_user?.name }}</td>
                <td class="table-td text-right">
                  <div class="flex justify-end gap-1.5">
                    <button @click="openModal(exp)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                      <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                    </button>
                    <button @click="deleteExpense(exp)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                      <TrashIcon class="w-3.5 h-3.5" /> Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!expenses.length">
                <td colspan="7" class="table-td text-center text-gray-400 py-8">No expenses found</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white">
          <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit Expense' : 'Add Expense' }}</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form @submit.prevent="save" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Expense Date *</label>
              <input v-model="form.expense_date" type="date" required class="form-input" />
            </div>
            <div>
              <label class="form-label">Expense Account *</label>
              <select v-model.number="form.expense_account_id" required class="form-input">
                <option value="">— Select Account —</option>
                <option v-for="a in expenseAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="form-label">Description *</label>
              <input v-model="form.description" required maxlength="500" class="form-input" placeholder="e.g., Office supplies for reception" />
            </div>
            <div>
              <label class="form-label">Amount (LKR) *</label>
              <input v-model.number="form.amount" type="number" required min="0" step="0.01" class="form-input" />
            </div>
            <div>
              <label class="form-label">Payment Method *</label>
              <select v-model="form.payment_method" required class="form-input">
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="card">Card</option>
              </select>
            </div>
            <div>
              <label class="form-label">Reference Number</label>
              <input v-model="form.reference_number" maxlength="100" class="form-input" placeholder="Cheque/Receipt no" />
            </div>
            <div>
              <label class="form-label">Paid By User *</label>
              <select v-model.number="form.paid_by_user_id" required class="form-input">
                <option value="">— Select User —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" maxlength="1000" rows="3" class="form-input" placeholder="Additional details..."></textarea>
            </div>
          </div>

          <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>

          <div class="flex gap-3 pt-2 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary flex-1">
              {{ saving ? 'Saving…' : (editing ? 'Update Expense' : 'Add Expense') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import { fmtDate } from '../utils/date.js'

const expenses = ref([])
const users = ref([])
const showModal = ref(false)
const editing = ref(null)
const saving = ref(false)
const formError = ref('')
const summary = ref({ by_category: [], grand_total: 0 })
const loading = ref(false)

const search = ref('')
const filters = reactive({ from_date: '', to_date: '', expense_account_id: '', payment_method: '' })
const expenseAccounts = ref([])

const form = reactive({
  expense_date: new Date().toISOString().split('T')[0],
  expense_account_id: '', description: '', amount: 0,
  payment_method: 'cash', reference_number: '', paid_by_user_id: '',
  notes: '',
})

const avgExpense = computed(() => {
  if (!expenses.value.length) return 0
  const total = expenses.value.reduce((sum, e) => sum + parseFloat(e.amount), 0)
  return total / expenses.value.length
})

function formatNumber(val, decimals = 2) {
  const num = parseFloat(val || 0)
  return isNaN(num) ? '0.00' : num.toFixed(decimals)
}

function paymentBadgeClass(method) {
  const classes = {
    cash: 'bg-green-100 text-green-700',
    cheque: 'bg-blue-100 text-blue-700',
    bank_transfer: 'bg-purple-100 text-purple-700',
    card: 'bg-orange-100 text-orange-700',
  }
  return classes[method] ?? 'bg-gray-100 text-gray-700'
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/expenses', {
      params: { search: search.value, ...filters }
    })
    expenses.value = data.data || data

    const { data: summaryData } = await axios.get('/api/expenses-summary', { params: filters })
    summary.value = summaryData
  } catch (e) {
    console.error('Failed to load expenses:', e)
  } finally {
    loading.value = false
  }
}

function openModal(expense) {
  editing.value = expense
  formError.value = ''
  if (expense) {
    Object.assign(form, {
      expense_date: expense.expense_date,
      expense_account_id: expense.expense_account_id,
      description: expense.description,
      amount: parseFloat(expense.amount),
      payment_method: expense.payment_method,
      reference_number: expense.reference_number ?? '',
      paid_by_user_id: expense.paid_by_user_id,
      notes: expense.notes ?? '',
    })
  } else {
    Object.assign(form, {
      expense_date: new Date().toISOString().split('T')[0],
      expense_account_id: '', description: '', amount: 0,
      payment_method: 'cash', reference_number: '', paid_by_user_id: '',
      notes: '',
    })
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/expenses/${editing.value.id}`, form)
    } else {
      await axios.post('/api/expenses', form)
    }
    showModal.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally {
    saving.value = false
  }
}

async function deleteExpense(expense) {
  if (!confirm(`Delete expense "${expense.description}"?`)) return
  try {
    await axios.delete(`/api/expenses/${expense.id}`)
    load()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error deleting expense')
  }
}

onMounted(async () => {
  const [usersRes, accountsRes] = await Promise.all([
    axios.get('/api/users?per_page=100'),
    axios.get('/api/accounts?type=expense'),
  ])
  users.value = usersRes.data.data || []
  expenseAccounts.value = (accountsRes.data || []).filter(a => a.sub_type !== 'cogs' && a.is_active !== false)
  load()
})
</script>
