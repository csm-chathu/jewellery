<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Account Transfers</h2>
        <p class="text-sm text-gray-500 mt-0.5">Move money between accounts (e.g. Cash → Savings)</p>
      </div>
      <button @click="openNew"
        class="flex items-center gap-2 px-4 py-2 bg-gold-600 text-white rounded-lg hover:bg-gold-700 text-sm font-medium transition-colors">
        <PlusIcon class="w-4 h-4" />
        New Transfer
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">From Date</label>
        <input v-model="filters.from" type="date"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-gold-500 focus:border-gold-500 outline-none" />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">To Date</label>
        <input v-model="filters.to" type="date"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-gold-500 focus:border-gold-500 outline-none" />
      </div>
      <button @click="loadTransfers"
        class="px-4 py-1.5 bg-gray-700 text-white rounded-lg text-sm hover:bg-gray-800 transition-colors">
        Filter
      </button>
      <button @click="resetFilters"
        class="px-4 py-1.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors">
        Reset
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16 text-gray-400">
        <div class="animate-spin w-6 h-6 border-2 border-gold-500 border-t-transparent rounded-full mr-2"></div>
        Loading transfers…
      </div>

      <template v-else-if="transfers.length">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">Ref #</th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">From</th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">To</th>
              <th class="text-right px-4 py-3 font-semibold text-gray-600">Amount</th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">Note</th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">By</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="t in transfers" :key="t.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ t.entry_number }}</td>
              <td class="px-4 py-3 text-gray-700">{{ fmtDate(t.entry_date) }}</td>
              <td class="px-4 py-3">
                <span class="text-xs bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded-full font-medium">
                  {{ fromAccount(t)?.name ?? '—' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="text-xs bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full font-medium">
                  {{ toAccount(t)?.name ?? '—' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-gray-800">
                {{ fmtMoney(t.lines_sum_debit) }}
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate">{{ t.description }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ t.created_by?.name ?? '—' }}</td>
              <td class="px-4 py-3 text-right">
                <button @click="confirmDelete(t)"
                  class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  title="Delete transfer">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1"
          class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm text-gray-500">
          <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
          <div class="flex gap-2">
            <button @click="goPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
              class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
              Prev
            </button>
            <button @click="goPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
              Next
            </button>
          </div>
        </div>
      </template>

      <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
        <ArrowsRightLeftIcon class="w-10 h-10 mb-3 opacity-40" />
        <p class="text-sm">No transfers yet.</p>
        <p class="text-xs mt-1">Click "New Transfer" to move money between accounts.</p>
      </div>
    </div>
  </div>

  <!-- New Transfer Modal -->
  <Teleport to="body">
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-bold text-gray-800">New Account Transfer</h3>
          <button @click="showModal = false" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitTransfer" class="px-6 py-5 space-y-4">
          <!-- From Account -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From Account <span class="text-red-500">*</span></label>
            <select v-model="form.from_account_id" required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gold-500 focus:border-gold-500 outline-none">
              <option value="">— select account —</option>
              <option v-for="a in accounts" :key="a.id" :value="a.id">
                {{ a.code }} – {{ a.name }}
              </option>
            </select>
          </div>

          <!-- Arrow indicator -->
          <div class="flex items-center justify-center">
            <div class="flex items-center gap-2 text-xs text-gray-400">
              <span class="w-12 h-px bg-gray-300"></span>
              <ArrowsRightLeftIcon class="w-4 h-4 text-gold-500" />
              <span class="w-12 h-px bg-gray-300"></span>
            </div>
          </div>

          <!-- To Account -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To Account <span class="text-red-500">*</span></label>
            <select v-model="form.to_account_id" required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gold-500 focus:border-gold-500 outline-none">
              <option value="">— select account —</option>
              <option v-for="a in accounts" :key="a.id" :value="a.id" :disabled="a.id === form.from_account_id">
                {{ a.code }} – {{ a.name }}
              </option>
            </select>
          </div>

          <!-- Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
            <input v-model.number="form.amount" type="number" min="0.01" step="0.01" required
              placeholder="0.00"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gold-500 focus:border-gold-500 outline-none" />
          </div>

          <!-- Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
            <input v-model="form.entry_date" type="date" required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gold-500 focus:border-gold-500 outline-none" />
          </div>

          <!-- Note -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model="form.description" type="text" maxlength="255"
              placeholder="e.g. Weekly savings deposit"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-gold-500 focus:border-gold-500 outline-none" />
          </div>

          <!-- Summary -->
          <div v-if="form.from_account_id && form.to_account_id && form.amount"
            class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
            <span class="font-semibold">{{ accountName(form.from_account_id) }}</span>
            → <span class="font-semibold">{{ accountName(form.to_account_id) }}</span>
            &nbsp;|&nbsp; <span class="font-semibold">{{ fmtMoney(form.amount) }}</span>
          </div>

          <p v-if="formError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ formError }}</p>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
              Cancel
            </button>
            <button type="submit" :disabled="saving"
              class="flex-1 px-4 py-2 bg-gold-600 text-white rounded-lg text-sm font-medium hover:bg-gold-700 disabled:opacity-50 transition-colors">
              {{ saving ? 'Saving…' : 'Save Transfer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Delete Confirm Modal -->
  <Teleport to="body">
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <h3 class="text-base font-bold text-gray-800 mb-2">Delete Transfer?</h3>
        <p class="text-sm text-gray-500 mb-5">
          This will reverse the journal entry for
          <span class="font-semibold">{{ deleteTarget.entry_number }}</span>.
          This action cannot be undone.
        </p>
        <div class="flex gap-3">
          <button @click="deleteTarget = null"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
            Cancel
          </button>
          <button @click="doDelete" :disabled="deleting"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 disabled:opacity-50 transition-colors">
            {{ deleting ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import {
  PlusIcon, TrashIcon, XMarkIcon, ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'

const transfers  = ref([])
const accounts   = ref([])
const loading    = ref(false)
const showModal  = ref(false)
const saving     = ref(false)
const formError  = ref('')
const deleteTarget = ref(null)
const deleting   = ref(false)
const pagination = reactive({ current_page: 1, last_page: 1 })

const filters = reactive({ from: '', to: '' })

const form = reactive({
  from_account_id: '',
  to_account_id:   '',
  amount:          '',
  entry_date:      new Date().toISOString().slice(0, 10),
  description:     '',
})

async function loadTransfers(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/account-transfers', {
      params: { ...filters, page },
    })
    transfers.value = data.data
    pagination.current_page = data.current_page
    pagination.last_page    = data.last_page
  } finally {
    loading.value = false
  }
}

async function loadAccounts() {
  const { data } = await axios.get('/api/accounts/all')
  accounts.value = data
}

function resetFilters() {
  filters.from = ''
  filters.to   = ''
  loadTransfers()
}

function goPage(p) {
  if (p < 1 || p > pagination.last_page) return
  loadTransfers(p)
}

function openNew() {
  formError.value = ''
  form.from_account_id = ''
  form.to_account_id   = ''
  form.amount          = ''
  form.entry_date      = new Date().toISOString().slice(0, 10)
  form.description     = ''
  showModal.value = true
}

async function submitTransfer() {
  formError.value = ''
  if (!form.from_account_id || !form.to_account_id || !form.amount || !form.entry_date) {
    formError.value = 'Please fill in all required fields.'
    return
  }
  if (form.from_account_id === form.to_account_id) {
    formError.value = 'From and To accounts must be different.'
    return
  }
  saving.value = true
  try {
    await axios.post('/api/account-transfers', {
      from_account_id: form.from_account_id,
      to_account_id:   form.to_account_id,
      amount:          form.amount,
      entry_date:      form.entry_date,
      description:     form.description || undefined,
    })
    showModal.value = false
    loadTransfers()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save transfer.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(t) {
  deleteTarget.value = t
}

async function doDelete() {
  deleting.value = true
  try {
    await axios.delete(`/api/account-transfers/${deleteTarget.value.id}`)
    deleteTarget.value = null
    loadTransfers(pagination.current_page)
  } finally {
    deleting.value = false
  }
}

function fromAccount(t) {
  return t.lines?.find(l => l.credit > 0)?.account
}
function toAccount(t) {
  return t.lines?.find(l => l.debit > 0)?.account
}
function accountName(id) {
  return accounts.value.find(a => a.id === Number(id))?.name ?? ''
}

function fmtMoney(v) {
  return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  loadTransfers()
  loadAccounts()
})
</script>
