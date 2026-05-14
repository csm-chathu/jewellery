<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <WrenchScrewdriverIcon class="w-5 h-5 text-amber-500" />
          Rework / Job Orders
        </h2>
        <p class="text-sm text-gray-400 mt-0.5">Track gold rework from buy-back or scrap through to finished product</p>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Job Order
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-4 gap-4">
      <div v-for="c in summaryCards" :key="c.label" class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">{{ c.label }}</p>
        <p class="text-2xl font-bold mt-1" :class="c.color">{{ c.value }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap">
      <input v-model="filters.search" placeholder="Ref #, description, goldsmith…" class="form-input w-60" @input="load" />
      <select v-model="filters.status" class="form-input w-40" @change="load">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <button @click="filters.search=''; filters.status=''; load()" class="btn-secondary text-sm">Clear</button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Ref #</th>
            <th class="table-th">Source</th>
            <th class="table-th">Description</th>
            <th class="table-th">Goldsmith</th>
            <th class="table-th">Input</th>
            <th class="table-th">Added Gold</th>
            <th class="table-th">Making</th>
            <th class="table-th">Total Cost</th>
            <th class="table-th">Expected</th>
            <th class="table-th">Status</th>
            <th class="table-th">Output Product</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in orders.data" :key="r.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs text-gray-500">{{ r.reference_number }}</td>
            <td class="table-td text-xs">
              <span v-if="r.source_type === 'buyback'" class="badge bg-blue-100 text-blue-700">
                Buy-Back
              </span>
              <span v-else-if="r.source_type === 'scrap'" class="badge bg-orange-100 text-orange-700">
                Scrap
              </span>
              <span v-else class="badge bg-gray-100 text-gray-600">Manual</span>
              <p class="text-gray-400 mt-0.5 text-[10px]">
                {{ r.buyback?.buyback_number ?? r.scrap_item?.sku ?? '' }}
              </p>
              <p v-if="r.buyback?.customer" class="text-gray-400 text-[10px]">{{ r.buyback.customer.name }}</p>
            </td>
            <td class="table-td text-sm font-medium max-w-[160px] truncate">{{ r.description }}</td>
            <td class="table-td text-sm text-gray-500">{{ r.goldsmith_name || '—' }}</td>
            <td class="table-td text-sm font-mono">
              {{ r.input_weight }}g
              <span v-if="r.input_karat" class="text-xs text-gold-600 ml-1">{{ r.input_karat }}</span>
            </td>
            <td class="table-td text-sm font-mono">{{ r.added_gold_weight ? r.added_gold_weight + 'g' : '—' }}</td>
            <td class="table-td text-sm">{{ r.making_charge ? 'LKR ' + lkr(r.making_charge) : '—' }}</td>
            <td class="table-td font-semibold text-gold-700">LKR {{ lkr(r.total_cost) }}</td>
            <td class="table-td text-sm text-gray-500">{{ fmtDate(r.expected_at) }}</td>
            <td class="table-td">
              <span :class="statusClass(r.status)" class="badge text-xs">{{ statusLabel(r.status) }}</span>
            </td>
            <td class="table-td text-xs">
              <span v-if="r.output_product" class="text-green-700 font-medium">{{ r.output_product.name }}</span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="table-td">
              <div class="flex gap-1.5 flex-wrap">
                <button v-if="r.status !== 'completed' && r.status !== 'cancelled'"
                  @click="openEdit(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                </button>
                <button v-if="r.status !== 'completed' && r.status !== 'cancelled'"
                  @click="openComplete(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 hover:bg-green-200 whitespace-nowrap">
                  <CheckCircleIcon class="w-3.5 h-3.5" /> Complete
                </button>
                <button v-if="r.status !== 'completed'"
                  @click="del(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!orders.data?.length">
            <td colspan="12" class="table-td text-center text-gray-400 py-10">No job orders found</td>
          </tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ orders.total ?? 0 }} orders</span>
        <div class="flex gap-2">
          <button @click="page--; load()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; load()" :disabled="page>=orders.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <teleport to="body">
      <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showForm=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col">
          <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
            <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit Job Order' : 'New Job Order' }}</h3>
            <button @click="showForm=false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
          </div>
          <div class="flex-1 overflow-y-auto p-6 space-y-5">

            <!-- Source selection -->
            <div>
              <label class="form-label">Gold Source *</label>
              <div class="grid grid-cols-3 gap-2">
                <button v-for="src in ['buyback','scrap','manual']" :key="src"
                  type="button"
                  @click="form.source_type = src; form.buyback_id = null; form.scrap_item_id = null; prefillFromSource()"
                  class="py-2 px-3 rounded-xl border-2 text-sm font-medium transition-colors"
                  :class="form.source_type === src
                    ? 'border-amber-400 bg-amber-50 text-amber-700'
                    : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                  {{ src === 'buyback' ? 'Buy-Back' : src === 'scrap' ? 'Scrap Gold' : 'Manual Entry' }}
                </button>
              </div>
            </div>

            <!-- Buy-back picker -->
            <div v-if="form.source_type === 'buyback'">
              <label class="form-label">Select Buy-Back Record *</label>
              <select v-model="form.buyback_id" class="form-input" @change="prefillFromBuyback">
                <option :value="null">-- Select --</option>
                <option v-for="b in buybacks" :key="b.id" :value="b.id">
                  {{ b.buyback_number }} — {{ b.customer?.name }} | {{ b.net_weight }}g {{ b.declared_karat }} | LKR {{ lkr(b.final_price) }}
                </option>
              </select>
            </div>

            <!-- Scrap picker -->
            <div v-if="form.source_type === 'scrap'">
              <label class="form-label">Select Scrap Item *</label>
              <select v-model="form.scrap_item_id" class="form-input" @change="prefillFromScrap">
                <option :value="null">-- Select --</option>
                <option v-for="s in scraps" :key="s.id" :value="s.id">
                  {{ s.sku }} — {{ s.description }} | {{ s.weight_g }}g {{ s.karat }} | LKR {{ lkr(s.estimated_value) }}
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="form-label">Description / New Piece Name *</label>
                <input v-model="form.description" class="form-input" placeholder="e.g. 22K necklace rework from old bangle" />
              </div>
              <div>
                <label class="form-label">Goldsmith Name</label>
                <input v-model="form.goldsmith_name" class="form-input" placeholder="e.g. Sunil Goldworks" />
              </div>
              <div>
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-input">
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                </select>
              </div>
            </div>

            <!-- Input gold -->
            <div class="border rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Input Gold</p>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="form-label">Weight (g) *</label>
                  <input v-model.number="form.input_weight" type="number" min="0" step="0.001" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Karat</label>
                  <select v-model="form.input_karat" class="form-input">
                    <option value="">—</option>
                    <option v-for="k in ['9k','14k','18k','22k','24k']" :key="k" :value="k">{{ k.toUpperCase() }}</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Gold Cost (LKR) *</label>
                  <input v-model.number="form.input_gold_cost" type="number" min="0" step="0.01" class="form-input" />
                </div>
              </div>
            </div>

            <!-- Added gold -->
            <div class="border rounded-xl p-4 space-y-3 bg-amber-50/40">
              <p class="text-sm font-semibold text-gray-700">Added Gold <span class="text-xs font-normal text-gray-400">(if extra gold added)</span></p>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Added Weight (g)</label>
                  <input v-model.number="form.added_gold_weight" type="number" min="0" step="0.001" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Added Gold Cost (LKR)</label>
                  <input v-model.number="form.added_gold_cost" type="number" min="0" step="0.01" class="form-input" />
                </div>
              </div>
            </div>

            <!-- Making charge -->
            <div class="border rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Making Charge</p>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Making Charge (LKR)</label>
                  <input v-model.number="form.making_charge" type="number" min="0" step="0.01" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Notes</label>
                  <input v-model="form.making_charge_notes" class="form-input" placeholder="e.g. Goldsmith invoice #123" />
                </div>
              </div>
            </div>

            <!-- Total cost preview -->
            <div class="bg-gold-50 border border-gold-200 rounded-xl p-3 flex items-center justify-between">
              <span class="text-sm font-medium text-gray-600">Estimated Total Cost</span>
              <span class="text-lg font-bold text-gold-700">
                LKR {{ lkr((form.input_gold_cost||0) + (form.added_gold_cost||0) + (form.making_charge||0)) }}
              </span>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Start Date</label>
                <input v-model="form.started_at" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Expected Completion</label>
                <input v-model="form.expected_at" type="date" class="form-input" />
              </div>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Internal notes…"></textarea>
            </div>

            <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 shrink-0">
            <button @click="showForm=false" class="btn-secondary">Cancel</button>
            <button @click="save" :disabled="saving" class="btn-primary px-6">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </div>
      </div>

      <!-- Complete Modal -->
      <div v-if="showCompleteModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showCompleteModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[92vh] flex flex-col">
          <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
            <div>
              <h3 class="font-semibold text-gray-800">Complete Job Order</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ completing?.reference_number }}</p>
            </div>
            <button @click="showCompleteModal=false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
          </div>
          <div class="flex-1 overflow-y-auto p-6 space-y-5">

            <!-- Cost summary -->
            <div class="bg-gray-50 rounded-xl p-4 space-y-1 text-sm">
              <div class="flex justify-between text-gray-500">
                <span>Input gold cost</span>
                <span>LKR {{ lkr(completing?.input_gold_cost) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>Added gold cost</span>
                <span>LKR {{ lkr(completing?.added_gold_cost) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>Making charge (recorded)</span>
                <span>LKR {{ lkr(completing?.making_charge) }}</span>
              </div>
              <div class="flex justify-between font-bold text-gold-700 border-t pt-2 mt-2">
                <span>Total Cost (purchase price)</span>
                <span>LKR {{ lkr((completing?.input_gold_cost||0) + (completing?.added_gold_cost||0) + (completeForm.making_charge ?? completing?.making_charge ?? 0)) }}</span>
              </div>
            </div>

            <!-- Override making charge -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Final Making Charge (LKR)</label>
                <input v-model.number="completeForm.making_charge" type="number" min="0" step="0.01" class="form-input"
                  :placeholder="completing?.making_charge" />
              </div>
              <div>
                <label class="form-label">Making Charge Notes</label>
                <input v-model="completeForm.making_charge_notes" class="form-input" :placeholder="completing?.making_charge_notes || ''" />
              </div>
            </div>

            <!-- Output piece -->
            <div class="border rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Finished Piece Details</p>
              <div>
                <label class="form-label">Product Name *</label>
                <input v-model="completeForm.product_name" class="form-input" :placeholder="completing?.description" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Category</label>
                  <select v-model="completeForm.category_id" class="form-input">
                    <option :value="null">— None —</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Output Karat *</label>
                  <select v-model="completeForm.output_karat" class="form-input">
                    <option value="">— Select —</option>
                    <option v-for="k in ['9k','14k','18k','22k','24k']" :key="k" :value="k">{{ k.toUpperCase() }}</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Output Weight (g) *</label>
                  <input v-model.number="completeForm.output_weight" type="number" min="0.001" step="0.001" class="form-input"
                    :placeholder="(completing?.input_weight + (completing?.added_gold_weight||0)).toFixed(3)" />
                </div>
                <div>
                  <label class="form-label">Selling Price (LKR) *</label>
                  <input v-model.number="completeForm.selling_price" type="number" min="0" step="0.01" class="form-input" />
                </div>
              </div>
            </div>

            <p class="text-xs text-gray-400">
              This will create a new product in inventory with stock qty = 1 and purchase price = total cost.
              <span v-if="completing?.source_type === 'scrap'"> The scrap item will be marked as <strong>Melted</strong>.</span>
            </p>

            <p v-if="completeError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ completeError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 shrink-0">
            <button @click="showCompleteModal=false" class="btn-secondary">Cancel</button>
            <button @click="doComplete" :disabled="completeSaving" class="btn-primary px-6 bg-green-600 hover:bg-green-700">
              {{ completeSaving ? 'Completing…' : 'Complete & Add to Inventory' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import {
  PlusIcon, PencilSquareIcon, TrashIcon,
  CheckCircleIcon, WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'

const orders    = ref({ data: [], total: 0, last_page: 1 })
const buybacks  = ref([])
const scraps    = ref([])
const categories = ref([])
const page      = ref(1)
const filters   = reactive({ search: '', status: '' })

const showForm  = ref(false)
const editing   = ref(null)
const saving    = ref(false)
const formError = ref('')

const showCompleteModal = ref(false)
const completing        = ref(null)
const completeSaving    = ref(false)
const completeError     = ref('')

const blankForm = () => ({
  source_type: 'buyback', buyback_id: null, scrap_item_id: null,
  description: '', goldsmith_name: '', status: 'pending',
  input_weight: 0, input_karat: '', input_gold_cost: 0,
  added_gold_weight: 0, added_gold_cost: 0,
  making_charge: 0, making_charge_notes: '',
  started_at: new Date().toISOString().split('T')[0],
  expected_at: '', notes: '',
})
const form = reactive(blankForm())

const blankComplete = () => ({
  making_charge: null, making_charge_notes: '',
  product_name: '', category_id: null,
  output_weight: null, output_karat: '', selling_price: null,
})
const completeForm = reactive(blankComplete())

const summaryCards = computed(() => {
  const all  = orders.value.total ?? 0
  const data = orders.value.data ?? []
  const pending    = data.filter(r => r.status === 'pending').length
  const inProgress = data.filter(r => r.status === 'in_progress').length
  const completed  = data.filter(r => r.status === 'completed').length
  return [
    { label: 'Total Orders',  value: all,        color: 'text-gray-800' },
    { label: 'Pending',        value: pending,    color: 'text-yellow-600' },
    { label: 'In Progress',    value: inProgress, color: 'text-blue-600' },
    { label: 'Completed',      value: completed,  color: 'text-green-600' },
  ]
})

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(val) {
  if (!val) return '—'
  return new Date(val).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function statusClass(s) {
  return {
    pending:     'bg-yellow-100 text-yellow-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed:   'bg-green-100 text-green-700',
    cancelled:   'bg-gray-100 text-gray-500',
  }[s] ?? 'bg-gray-100 text-gray-600'
}

function statusLabel(s) {
  return { pending: 'Pending', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' }[s] ?? s
}

function prefillFromBuyback() {
  const b = buybacks.value.find(x => x.id === form.buyback_id)
  if (!b) return
  form.input_weight    = b.net_weight
  form.input_karat     = b.declared_karat
  form.input_gold_cost = b.final_price
}

function prefillFromScrap() {
  const s = scraps.value.find(x => x.id === form.scrap_item_id)
  if (!s) return
  form.input_weight    = s.weight_g
  form.input_karat     = s.karat
  form.input_gold_cost = s.estimated_value
}

function prefillFromSource() {
  // Reset when switching source type
  form.input_weight = 0; form.input_karat = ''; form.input_gold_cost = 0
}

function openCreate() {
  editing.value = null
  formError.value = ''
  Object.assign(form, blankForm())
  showForm.value = true
}

function openEdit(r) {
  editing.value = r
  formError.value = ''
  Object.assign(form, {
    source_type: r.source_type, buyback_id: r.buyback_id, scrap_item_id: r.scrap_item_id,
    description: r.description, goldsmith_name: r.goldsmith_name ?? '',
    status: r.status,
    input_weight: r.input_weight, input_karat: r.input_karat ?? '',
    input_gold_cost: r.input_gold_cost,
    added_gold_weight: r.added_gold_weight ?? 0, added_gold_cost: r.added_gold_cost ?? 0,
    making_charge: r.making_charge ?? 0, making_charge_notes: r.making_charge_notes ?? '',
    started_at: r.started_at ?? '', expected_at: r.expected_at ?? '',
    notes: r.notes ?? '',
  })
  showForm.value = true
}

function openComplete(r) {
  completing.value = r
  completeError.value = ''
  Object.assign(completeForm, {
    ...blankComplete(),
    product_name: r.description,
    output_karat: r.input_karat ?? '',
    output_weight: parseFloat((r.input_weight + (r.added_gold_weight || 0)).toFixed(3)),
    making_charge: r.making_charge,
    making_charge_notes: r.making_charge_notes ?? '',
  })
  showCompleteModal.value = true
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/rework-orders/${editing.value.id}`, form)
    } else {
      await axios.post('/api/rework-orders', form)
    }
    showForm.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { saving.value = false }
}

async function doComplete() {
  completeSaving.value = true; completeError.value = ''
  try {
    await axios.post(`/api/rework-orders/${completing.value.id}/complete`, completeForm)
    showCompleteModal.value = false
    load()
  } catch (e) {
    completeError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error completing'
  } finally { completeSaving.value = false }
}

async function del(r) {
  if (!confirm(`Delete job order ${r.reference_number}?`)) return
  try {
    await axios.delete(`/api/rework-orders/${r.id}`)
    load()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error deleting')
  }
}

async function load() {
  const { data } = await axios.get('/api/rework-orders', { params: { ...filters, page: page.value } })
  orders.value = data
}

onMounted(async () => {
  const [b, s, c] = await Promise.all([
    axios.get('/api/rework-orders/options/buybacks'),
    axios.get('/api/rework-orders/options/scraps'),
    axios.get('/api/rework-orders/options/categories'),
  ])
  buybacks.value   = b.data
  scraps.value     = s.data
  categories.value = c.data
  load()
})
</script>
