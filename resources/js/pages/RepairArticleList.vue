<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Repair Article List</h1>
        <p class="text-sm text-gray-500 mt-1">Track articles received for repair</p>
      </div>
      <button @click="openModal()" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Repair
      </button>
    </div>

    <!-- Search -->
    <div class="card p-4">
      <input v-model="search" @input="fetchDebounced" type="text" placeholder="Search by article, customer, bill no, phone…"
        class="form-input w-full max-w-md" />
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-900 text-white text-xs uppercase">
            <th class="px-3 py-3 text-left">Date</th>
            <th class="px-3 py-3 text-left">Bill No</th>
            <th class="px-3 py-3 text-left">Give Date</th>
            <th class="px-3 py-3 text-left">Article</th>
            <th class="px-3 py-3 text-left">Damage</th>
            <th class="px-3 py-3 text-left">Customer Name</th>
            <th class="px-3 py-3 text-left">Telephone No</th>
            <th class="px-3 py-3 text-right">Weight</th>
            <th class="px-3 py-3 text-right">Add Weight</th>
            <th class="px-3 py-3 text-right">Advance</th>
            <th class="px-3 py-3 text-center">Done</th>
            <th class="px-3 py-3 text-center">Give</th>
            <th class="px-3 py-3 text-right">Price</th>
            <th class="px-3 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="14" class="text-center py-8 text-gray-400">Loading…</td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="14" class="text-center py-8 text-gray-400">No repair articles found.</td>
          </tr>
          <tr v-for="row in rows" :key="row.id"
            class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
            :class="{ 'bg-green-50': row.given }">
            <td class="px-3 py-2 whitespace-nowrap">{{ fmtDate(row.received_date) }}</td>
            <td class="px-3 py-2">{{ row.bill_number ?? '—' }}</td>
            <td class="px-3 py-2 whitespace-nowrap" :class="isOverdue(row) ? 'text-red-600 font-semibold' : ''">
              {{ fmtDate(row.give_date) }}
            </td>
            <td class="px-3 py-2 font-medium">{{ row.article }}</td>
            <td class="px-3 py-2 text-gray-600 max-w-[200px] truncate" :title="row.damage">{{ row.damage ?? '—' }}</td>
            <td class="px-3 py-2">{{ row.customer_name ?? '—' }}</td>
            <td class="px-3 py-2">{{ row.telephone ?? '—' }}</td>
            <td class="px-3 py-2 text-right">{{ row.weight ?? '—' }}</td>
            <td class="px-3 py-2 text-right">{{ row.add_weight ?? '—' }}</td>
            <td class="px-3 py-2 text-right">{{ row.advance ? Number(row.advance).toLocaleString() : '—' }}</td>
            <td class="px-3 py-2 text-center">
              <span v-if="row.done" class="text-green-600 font-bold text-xs bg-green-100 px-2 py-0.5 rounded-full">YES</span>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>
            <td class="px-3 py-2 text-center">
              <span v-if="row.given" class="text-blue-600 font-bold text-xs bg-blue-100 px-2 py-0.5 rounded-full">YES</span>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>
            <td class="px-3 py-2 text-right font-semibold">{{ Number(row.price).toLocaleString() }}</td>
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
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-bold">{{ editing ? 'Edit Repair Article' : 'Add Repair Article' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="save" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Received Date *</label>
                <input v-model="form.received_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Bill No</label>
                <input v-model="form.bill_number" type="text" class="form-input" placeholder="e.g. 1409" />
              </div>
              <div>
                <label class="form-label">Give Date</label>
                <input v-model="form.give_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Article *</label>
                <input v-model="form.article" type="text" required class="form-input" placeholder="e.g. Chain, Ring…" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Damage / Work Description</label>
                <textarea v-model="form.damage" class="form-input" rows="2" placeholder="Describe the damage or work needed…" />
              </div>
              <div>
                <label class="form-label">Customer Name</label>
                <input v-model="form.customer_name" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Telephone No</label>
                <input v-model="form.telephone" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Weight (g)</label>
                <input v-model="form.weight" type="number" step="0.001" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Add Weight (g)</label>
                <input v-model="form.add_weight" type="number" step="0.001" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Advance (LKR)</label>
                <input v-model="form.advance" type="number" step="0.01" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Price (LKR)</label>
                <input v-model="form.price" type="number" step="0.01" min="0" class="form-input" />
              </div>
              <div class="flex items-center gap-6 col-span-2 pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input v-model="form.done" type="checkbox" class="w-4 h-4 accent-green-600" />
                  <span class="text-sm font-medium">Done</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input v-model="form.given" type="checkbox" class="w-4 h-4 accent-blue-600" />
                  <span class="text-sm font-medium">Given back to customer</span>
                </label>
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
          <h3 class="text-lg font-bold text-gray-900">Delete Repair Article?</h3>
          <p class="text-sm text-gray-600">
            Are you sure you want to delete <strong>{{ deleteTarget.article }}</strong>? This cannot be undone.
          </p>
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
import { ref, reactive, onMounted } from 'vue'

function fmtDate(val) {
  if (!val) return '—'
  return val.slice(0, 10).replace(/-/g, '.')
}
import axios from 'axios'
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const rows       = ref([])
const loading    = ref(false)
const search     = ref('')
const pagination = ref({ current_page: 1, last_page: 1 })

const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formError  = ref('')

const deleteTarget = ref(null)
const deleting     = ref(false)

const emptyForm = () => ({
  bill_number: '', received_date: new Date().toISOString().slice(0, 10),
  give_date: '', article: '', damage: '', customer_name: '', telephone: '',
  weight: '', add_weight: '', advance: '', price: '', done: false, given: false, notes: '',
})

const form = reactive(emptyForm())

let debounceTimer = null
function fetchDebounced() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetch(1), 300)
}

async function fetch(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/repair-articles', { params: { search: search.value, page } })
    rows.value       = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page }
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  if (p >= 1 && p <= pagination.value.last_page) fetch(p)
}

function isOverdue(row) {
  if (!row.give_date || row.given) return false
  return new Date(row.give_date) < new Date()
}

function openModal(row = null) {
  formError.value = ''
  if (row) {
    editing.value = row.id
    Object.assign(form, {
      bill_number: row.bill_number ?? '', received_date: row.received_date?.slice(0, 10) ?? '',
      give_date: row.give_date?.slice(0, 10) ?? '', article: row.article, damage: row.damage ?? '',
      customer_name: row.customer_name ?? '', telephone: row.telephone ?? '',
      weight: row.weight ?? '', add_weight: row.add_weight ?? '',
      advance: row.advance ?? '', price: row.price ?? '',
      done: row.done, given: row.given, notes: row.notes ?? '',
    })
  } else {
    editing.value = null
    Object.assign(form, emptyForm())
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  saving.value = true
  formError.value = ''
  try {
    const payload = { ...form }
    if (editing.value) {
      await axios.put(`/api/repair-articles/${editing.value}`, payload)
    } else {
      await axios.post('/api/repair-articles', payload)
    }
    closeModal()
    fetch(pagination.value.current_page)
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0] ?? 'Failed to save.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(row) { deleteTarget.value = row }

async function doDelete() {
  deleting.value = true
  try {
    await axios.delete(`/api/repair-articles/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetch(pagination.value.current_page)
  } finally {
    deleting.value = false
  }
}

onMounted(() => fetch())
</script>
