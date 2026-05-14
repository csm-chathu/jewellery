<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <LockClosedIcon class="w-5 h-5 text-gray-400" />
          Private Gold Purchases
        </h2>
        <p class="text-sm text-gray-400 mt-0.5">Internal records only — not reflected in accounts or external reports</p>
      </div>
      <button @click="openModal(null)" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Record
      </button>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap">
      <input v-model="filters.search" placeholder="Reference, description, notes…" class="form-input w-56" @input="load" />
      <input v-model="filters.date_from" type="date" class="form-input w-36" @change="load" />
      <input v-model="filters.date_to" type="date" class="form-input w-36" @change="load" />
      <button @click="filters.search=''; filters.date_from=''; filters.date_to=''; load()" class="btn-secondary text-sm">Clear</button>
    </div>

    <!-- Summary bar -->
    <div class="grid grid-cols-3 gap-3">
      <div class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Records</p>
        <p class="text-lg font-bold text-gray-800 mt-1">{{ records.total ?? 0 }}</p>
      </div>
      <div class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Total Weight (net)</p>
        <p class="text-lg font-bold text-gray-800 mt-1">{{ totalWeight.toFixed(3) }} g</p>
      </div>
      <div class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">Total Paid</p>
        <p class="text-lg font-bold text-gray-800 mt-1">LKR {{ lkr(totalPaid) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Ref #</th>
            <th class="table-th">Date</th>
            <th class="table-th">Description</th>
            <th class="table-th">Type</th>
            <th class="table-th">Net Weight</th>
            <th class="table-th">Karat</th>
            <th class="table-th">Rate/g</th>
            <th class="table-th">Final Price</th>
            <th class="table-th">Payment</th>
            <th class="table-th">Recorded By</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in records.data" :key="r.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs text-gray-500">{{ r.reference_number }}</td>
            <td class="table-td text-sm">{{ r.purchase_date ? new Date(r.purchase_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—' }}</td>
            <td class="table-td text-sm">
              <p>{{ r.description || '—' }}</p>
              <p v-if="r.notes" class="text-xs text-gray-400 truncate max-w-[160px]">{{ r.notes }}</p>
            </td>
            <td class="table-td">
              <span class="badge bg-gray-100 text-gray-600 text-xs capitalize">{{ r.item_type }}</span>
            </td>
            <td class="table-td font-mono text-sm">{{ r.net_weight }}g</td>
            <td class="table-td text-sm text-gold-600 font-medium">{{ r.declared_karat }}</td>
            <td class="table-td text-sm">LKR {{ lkr(r.rate_per_gram) }}</td>
            <td class="table-td font-semibold text-gold-700">LKR {{ lkr(r.final_price) }}</td>
            <td class="table-td">
              <span class="badge text-xs capitalize"
                :class="r.payment_method === 'cash' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
                {{ r.payment_method === 'bank_transfer' ? 'Bank' : 'Cash' }}
              </span>
            </td>
            <td class="table-td text-sm text-gray-500">{{ r.recorder?.name }}</td>
            <td class="table-td">
              <div class="flex gap-1.5">
                <button @click="openModal(r)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                </button>
                <button @click="del(r)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!records.data?.length">
            <td colspan="11" class="table-td text-center text-gray-400 py-10">No records found</td>
          </tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ records.total ?? 0 }} records</span>
        <div class="flex gap-2">
          <button @click="page--; load()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">← Prev</button>
          <button @click="page++; load()" :disabled="page>=records.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
            <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit Record' : 'New Private Purchase' }}</h3>
            <button @click="showModal=false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-6 space-y-5">

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Purchase Date *</label>
                <input v-model="form.purchase_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Item Type *</label>
                <select v-model="form.item_type" class="form-input">
                  <option value="jewelry">Jewelry</option>
                  <option value="coin">Coin / Medallion</option>
                  <option value="bar">Bar / Ingot</option>
                  <option value="scrap">Scrap Gold</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="form-label">Description</label>
                <input v-model="form.description" class="form-input" placeholder="e.g. Old 22K bangle" />
              </div>
            </div>

            <!-- Weight -->
            <div class="border rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Weight</p>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="form-label">Gross (g) *</label>
                  <input v-model.number="form.gross_weight" type="number" min="0" step="0.001" class="form-input" @input="calcNet" />
                </div>
                <div>
                  <label class="form-label">Deduction (g)</label>
                  <input v-model.number="form.deduction_weight" type="number" min="0" step="0.001" class="form-input" @input="calcNet" placeholder="Stones…" />
                </div>
                <div>
                  <label class="form-label">Net (g)</label>
                  <input v-model.number="form.net_weight" type="number" min="0" step="0.001" class="form-input bg-gold-50" />
                </div>
              </div>
              <div>
                <label class="form-label">Declared Karat</label>
                <select v-model="form.declared_karat" class="form-input w-36">
                  <option value="unknown">Unknown</option>
                  <option value="9k">9K</option>
                  <option value="14k">14K</option>
                  <option value="18k">18K</option>
                  <option value="22k">22K</option>
                  <option value="24k">24K</option>
                </select>
              </div>
            </div>

            <!-- Pricing -->
            <div class="border rounded-xl p-4 space-y-3 bg-gold-50/40">
              <p class="text-sm font-semibold text-gray-700">Pricing</p>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Rate/g (LKR) *</label>
                  <input v-model.number="form.rate_per_gram" type="number" min="0" step="0.01" class="form-input" @input="calcPrice" />
                </div>
                <div>
                  <label class="form-label">Final Price (LKR) *</label>
                  <input v-model.number="form.final_price" type="number" min="0" step="0.01" class="form-input font-semibold" />
                </div>
                <div>
                  <label class="form-label">Payment Method</label>
                  <select v-model="form.payment_method" class="form-input">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                  </select>
                </div>
              </div>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Internal notes…"></textarea>
            </div>

            <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 sticky bottom-0">
            <button @click="showModal=false" class="btn-secondary">Cancel</button>
            <button @click="save" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, PencilSquareIcon, TrashIcon, LockClosedIcon } from '@heroicons/vue/24/outline'

const records   = ref({ data: [], total: 0, last_page: 1 })
const showModal = ref(false)
const editing   = ref(null)
const saving    = ref(false)
const formError = ref('')
const page      = ref(1)
const filters   = reactive({ search: '', date_from: '', date_to: '' })

const KARAT_PURITY = { '9k': 9/24, '14k': 14/24, '18k': 18/24, '22k': 22/24, '24k': 1 }

const totalWeight = computed(() => (records.value.data ?? []).reduce((s, r) => s + (r.net_weight || 0), 0))
const totalPaid   = computed(() => (records.value.data ?? []).reduce((s, r) => s + (r.final_price || 0), 0))

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const blankForm = () => ({
  purchase_date: new Date().toISOString().split('T')[0],
  description: '', item_type: 'jewelry',
  gross_weight: 0, deduction_weight: 0, net_weight: 0, declared_karat: 'unknown',
  rate_per_gram: 0, final_price: 0, payment_method: 'cash', notes: '',
})
const form = reactive(blankForm())

function calcNet() {
  form.net_weight = Math.max(0, (form.gross_weight || 0) - (form.deduction_weight || 0))
  calcPrice()
}

function calcPrice() {
  const purity = KARAT_PURITY[form.declared_karat] ?? 1
  form.final_price = Math.round(form.rate_per_gram * form.net_weight * purity * 100) / 100
}

function openModal(r) {
  editing.value = r
  formError.value = ''
  if (r) {
    Object.assign(form, { ...blankForm(), ...r })
  } else {
    Object.assign(form, blankForm())
  }
  showModal.value = true
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/informal-purchases/${editing.value.id}`, form)
    } else {
      await axios.post('/api/informal-purchases', form)
    }
    showModal.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { saving.value = false }
}

async function del(r) {
  if (!confirm(`Delete record ${r.reference_number}?`)) return
  await axios.delete(`/api/informal-purchases/${r.id}`)
  load()
}

async function load() {
  const { data } = await axios.get('/api/informal-purchases', { params: { ...filters, page: page.value } })
  records.value = data
}

onMounted(load)
</script>
