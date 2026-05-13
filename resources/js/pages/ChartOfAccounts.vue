<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Chart of Accounts</h2>
        <p class="text-sm text-gray-500 mt-0.5">Manage your accounts structure — assets, liabilities, equity, revenues & expenses</p>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Account
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <input v-model="search" type="search" placeholder="Search code or name…" class="form-input w-56" @input="debouncedFetch" />
      <div class="flex gap-1">
        <button v-for="t in typeFilters" :key="t.value"
          @click="typeFilter = typeFilter === t.value ? '' : t.value; fetch()"
          :class="['inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors',
            typeFilter === t.value ? t.active : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50']">
          {{ t.label }}
        </button>
      </div>
    </div>

    <!-- Accounts grouped by type -->
    <div v-for="group in groupedAccounts" :key="group.type" class="card p-0 overflow-hidden">
      <div :class="['px-5 py-3 border-b flex items-center gap-3', group.headerClass]">
        <component :is="group.icon" class="w-5 h-5" />
        <h3 class="font-semibold text-sm uppercase tracking-wider">{{ group.label }}</h3>
        <span class="ml-auto text-xs font-medium opacity-70">{{ group.accounts.length }} accounts</span>
      </div>
      <table class="w-full">
        <thead class="bg-gray-50 border-b text-xs">
          <tr>
            <th class="table-th w-24">Code</th>
            <th class="table-th">Name</th>
            <th class="table-th w-36">Sub-type</th>
            <th class="table-th w-24 text-center">Status</th>
            <th class="table-th w-28 text-center">System</th>
            <th class="table-th w-32">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="acc in group.accounts" :key="acc.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs font-semibold text-gray-700">{{ acc.code }}</td>
            <td class="table-td font-medium text-gray-800">
              {{ acc.name }}
              <span v-if="acc.description" class="ml-2 text-xs text-gray-400 font-normal">{{ acc.description }}</span>
            </td>
            <td class="table-td text-xs text-gray-500 capitalize">{{ acc.sub_type?.replace(/_/g, ' ') ?? '—' }}</td>
            <td class="table-td text-center">
              <span :class="acc.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="badge text-xs">
                {{ acc.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="table-td text-center">
              <span v-if="acc.is_system" class="badge bg-blue-100 text-blue-700 text-xs">System</span>
              <span v-else class="text-gray-300 text-xs">—</span>
            </td>
            <td class="table-td">
              <div class="flex items-center gap-1.5">
                <button @click="openEdit(acc)"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                </button>
                <button v-if="!acc.is_system" @click="deleteAccount(acc)"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!group.accounts.length">
            <td colspan="6" class="table-td text-center text-gray-400 py-4 text-sm">No {{ group.label.toLowerCase() }} accounts</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div v-if="!loading && !accounts.length" class="card text-center py-16 text-gray-400">
      No accounts found
    </div>

    <!-- Add / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
          <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit Account' : 'New Account' }}</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        <form @submit.prevent="saveAccount" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Account Code *</label>
              <input v-model="form.code" type="text" class="form-input font-mono" placeholder="e.g. 1000" required />
            </div>
            <div>
              <label class="form-label">Type *</label>
              <select v-model="form.type" class="form-input" required :disabled="editing?.is_system">
                <option value="asset">Asset</option>
                <option value="liability">Liability</option>
                <option value="equity">Equity</option>
                <option value="revenue">Revenue</option>
                <option value="expense">Expense</option>
              </select>
            </div>
          </div>
          <div>
            <label class="form-label">Account Name *</label>
            <input v-model="form.name" type="text" class="form-input" placeholder="e.g. Cash on Hand" required />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Sub-type</label>
              <select v-model="form.sub_type" class="form-input">
                <option value="">— None —</option>
                <optgroup label="Asset">
                  <option value="current_asset">Current Asset</option>
                  <option value="fixed_asset">Fixed Asset</option>
                </optgroup>
                <optgroup label="Liability">
                  <option value="current_liability">Current Liability</option>
                  <option value="long_term_liability">Long-term Liability</option>
                </optgroup>
                <optgroup label="Equity">
                  <option value="equity">Equity</option>
                </optgroup>
                <optgroup label="Revenue">
                  <option value="operating_revenue">Operating Revenue</option>
                  <option value="other_income">Other Income</option>
                </optgroup>
                <optgroup label="Expense">
                  <option value="cogs">Cost of Goods Sold</option>
                  <option value="operating">Operating Expense</option>
                </optgroup>
              </select>
            </div>
            <div class="flex items-end pb-0.5">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_active" type="checkbox" class="rounded text-amber-500" />
                <span class="text-sm text-gray-700">Active</span>
              </label>
            </div>
          </div>
          <div>
            <label class="form-label">Description</label>
            <textarea v-model="form.description" rows="2" class="form-input" placeholder="Optional description…"></textarea>
          </div>
          <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? 'Saving…' : (editing ? 'Save Changes' : 'Create Account') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import {
  PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon,
  BanknotesIcon, ScaleIcon, BuildingLibraryIcon, ArrowTrendingUpIcon, ReceiptPercentIcon,
} from '@heroicons/vue/24/outline'

const accounts   = ref([])
const loading    = ref(false)
const search     = ref('')
const typeFilter = ref('')
const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formError  = ref('')

const defaultForm = () => ({ code: '', name: '', type: 'asset', sub_type: '', description: '', is_active: true })
const form = ref(defaultForm())

const typeFilters = [
  { value: 'asset',     label: 'Assets',      active: 'bg-blue-600 text-white border-blue-600' },
  { value: 'liability', label: 'Liabilities',  active: 'bg-red-600 text-white border-red-600' },
  { value: 'equity',    label: 'Equity',       active: 'bg-purple-600 text-white border-purple-600' },
  { value: 'revenue',   label: 'Revenue',      active: 'bg-green-600 text-white border-green-600' },
  { value: 'expense',   label: 'Expenses',     active: 'bg-amber-500 text-white border-amber-500' },
]

const typeConfig = {
  asset:     { label: 'Assets',      headerClass: 'bg-blue-50 text-blue-800',   icon: BanknotesIcon },
  liability: { label: 'Liabilities', headerClass: 'bg-red-50 text-red-800',     icon: ScaleIcon },
  equity:    { label: 'Equity',      headerClass: 'bg-purple-50 text-purple-800', icon: BuildingLibraryIcon },
  revenue:   { label: 'Revenue',     headerClass: 'bg-green-50 text-green-800', icon: ArrowTrendingUpIcon },
  expense:   { label: 'Expenses',    headerClass: 'bg-amber-50 text-amber-800', icon: ReceiptPercentIcon },
}

const groupedAccounts = computed(() => {
  const types = typeFilter.value ? [typeFilter.value] : ['asset', 'liability', 'equity', 'revenue', 'expense']
  return types.map(type => ({
    type,
    ...typeConfig[type],
    accounts: accounts.value.filter(a => a.type === type),
  })).filter(g => !typeFilter.value || g.accounts.length >= 0)
})

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(fetch, 350) }

async function fetch() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/accounts', {
      params: { search: search.value, type: typeFilter.value },
    })
    accounts.value = data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  form.value    = defaultForm()
  formError.value = ''
  showModal.value = true
}

function openEdit(acc) {
  editing.value = acc
  form.value    = { code: acc.code, name: acc.name, type: acc.type, sub_type: acc.sub_type ?? '', description: acc.description ?? '', is_active: acc.is_active }
  formError.value = ''
  showModal.value = true
}

async function saveAccount() {
  saving.value    = true
  formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/accounts/${editing.value.id}`, form.value)
    } else {
      await axios.post('/api/accounts', form.value)
    }
    showModal.value = false
    fetch()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'An error occurred.'
  } finally {
    saving.value = false
  }
}

async function deleteAccount(acc) {
  if (!confirm(`Delete account "${acc.code} – ${acc.name}"?`)) return
  try {
    await axios.delete(`/api/accounts/${acc.id}`)
    fetch()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Cannot delete this account.')
  }
}

onMounted(fetch)
</script>
