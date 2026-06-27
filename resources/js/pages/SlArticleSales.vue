<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">S.L Article Sales</h1>
        <p class="text-sm text-gray-500 mt-1">Private Gold Book — Sales</p>
      </div>
      <button @click="openModal()" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Sale
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="form-label">Gold Rate (per sovereign)</label>
        <input v-model.number="defaultGoldRate" type="number" step="1000" min="0"
          class="form-input w-40" @change="recalcDisplay" />
      </div>
      <div>
        <label class="form-label">From</label>
        <input v-model="filters.date_from" type="date" class="form-input" @change="fetch(1)" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="filters.date_to" type="date" class="form-input" @change="fetch(1)" />
      </div>
      <div>
        <label class="form-label">Search Article</label>
        <input v-model="filters.search" type="text" class="form-input w-44" @input="fetchDebounced" placeholder="Article name…" />
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-900 text-white text-xs uppercase">
            <th class="px-3 py-3 text-left">Date</th>
            <th class="px-3 py-3 text-center w-12">No</th>
            <th class="px-3 py-3 text-left">Article</th>
            <th class="px-3 py-3 text-right">Weight</th>
            <th class="px-3 py-3 text-right">By Price</th>
            <th class="px-3 py-3 text-right">Sale Price</th>
            <th class="px-3 py-3 text-right">Profit</th>
            <th class="px-3 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="8" class="text-center py-8 text-gray-400">Loading…</td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="8" class="text-center py-8 text-gray-400">No records found.</td>
          </tr>
          <tr v-for="(row, idx) in rows" :key="row.id"
            class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
            :class="profitClass(row.profit)">
            <td class="px-3 py-2 whitespace-nowrap">{{ fmtDate(row.sale_date) }}</td>
            <td class="px-3 py-2 text-center text-gray-500">{{ pagination.from + idx }}</td>
            <td class="px-3 py-2 font-medium">{{ row.article }}</td>
            <td class="px-3 py-2 text-right">{{ row.weight ?? '—' }}</td>
            <td class="px-3 py-2 text-right">{{ fmt(row.by_price) }}</td>
            <td class="px-3 py-2 text-right font-semibold">{{ row.sale_price ? fmt(row.sale_price) : '—' }}</td>
            <td class="px-3 py-2 text-right font-semibold"
              :class="row.profit > 0 ? 'text-green-600' : row.profit < 0 ? 'text-red-600' : 'text-gray-500'">
              {{ fmt(row.profit) }}
            </td>
            <td class="px-3 py-2 text-center">
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
        <!-- Totals row -->
        <tfoot v-if="rows.length">
          <tr class="bg-gray-100 font-bold text-sm border-t-2 border-gray-300">
            <td class="px-3 py-2" colspan="4">Totals</td>
            <td class="px-3 py-2 text-right">{{ fmt(totals.by_price) }}</td>
            <td class="px-3 py-2 text-right">{{ fmt(totals.sale_price) }}</td>
            <td class="px-3 py-2 text-right" :class="totals.profit >= 0 ? 'text-green-700' : 'text-red-700'">
              {{ fmt(totals.profit) }}
            </td>
            <td></td>
          </tr>
        </tfoot>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <p class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
        <div class="flex gap-2">
          <button @click="goPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
            class="px-3 py-1 text-sm border rounded disabled:opacity-40">Prev</button>
          <button @click="goPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 text-sm border rounded disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
          <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-bold">{{ editing ? 'Edit Sale' : 'Add Sale' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="save" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Date *</label>
                <input v-model="form.sale_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Article *</label>
                <input v-model="form.article" type="text" required class="form-input" placeholder="e.g. Flat Chain" />
              </div>
              <div>
                <label class="form-label">Weight (g)</label>
                <input v-model="form.weight" type="number" step="0.001" min="0" class="form-input"
                  @input="calcByPrice" />
              </div>
              <div>
                <label class="form-label">Gold Rate (per sovereign)</label>
                <input v-model="form.gold_rate" type="number" step="1000" min="0" class="form-input"
                  @input="calcByPrice" />
              </div>
              <div>
                <label class="form-label">By Price (auto)</label>
                <input :value="fmt(form.by_price)" type="text" readonly class="form-input bg-gray-50 text-gray-600" />
              </div>
              <div>
                <label class="form-label">Sale Price</label>
                <input v-model="form.sale_price" type="number" step="0.01" min="0" class="form-input"
                  @input="calcProfit" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Profit</label>
                <input v-model.number="form.profit" type="number" step="1" class="form-input font-semibold"
                  :class="form.profit > 0 ? 'text-green-600' : form.profit < 0 ? 'text-red-600' : ''" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Notes</label>
                <textarea v-model="form.notes" class="form-input" rows="2" />
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
          <h3 class="text-lg font-bold">Delete Sale Record?</h3>
          <p class="text-sm text-gray-600">Delete <strong>{{ deleteTarget.article }}</strong>?</p>
          <div class="flex justify-end gap-3">
            <button @click="deleteTarget = null" class="btn-secondary">Cancel</button>
            <button @click="doDelete" :disabled="deleting" class="btn-danger">
              {{ deleting ? 'Deleting…' : 'Delete' }}
            </button>
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

const rows            = ref([])
const loading         = ref(false)
const defaultGoldRate = ref(280000)
const pagination      = ref({ current_page: 1, last_page: 1, from: 1 })
const _today = new Date().toISOString().slice(0, 10)
const _from30 = (() => { const d = new Date(); d.setDate(d.getDate() - 30); return d.toISOString().slice(0, 10) })()
const filters         = reactive({ date_from: _from30, date_to: _today, search: '' })

const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formError  = ref('')
const deleteTarget = ref(null)
const deleting     = ref(false)

const emptyForm = () => ({
  sale_date: new Date().toISOString().slice(0, 10),
  article: '', weight: '', gold_rate: defaultGoldRate.value,
  by_price: 0, sale_price: '', profit: 0, notes: '',
})
const form = reactive(emptyForm())

const totals = computed(() => ({
  by_price:   rows.value.reduce((s, r) => s + (r.by_price  ?? 0), 0),
  sale_price: rows.value.reduce((s, r) => s + (r.sale_price ?? 0), 0),
  profit:     rows.value.reduce((s, r) => s + (r.profit    ?? 0), 0),
}))

function fmt(v) { return Number(v || 0).toLocaleString() }
function fmtDate(v) { return v ? v.slice(0, 10).replace(/-/g, '.') : '—' }
function profitClass(p) { return p > 0 ? '' : p < 0 ? 'bg-red-50/40' : '' }

function calcByPrice() {
  if (form.weight && form.gold_rate) {
    form.by_price = Math.round(form.gold_rate / 8 * form.weight)
  } else {
    form.by_price = 0
  }
  calcProfit()
}
function calcProfit() {
  form.profit = Math.round((form.sale_price || 0) - form.by_price)
}

let debounceTimer = null
function fetchDebounced() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetch(1), 300)
}

async function fetch(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/sl-article-sales', {
      params: { ...filters, page }
    })
    rows.value       = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page, from: data.from ?? 1 }
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  if (p >= 1 && p <= pagination.value.last_page) fetch(p)
}

function openModal(row = null) {
  formError.value = ''
  if (row) {
    editing.value = row.id
    Object.assign(form, {
      sale_date: row.sale_date?.slice(0, 10) ?? '',
      article: row.article, weight: row.weight ?? '',
      gold_rate: row.gold_rate ?? defaultGoldRate.value,
      by_price: row.by_price ?? 0,
      sale_price: row.sale_price ?? '', profit: row.profit ?? 0,
      notes: row.notes ?? '',
    })
  } else {
    editing.value = null
    Object.assign(form, emptyForm())
    form.gold_rate = defaultGoldRate.value
    calcByPrice()
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  saving.value = true; formError.value = ''
  try {
    const payload = { ...form }
    if (editing.value) {
      await axios.put(`/api/sl-article-sales/${editing.value}`, payload)
    } else {
      await axios.post('/api/sl-article-sales', payload)
    }
    closeModal()
    fetch(pagination.value.current_page)
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
    await axios.delete(`/api/sl-article-sales/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetch(pagination.value.current_page)
  } finally {
    deleting.value = false
  }
}

onMounted(() => fetch())
</script>
