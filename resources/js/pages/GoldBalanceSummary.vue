<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Gold Balance Summary</h1>
        <p class="text-sm text-gray-500 mt-1">Total Gold = Give Weights − Weight − Wastage</p>
      </div>
      <button @click="openModal()" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Entry
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="form-label">From</label>
        <input v-model="filters.date_from" type="date" class="form-input" @change="fetch(1)" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="filters.date_to" type="date" class="form-input" @change="fetch(1)" />
      </div>
      <div>
        <label class="form-label">Search</label>
        <input v-model="filters.search" type="text" class="form-input w-44" @input="fetchDebounced" placeholder="Description or article…" />
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm border-collapse">
        <thead>
          <tr class="bg-gray-900 text-white text-xs uppercase">
            <th class="px-3 py-3 text-left border border-gray-700">Date</th>
            <th class="px-3 py-3 text-left border border-gray-700">Description</th>
            <th class="px-3 py-3 text-center border border-gray-700">Karat</th>
            <th class="px-3 py-3 text-right border border-gray-700">Give Wt 1</th>
            <th class="px-3 py-3 text-right border border-gray-700">Give Wt 2</th>
            <th class="px-3 py-3 text-right border border-gray-700">Give Wt 3</th>
            <th class="px-3 py-3 text-right border border-gray-700">Give Wt 4</th>
            <th class="px-3 py-3 text-left border border-gray-700">Article</th>
            <th class="px-3 py-3 text-right border border-gray-700">Weight</th>
            <th class="px-3 py-3 text-right border border-gray-700">Wastage</th>
            <th class="px-3 py-3 text-right border border-gray-700">Other Charge</th>
            <th class="px-3 py-3 text-right border border-gray-700">Total Gold</th>
            <th class="px-3 py-3 text-center border border-gray-700">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="13" class="text-center py-8 text-gray-400">Loading…</td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="13" class="text-center py-8 text-gray-400">No entries found.</td>
          </tr>
          <tr v-for="row in rows" :key="row.id"
            class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-3 py-2 border border-gray-200 whitespace-nowrap">{{ fmtDate(row.entry_date) }}</td>
            <td class="px-3 py-2 border border-gray-200">{{ row.description ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-center">{{ row.karat ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.give_weight_1 ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.give_weight_2 ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.give_weight_3 ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.give_weight_4 ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200">{{ row.article ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.weight ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.wastage ?? '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right">{{ row.other_charge ? Number(row.other_charge).toLocaleString() : '—' }}</td>
            <td class="px-3 py-2 border border-gray-200 text-right font-bold"
              :class="row.total_gold > 0 ? 'text-green-700' : row.total_gold < 0 ? 'text-red-600' : ''">
              {{ row.total_gold }}
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
            <td class="px-3 py-2 border border-gray-300" colspan="3">Totals</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ sumField('give_weight_1') }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ sumField('give_weight_2') }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ sumField('give_weight_3') }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ sumField('give_weight_4') }}</td>
            <td class="px-3 py-2 border border-gray-300"></td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ sumField('weight') }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ sumField('wastage') }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right">{{ Number(rows.reduce((s,r)=>s+(r.other_charge??0),0)).toLocaleString() }}</td>
            <td class="px-3 py-2 border border-gray-300 text-right"
              :class="totalGold > 0 ? 'text-green-700' : totalGold < 0 ? 'text-red-600' : ''">
              {{ totalGold }}
            </td>
            <td class="px-3 py-2 border border-gray-300"></td>
          </tr>
        </tfoot>
      </table>

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
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-bold">{{ editing ? 'Edit Entry' : 'Add Entry' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="save" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Date *</label>
                <input v-model="form.entry_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Karat</label>
                <input v-model="form.karat" type="text" class="form-input" placeholder="e.g. 20Kt, 18Kt" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Description</label>
                <input v-model="form.description" type="text" class="form-input" />
              </div>

              <div class="col-span-2">
                <p class="form-label mb-2">Give Weights (g)</p>
                <div class="grid grid-cols-4 gap-3">
                  <div>
                    <label class="text-xs text-gray-500">Give Wt 1</label>
                    <input v-model="form.give_weight_1" type="number" step="0.001" min="0" class="form-input" @input="calcTotal" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Give Wt 2</label>
                    <input v-model="form.give_weight_2" type="number" step="0.001" min="0" class="form-input" @input="calcTotal" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Give Wt 3</label>
                    <input v-model="form.give_weight_3" type="number" step="0.001" min="0" class="form-input" @input="calcTotal" />
                  </div>
                  <div>
                    <label class="text-xs text-gray-500">Give Wt 4</label>
                    <input v-model="form.give_weight_4" type="number" step="0.001" min="0" class="form-input" @input="calcTotal" />
                  </div>
                </div>
              </div>

              <div>
                <label class="form-label">Article</label>
                <input v-model="form.article" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Weight (g)</label>
                <input v-model="form.weight" type="number" step="0.001" min="0" class="form-input" @input="calcTotal" />
              </div>
              <div>
                <label class="form-label">Wastage (g)</label>
                <input v-model="form.wastage" type="number" step="0.001" min="0" class="form-input" @input="calcTotal" />
              </div>
              <div>
                <label class="form-label">Other Charge (LKR)</label>
                <input v-model="form.other_charge" type="number" step="0.01" min="0" class="form-input" />
              </div>

              <div class="col-span-2">
                <label class="form-label">Total Gold (auto)</label>
                <input :value="form.total_gold" type="text" readonly
                  class="form-input font-bold bg-yellow-50"
                  :class="form.total_gold > 0 ? 'text-green-700' : form.total_gold < 0 ? 'text-red-600' : ''" />
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
          <p class="text-sm text-gray-600">Delete <strong>{{ deleteTarget.description || deleteTarget.entry_date }}</strong>?</p>
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

const rows       = ref([])
const loading    = ref(false)
const pagination = ref({ current_page: 1, last_page: 1 })
const _today = new Date().toISOString().slice(0, 10)
const _from30 = (() => { const d = new Date(); d.setDate(d.getDate() - 30); return d.toISOString().slice(0, 10) })()
const filters    = reactive({ date_from: _from30, date_to: _today, search: '' })

const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formError  = ref('')
const deleteTarget = ref(null)
const deleting     = ref(false)

const emptyForm = () => ({
  entry_date: new Date().toISOString().slice(0, 10),
  description: '', karat: '',
  give_weight_1: '', give_weight_2: '', give_weight_3: '', give_weight_4: '',
  article: '', weight: '', wastage: '', other_charge: '', total_gold: 0,
})
const form = reactive(emptyForm())

const totalGold = computed(() =>
  Math.round(rows.value.reduce((s, r) => s + (r.total_gold ?? 0), 0) * 1000) / 1000
)

function sumField(f) {
  const v = rows.value.reduce((s, r) => s + (r[f] ?? 0), 0)
  return v ? Math.round(v * 1000) / 1000 : '—'
}

function fmtDate(v) { return v ? v.slice(0, 10).replace(/-/g, '.') : '—' }

function calcTotal() {
  const give = (parseFloat(form.give_weight_1) || 0)
             + (parseFloat(form.give_weight_2) || 0)
             + (parseFloat(form.give_weight_3) || 0)
             + (parseFloat(form.give_weight_4) || 0)
  form.total_gold = Math.round((give - (parseFloat(form.weight) || 0) - (parseFloat(form.wastage) || 0)) * 1000) / 1000
}

let debounceTimer = null
function fetchDebounced() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetch(1), 300)
}

async function fetch(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/gold-balance-entries', { params: { ...filters, page } })
    rows.value       = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page }
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
      entry_date: row.entry_date?.slice(0, 10) ?? '',
      description: row.description ?? '', karat: row.karat ?? '',
      give_weight_1: row.give_weight_1 ?? '', give_weight_2: row.give_weight_2 ?? '',
      give_weight_3: row.give_weight_3 ?? '', give_weight_4: row.give_weight_4 ?? '',
      article: row.article ?? '', weight: row.weight ?? '',
      wastage: row.wastage ?? '', other_charge: row.other_charge ?? '',
      total_gold: row.total_gold ?? 0,
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
      await axios.put(`/api/gold-balance-entries/${editing.value}`, form)
    } else {
      await axios.post('/api/gold-balance-entries', form)
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
    await axios.delete(`/api/gold-balance-entries/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetch(pagination.value.current_page)
  } finally {
    deleting.value = false
  }
}

onMounted(() => fetch())
</script>
