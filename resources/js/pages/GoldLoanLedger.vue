<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Udaya Loan Summary</h1>
        <p class="text-sm text-gray-500 mt-1">Weight = Loan Amount / Loan Rate × 8 &nbsp;|&nbsp; Balance = prev + Weight − Give Weight</p>
      </div>
      <button @click="openModal()" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Entry
      </button>
    </div>

    <!-- Summary Cards -->
    <div v-if="rows.length" class="grid grid-cols-3 gap-4">
      <div class="card p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Weight In</p>
        <p class="text-2xl font-bold text-blue-700">{{ round3(rows.reduce((s,r)=>s+(r.weight??0),0)) }} g</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Given Back</p>
        <p class="text-2xl font-bold text-red-600">{{ round3(rows.reduce((s,r)=>s+(r.give_weight??0),0)) }} g</p>
      </div>
      <div class="card p-4 text-center border-2"
        :class="finalBalance > 0 ? 'border-green-400' : finalBalance < 0 ? 'border-red-400' : 'border-gray-300'">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Current Balance</p>
        <p class="text-3xl font-bold" :class="finalBalance > 0 ? 'text-green-700' : finalBalance < 0 ? 'text-red-600' : 'text-gray-700'">
          {{ finalBalance }} g
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="form-label">From</label>
        <input v-model="filters.date_from" type="date" class="form-input" @change="fetch" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="filters.date_to" type="date" class="form-input" @change="fetch" />
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm border-collapse">
        <thead>
          <tr class="bg-gray-900 text-white text-xs uppercase">
            <th class="px-3 py-3 text-left border border-gray-700">Date</th>
            <th class="px-3 py-3 text-right border border-gray-700">Loan Rate</th>
            <th class="px-3 py-3 text-right border border-gray-700">Loan Amount</th>
            <th class="px-3 py-3 text-right border border-gray-700">Weight (g)</th>
            <th class="px-3 py-3 text-left border border-gray-700">Description</th>
            <th class="px-3 py-3 text-right border border-gray-700">Give Weight (g)</th>
            <th class="px-3 py-3 text-right border border-gray-700">Total Balance</th>
            <th class="px-3 py-3 text-center border border-gray-700">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="8" class="text-center py-8 text-gray-400">Loading…</td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="8" class="text-center py-8 text-gray-400">No entries found.</td>
          </tr>
          <tr v-for="row in rows" :key="row.id"
            class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
            :class="row.description ? 'bg-amber-50' : ''">
            <td class="px-3 py-2 border border-gray-200 whitespace-nowrap">{{ fmtDate(row.entry_date) }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.loan_rate ? fmt(row.loan_rate) : '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.loan_amount ? fmt(row.loan_amount) : '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right font-medium">{{ row.weight ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-amber-700 font-medium">{{ row.description ?? '' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right text-red-600 font-medium">{{ row.give_weight ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right font-bold"
              :class="row.total_balance > 0 ? 'text-green-700' : row.total_balance < 0 ? 'text-red-600' : ''">
              {{ row.total_balance }}
            </td>
            <td class="px-3 py-2 border border-gray-200 text-center">
              <div class="flex items-center justify-center gap-1">
                <button @click="openModal(row)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit">
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button @click="confirmDelete(row)" class="p-1.5 text-red-500 hover:bg-red-50 rounded" title="Delete">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
        <tfoot v-if="rows.length">
          <tr class="bg-gray-100 font-bold text-sm border-t-2 border-gray-400">
            <td class="px-3 py-2 border border-gray-300" colspan="2">Totals</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ fmt(rows.reduce((s,r)=>s+(r.loan_amount??0),0)) }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ round3(rows.reduce((s,r)=>s+(r.weight??0),0)) }}</td>
            <td class="px-3 py-2 border border-gray-300"></td>
            <td class="px-3 py-2 border border-gray-300 text-right text-red-600">{{ round3(rows.reduce((s,r)=>s+(r.give_weight??0),0)) }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right"
              :class="finalBalance > 0 ? 'text-green-700' : finalBalance < 0 ? 'text-red-600' : ''">
              {{ finalBalance }}
            </td>
            <td class="px-3 py-2 border border-gray-300"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
          <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-bold">{{ editing ? 'Edit Entry' : 'Add Entry' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="save" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="form-label">Date *</label>
                <input v-model="form.entry_date" type="date" required class="form-input" />
              </div>

              <!-- Loan entry -->
              <div>
                <label class="form-label">Loan Rate</label>
                <input v-model="form.loan_rate" type="number" step="1000" min="0" class="form-input"
                  @input="calcWeight" placeholder="e.g. 382000" />
              </div>
              <div>
                <label class="form-label">Loan Amount</label>
                <input v-model="form.loan_amount" type="number" step="1000" min="0" class="form-input"
                  @input="calcWeight" placeholder="e.g. 1000000" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Weight (g) — auto from Loan Rate &amp; Amount</label>
                <input v-model="form.weight" type="number" step="0.001" class="form-input"
                  :class="autoWeight ? 'bg-yellow-50' : ''"
                  placeholder="or enter manually" />
                <p v-if="autoWeight" class="text-xs text-amber-600 mt-1">Auto: {{ form.loan_amount }} / {{ form.loan_rate }} × 8 = {{ form.weight }}</p>
              </div>

              <!-- Payment / description row -->
              <div>
                <label class="form-label">Description</label>
                <input v-model="form.description" type="text" class="form-input"
                  placeholder="e.g. Moose Pay, Balance Forward" />
              </div>
              <div>
                <label class="form-label">Give Weight (g)</label>
                <input v-model="form.give_weight" type="number" step="0.001" min="0" class="form-input" />
              </div>

            </div>

            <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Saving…' : (editing ? 'Update' : 'Add') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirm -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 space-y-4">
          <h3 class="text-lg font-bold">Delete Entry?</h3>
          <p class="text-sm text-gray-600">This will recalculate all running balances after deletion.</p>
          <div class="flex justify-end gap-3">
            <button @click="deleteTarget = null" class="btn-secondary">Cancel</button>
            <button @click="doDelete" :disabled="deleting" class="btn-danger">{{ deleting ? 'Deleting…' : 'Delete' }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const rows    = ref([])
const loading = ref(false)
const _today = new Date().toISOString().slice(0, 10)
const _from30 = (() => { const d = new Date(); d.setDate(d.getDate() - 30); return d.toISOString().slice(0, 10) })()
const filters = reactive({ date_from: _from30, date_to: _today })

const showModal = ref(false)
const editing   = ref(null)
const saving    = ref(false)
const formError = ref('')
const deleteTarget = ref(null)
const deleting     = ref(false)
const autoWeight   = ref(false)

const emptyForm = () => ({
  entry_date: new Date().toISOString().slice(0, 10),
  loan_rate: '', loan_amount: '', weight: '',
  description: '', give_weight: '',
})
const form = reactive(emptyForm())

const finalBalance = computed(() =>
  rows.value.length ? rows.value[rows.value.length - 1].total_balance : 0
)


function fmt(v)    { return Number(v || 0).toLocaleString() }
function round3(v) { return Math.round(v * 1000) / 1000 }
function fmtDate(v){ return v ? v.slice(0, 10).replace(/-/g, '.') : '—' }

function calcWeight() {
  if (form.loan_rate && form.loan_amount) {
    form.weight  = Math.round(form.loan_amount / form.loan_rate * 8 * 1000) / 1000
    autoWeight.value = true
  } else {
    autoWeight.value = false
  }
}

async function fetch() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/gold-loan-ledger', { params: filters })
    rows.value = data
  } finally {
    loading.value = false
  }
}

function openModal(row = null) {
  formError.value  = ''
  autoWeight.value = false
  if (row) {
    editing.value = row.id
    Object.assign(form, {
      entry_date:  row.entry_date?.slice(0, 10) ?? '',
      loan_rate:   row.loan_rate   ?? '',
      loan_amount: row.loan_amount ?? '',
      weight:      row.weight      ?? '',
      description: row.description ?? '',
      give_weight: row.give_weight ?? '',
    })
  } else {
    editing.value = null
    Object.assign(form, emptyForm())
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  saving.value = true; formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/gold-loan-ledger/${editing.value}`, form)
    } else {
      await axios.post('/api/gold-loan-ledger', form)
    }
    closeModal()
    fetch()
  } catch (e) {
    formError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Failed to save.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) { deleteTarget.value = row }
async function doDelete() {
  deleting.value = true
  try {
    await axios.delete(`/api/gold-loan-ledger/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetch()
  } finally {
    deleting.value = false
  }
}

onMounted(() => fetch())
</script>
