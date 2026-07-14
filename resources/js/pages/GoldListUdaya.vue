<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Gold List To Udaya Boss</h1>
        <p class="text-sm text-gray-500 mt-1">Rate = Price / Weight × 8 &nbsp;|&nbsp; Subtotal every 20 records</p>
      </div>
      <button @click="openModal()" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Item
      </button>
    </div>

    <!-- Summary cards -->
    <div v-if="rows.length" class="grid grid-cols-4 gap-4">
      <div class="card p-3 text-center">
        <p class="text-xs text-gray-500 uppercase mb-1">Total Items</p>
        <p class="text-xl font-bold text-gray-800">{{ rows.length }}</p>
      </div>
      <div class="card p-3 text-center">
        <p class="text-xs text-gray-500 uppercase mb-1">Total Weight</p>
        <p class="text-xl font-bold text-blue-700">{{ r3(grandTotals.weight) }} g</p>
      </div>
      <div class="card p-3 text-center">
        <p class="text-xs text-gray-500 uppercase mb-1">Give Weight</p>
        <p class="text-xl font-bold text-green-700">{{ r3(grandTotals.giveWeight) }} g</p>
      </div>
      <div class="card p-3 text-center border-2 border-amber-400">
        <p class="text-xs text-gray-500 uppercase mb-1">Avg Rate</p>
        <p class="text-xl font-bold text-amber-700">{{ r4(grandTotals.rate) }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-4 items-end">
      <div>
        <label class="form-label">From</label>
        <input v-model="filters.date_from" type="date" class="form-input" @change="fetch" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="filters.date_to" type="date" class="form-input" @change="fetch" />
      </div>
      <div>
        <label class="form-label">Search</label>
        <input v-model="search" @input="fetchDebounced" type="text" placeholder="Search item or description…"
          class="form-input w-44" />
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm border-collapse">
        <thead>
          <tr class="bg-gray-900 text-white text-xs uppercase">
            <th class="px-3 py-3 text-center border border-gray-700 w-12">No</th>
            <th class="px-3 py-3 text-left border border-gray-700">Item</th>
            <th class="px-3 py-3 text-left border border-gray-700">Description</th>
            <th class="px-3 py-3 text-right border border-gray-700">Weight</th>
            <th class="px-3 py-3 text-right border border-gray-700">Stock Weight</th>
            <th class="px-3 py-3 text-right border border-gray-700">Net Weight</th>
            <th class="px-3 py-3 text-right border border-gray-700">Price</th>
            <th class="px-3 py-3 text-right border border-gray-700">Rate</th>
            <th class="px-3 py-3 text-center border border-gray-700">Avg Karat</th>
            <th class="px-3 py-3 text-right border border-gray-700">Moose Pay</th>
            <th class="px-3 py-3 text-center border border-gray-700">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="11" class="text-center py-8 text-gray-400">Loading…</td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="11" class="text-center py-8 text-gray-400">No records found.</td>
          </tr>
          <template v-else v-for="(row, idx) in rows" :key="row.id">
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
              <td class="px-3 py-2 border border-gray-200 text-center text-gray-500">{{ idx + 1 }}</td>
              <td class="px-3 py-2 border border-gray-200 font-medium">{{ row.item }}</td>
              <td class="px-3 py-2 border border-gray-200 text-gray-600">{{ row.description ?? '—' }}</td>
              <td class="px-3 py-2 border border-gray-200 text-right">{{ row.weight ?? '—' }}</td>
              <td class="px-3 py-2 border border-gray-200 text-right">{{ row.stock_weight ?? '—' }}</td>
              <td class="px-3 py-2 border border-gray-200 text-right">{{ r3((row.weight ?? 0) - (row.stock_weight ?? 0)) }}</td>
              <td class="px-3 py-2 border border-gray-200 text-right">{{ row.price ? fmt(row.price) : '—' }}</td>
              <td class="px-3 py-2 border border-gray-200 text-right text-amber-700">{{ row.rate ? r4(row.rate) : '—' }}</td>
              <td class="px-3 py-2 border border-gray-200 text-center">{{ row.average_karat ?? '—' }}</td>
              <td class="px-3 py-2 border border-gray-200 text-right">{{ row.moose_pay ? fmt(row.moose_pay) : '—' }}</td>
              <td class="px-3 py-2 border border-gray-200 text-center">
                <div class="flex items-center justify-center gap-1">
                  <button @click="openModal(row)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button @click="confirmDelete(row)" class="p-1.5 text-red-500 hover:bg-red-50 rounded">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <!-- Subtotal every 20 rows -->
            <tr v-if="(idx + 1) % 20 === 0 && idx + 1 < rows.length"
              class="bg-blue-900 text-white text-xs font-bold">
              <td class="px-3 py-2 border border-blue-700 text-center" colspan="2">
                Subtotal (rows {{ idx - 18 }}–{{ idx + 1 }})
              </td>
              <td class="px-3 py-2 border border-blue-700 text-center">Give</td>
              <td class="px-3 py-2 border border-blue-700 text-right">
                {{ r3(batchTotal(idx, 'weight')) }}
              </td>
              <td class="px-3 py-2 border border-blue-700 text-right">
                {{ r3(batchTotal(idx, 'stock_weight')) }}
              </td>
              <td class="px-3 py-2 border border-blue-700 text-right">
                {{ r3(batchTotal(idx, 'weight') - batchTotal(idx, 'stock_weight')) }}
              </td>
              <td class="px-3 py-2 border border-blue-700"></td>
              <td class="px-3 py-2 border border-blue-700 text-right">
                {{ batchRate(idx) }}
              </td>
              <td class="px-3 py-2 border border-blue-700"></td>
              <td class="px-3 py-2 border border-blue-700 text-right">{{ r3(batchTotal(idx, 'moose_pay')) }}</td>
              <td class="px-3 py-2 border border-blue-700"></td>
            </tr>
          </template>
        </tbody>

        <!-- Grand total -->
        <tfoot v-if="rows.length">
          <tr class="bg-gray-900 text-white font-bold text-sm">
            <td class="px-3 py-3 border border-gray-700" colspan="2">Total</td>
            <td class="px-3 py-3 border border-gray-700 text-center">Give</td>
            <td class="px-3 py-3 border border-gray-700 text-right">{{ r3(grandTotals.weight) }}</td>
            <td class="px-3 py-3 border border-gray-700 text-right">{{ r3(grandTotals.stockWeight) }}</td>
            <td class="px-3 py-3 border border-gray-700 text-right">{{ r3(grandTotals.giveWeight) }}</td>
            <td class="px-3 py-3 border border-gray-700 text-right">{{ fmt(grandTotals.price) }}</td>
            <td class="px-3 py-3 border border-gray-700 text-right text-amber-300">{{ r4(grandTotals.rate) }}</td>
            <td class="px-3 py-3 border border-gray-700"></td>
            <td class="px-3 py-3 border border-gray-700 text-right">{{ r3(grandTotals.moosePay) }}</td>
            <td class="px-3 py-3 border border-gray-700"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
          <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-bold">{{ editing ? 'Edit Item' : 'Add Item' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="save" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="form-label">Item *</label>
                <input v-model="form.item" type="text" required class="form-input" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Description</label>
                <input v-model="form.description" type="text" class="form-input" placeholder="e.g. madawachchiya" />
              </div>
              <div>
                <label class="form-label">Weight (g)</label>
                <input v-model="form.weight" type="number" step="0.001" min="0" class="form-input" @input="calcRate" />
              </div>
              <div>
                <label class="form-label">Stock Weight (g)</label>
                <input v-model="form.stock_weight" type="number" step="0.001" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Price (LKR)</label>
                <input v-model="form.price" type="number" step="0.01" min="0" class="form-input" @input="calcRate" />
              </div>
              <div>
                <label class="form-label">Rate (auto)</label>
                <input :value="form.rate ? r4(form.rate) : ''" type="text" readonly
                  class="form-input bg-amber-50 text-amber-700 font-semibold" />
              </div>
              <div>
                <label class="form-label">Average Karat</label>
                <input v-model="form.average_karat" type="text" class="form-input" placeholder="e.g. 14KT, 18Kt" />
              </div>
              <div>
                <label class="form-label">Moose Pay</label>
                <input v-model="form.moose_pay" type="number" step="0.01" min="0" class="form-input" />
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
          <h3 class="text-lg font-bold">Delete Item?</h3>
          <p class="text-sm text-gray-600">Delete <strong>{{ deleteTarget.item }}</strong>?</p>
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

const _today = new Date().toISOString().slice(0, 10)
const _from30 = (() => { const d = new Date(); d.setDate(d.getDate() - 30); return d.toISOString().slice(0, 10) })()

const rows    = ref([])
const loading = ref(false)
const search  = ref('')
const filters = reactive({ date_from: _from30, date_to: _today })

const showModal    = ref(false)
const editing      = ref(null)
const saving       = ref(false)
const formError    = ref('')
const deleteTarget = ref(null)
const deleting     = ref(false)

const emptyForm = () => ({
  item: '', description: '', weight: '', stock_weight: '',
  price: '', rate: '', average_karat: '', moose_pay: '',
})
const form = reactive(emptyForm())

function fmt(v)  { return Number(v || 0).toLocaleString() }
function r3(v)   { return Math.round((v || 0) * 1000) / 1000 }
function r4(v)   { return Math.round((v || 0) * 10000) / 10000 }

// batch helpers — idx is 0-based index of last row in batch
function batchStart(idx) { return idx - (idx % 20) }
function batchTotal(idx, field) {
  const start = batchStart(idx)
  return rows.value.slice(start, idx + 1).reduce((s, r) => s + (r[field] ?? 0), 0)
}
function batchRate(idx) {
  const w = batchTotal(idx, 'weight')
  const p = batchTotal(idx, 'price')
  return w > 0 ? r4(p / w * 8) : '—'
}

const grandTotals = computed(() => {
  const weight      = rows.value.reduce((s, r) => s + (r.weight      ?? 0), 0)
  const stockWeight = rows.value.reduce((s, r) => s + (r.stock_weight ?? 0), 0)
  const price       = rows.value.reduce((s, r) => s + (r.price       ?? 0), 0)
  const moosePay    = rows.value.reduce((s, r) => s + (r.moose_pay   ?? 0), 0)
  const giveWeight  = weight - stockWeight
  const rate        = weight > 0 ? r4(price / weight * 8) : 0
  return { weight, stockWeight, giveWeight, price, rate, moosePay }
})

function calcRate() {
  if (form.weight && form.price) {
    form.rate = Math.round(form.price / form.weight * 8 * 10000) / 10000
  } else {
    form.rate = ''
  }
}

let debounceTimer = null
function fetchDebounced() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetch(), 300)
}

async function fetch() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/gold-list-udaya', { params: { search: search.value, ...filters } })
    rows.value = data
  } finally {
    loading.value = false
  }
}

function openModal(row = null) {
  formError.value = ''
  if (row) {
    editing.value = row.id
    Object.assign(form, {
      item: row.item, description: row.description ?? '',
      weight: row.weight ?? '', stock_weight: row.stock_weight ?? '',
      price: row.price ?? '', rate: row.rate ?? '',
      average_karat: row.average_karat ?? '', moose_pay: row.moose_pay ?? '',
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
      await axios.put(`/api/gold-list-udaya/${editing.value}`, form)
    } else {
      await axios.post('/api/gold-list-udaya', form)
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
    await axios.delete(`/api/gold-list-udaya/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetch()
  } finally {
    deleting.value = false
  }
}

onMounted(() => fetch())
</script>
