<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Cash Balance Table</h1>
        <p class="text-sm text-gray-500 mt-1">Daily cash reconciliation — Jewellery + Old Gold</p>
      </div>
      <button @click="openModal()" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Entry
      </button>
    </div>

    <!-- Date filter -->
    <div class="card p-4 flex flex-wrap gap-4 items-end">
      <div>
        <label class="form-label">From</label>
        <input v-model="filter.from" type="date" class="form-input" @change="fetch" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="filter.to" type="date" class="form-input" @change="fetch" />
      </div>
      <button @click="clearFilter" class="btn-secondary text-sm">Clear</button>
    </div>

    <!-- Entries list -->
    <div class="space-y-4">
      <div v-if="loading" class="card p-8 text-center text-gray-400">Loading…</div>
      <div v-else-if="!rows.length" class="card p-8 text-center text-gray-400">No entries found.</div>

      <div v-for="row in rows" :key="row.id" class="card overflow-hidden">
        <!-- Entry header -->
        <div class="flex items-center justify-between px-5 py-3 bg-gray-800 text-white">
          <div class="flex items-center gap-3">
            <CalendarIcon class="w-4 h-4 text-gray-400" />
            <span class="font-semibold">{{ fmtDate(row.entry_date) }}</span>
            <span v-if="row.entry_time" class="text-gray-400 text-sm">{{ row.entry_time }}</span>
          </div>
          <div class="flex items-center gap-2">
            <button @click="openModal(row)" class="p-1.5 text-blue-300 hover:bg-gray-700 rounded">
              <PencilIcon class="w-4 h-4" />
            </button>
            <button @click="confirmDelete(row)" class="p-1.5 text-red-400 hover:bg-gray-700 rounded">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Entry body: two columns -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-200">
          <!-- LEFT: Shot items -->
          <div class="p-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">Shot Items</h3>
            <table class="w-full text-sm">
              <tbody>
                <tr v-for="(shot, i) in (row.shots || [])" :key="i" class="border-b border-gray-100">
                  <td class="py-1.5 text-gray-700">{{ shot.name }}</td>
                  <td class="py-1.5 text-right font-medium">{{ fmt(shot.amount) }}</td>
                </tr>
                <tr v-if="!(row.shots || []).length">
                  <td colspan="2" class="py-2 text-gray-400 text-center text-xs">No shot items</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t-2 border-gray-300 font-bold text-gray-800">
                  <td class="pt-2">Total Shot</td>
                  <td class="pt-2 text-right text-red-700">{{ fmt(totalShot(row)) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- RIGHT: Cash summary -->
          <div class="p-4 space-y-0">
            <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">Cash Summary</h3>
            <table class="w-full text-sm">
              <tbody>
                <tr class="border-b border-gray-100">
                  <td class="py-1.5 text-gray-600">Jewellery Cash</td>
                  <td class="py-1.5 text-right">{{ fmt(row.jewellery_cash) }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                  <td class="py-1.5 text-gray-600">Old Gold Cash</td>
                  <td class="py-1.5 text-right">{{ fmt(row.old_cash) }}</td>
                </tr>
                <tr class="border-b-2 border-gray-300 font-semibold">
                  <td class="py-1.5">Total Cash</td>
                  <td class="py-1.5 text-right text-green-700">{{ fmt(totalCash(row)) }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                  <td class="py-2 text-gray-600">Shot</td>
                  <td class="py-2 text-right text-red-600">{{ fmt(totalShot(row)) }}</td>
                </tr>
                <tr class="border-b-2 border-indigo-300 font-semibold">
                  <td class="py-1.5 text-indigo-800">Cash In Hand</td>
                  <td class="py-1.5 text-right text-indigo-700 font-bold">{{ fmt(cashInHand(row)) }}</td>
                </tr>
                <tr class="border-b border-gray-100">
                  <td class="py-1.5 text-gray-600">Actual Cash</td>
                  <td class="py-1.5 text-right">{{ row.actual_cash != null ? fmt(row.actual_cash) : '—' }}</td>
                </tr>
                <tr v-if="row.actual_cash != null">
                  <td class="py-1.5 font-semibold" :class="discrepancy(row) >= 0 ? 'text-green-700' : 'text-red-700'">
                    Discrepancy
                  </td>
                  <td class="py-1.5 text-right font-bold" :class="discrepancy(row) >= 0 ? 'text-green-700' : 'text-red-700'">
                    {{ discrepancy(row) >= 0 ? '+' : '' }}{{ fmt(discrepancy(row)) }}
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="row.notes" class="mt-3 text-xs text-gray-500 italic">{{ row.notes }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl my-4">
          <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-bold">{{ editing ? 'Edit Entry' : 'Add Cash Balance Entry' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="save" class="p-5 space-y-5">

            <!-- Date / Time -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Date *</label>
                <input v-model="form.entry_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Time</label>
                <input v-model="form.entry_time" type="time" class="form-input" />
              </div>
            </div>

            <!-- Cash inputs -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Jewellery Cash (LKR) *</label>
                <input v-model="form.jewellery_cash" type="number" step="0.01" min="0" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Old Gold Cash (LKR) *</label>
                <input v-model="form.old_cash" type="number" step="0.01" min="0" required class="form-input" />
              </div>
            </div>

            <!-- Live totals preview -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm grid grid-cols-3 gap-2 text-center">
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Total Cash</p>
                <p class="font-bold text-green-700">{{ fmt(previewTotalCash) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Total Shot</p>
                <p class="font-bold text-red-600">{{ fmt(previewTotalShot) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-0.5">Cash In Hand</p>
                <p class="font-bold text-indigo-700">{{ fmt(previewCashInHand) }}</p>
              </div>
            </div>

            <!-- Shot items -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="form-label mb-0">Shot Items</label>
                <button type="button" @click="addShot" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                  <PlusIcon class="w-3 h-3" /> Add Row
                </button>
              </div>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="px-3 py-2 text-left font-medium text-gray-600">Name</th>
                      <th class="px-3 py-2 text-right font-medium text-gray-600">Amount (LKR)</th>
                      <th class="px-3 py-2 w-10"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(shot, i) in form.shots" :key="i" class="border-t border-gray-100">
                      <td class="px-2 py-1.5">
                        <input v-model="shot.name" type="text" placeholder="e.g. Gehan" class="form-input text-sm py-1" required />
                      </td>
                      <td class="px-2 py-1.5">
                        <input v-model="shot.amount" type="number" step="0.01" min="0" placeholder="0.00" class="form-input text-sm py-1 text-right" required />
                      </td>
                      <td class="px-2 py-1.5 text-center">
                        <button type="button" @click="removeShot(i)" class="text-red-400 hover:text-red-600">
                          <XMarkIcon class="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="!form.shots.length">
                      <td colspan="3" class="px-3 py-3 text-center text-gray-400 text-xs">No shot items — click "Add Row"</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Actual cash -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Actual Cash (LKR)</label>
                <input v-model="form.actual_cash" type="number" step="0.01" min="0" class="form-input" placeholder="Physical count" />
              </div>
              <div v-if="form.actual_cash !== '' && form.actual_cash !== null" class="flex items-end pb-1">
                <div class="w-full bg-gray-50 border rounded-lg px-3 py-2 text-sm">
                  <p class="text-xs text-gray-500 mb-0.5">Discrepancy</p>
                  <p class="font-bold" :class="previewDiscrepancy >= 0 ? 'text-green-700' : 'text-red-700'">
                    {{ previewDiscrepancy >= 0 ? '+' : '' }}{{ fmt(previewDiscrepancy) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Optional remarks"></textarea>
            </div>

            <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Saving…' : (editing ? 'Update' : 'Add Entry') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete confirm -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 space-y-4">
          <h3 class="text-lg font-bold">Delete Entry?</h3>
          <p class="text-sm text-gray-600">Delete entry for <strong>{{ fmtDate(deleteTarget.entry_date) }}</strong>?</p>
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
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon, CalendarIcon } from '@heroicons/vue/24/outline'

const rows    = ref([])
const loading = ref(false)
const _today = new Date().toISOString().slice(0, 10)
const _from30 = (() => { const d = new Date(); d.setDate(d.getDate() - 30); return d.toISOString().slice(0, 10) })()
const filter  = reactive({ from: _from30, to: _today })

const showModal    = ref(false)
const editing      = ref(null)
const saving       = ref(false)
const formError    = ref('')
const deleteTarget = ref(null)
const deleting     = ref(false)

const emptyForm = () => ({
  entry_date: new Date().toISOString().slice(0, 10),
  entry_time: '',
  jewellery_cash: '',
  old_cash: '',
  actual_cash: '',
  shots: [],
  notes: '',
})
const form = reactive(emptyForm())

function fmt(v) {
  const n = parseFloat(v) || 0
  return n.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function fmtDate(v) {
  if (!v) return '—'
  return String(v).slice(0, 10).replace(/-/g, '.')
}

// Computed helpers for a saved row
function totalCash(row) { return (row.jewellery_cash || 0) + (row.old_cash || 0) }
function totalShot(row) { return (row.shots || []).reduce((s, sh) => s + (parseFloat(sh.amount) || 0), 0) }
function cashInHand(row) { return totalCash(row) - totalShot(row) }
function discrepancy(row) { return (parseFloat(row.actual_cash) || 0) - cashInHand(row) }

// Live modal preview
const previewTotalCash  = computed(() => (parseFloat(form.jewellery_cash) || 0) + (parseFloat(form.old_cash) || 0))
const previewTotalShot  = computed(() => form.shots.reduce((s, sh) => s + (parseFloat(sh.amount) || 0), 0))
const previewCashInHand = computed(() => previewTotalCash.value - previewTotalShot.value)
const previewDiscrepancy = computed(() => (parseFloat(form.actual_cash) || 0) - previewCashInHand.value)

function addShot()       { form.shots.push({ name: '', amount: '' }) }
function removeShot(i)   { form.shots.splice(i, 1) }

async function fetch() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/cash-balance-entries', {
      params: { date_from: filter.from || undefined, date_to: filter.to || undefined },
    })
    rows.value = data
  } finally {
    loading.value = false
  }
}

function clearFilter() {
  filter.from = ''; filter.to = ''
  fetch()
}

function openModal(row = null) {
  formError.value = ''
  if (row) {
    editing.value = row.id
    Object.assign(form, {
      entry_date:     row.entry_date?.slice(0, 10) ?? '',
      entry_time:     row.entry_time ?? '',
      jewellery_cash: row.jewellery_cash ?? '',
      old_cash:       row.old_cash ?? '',
      actual_cash:    row.actual_cash ?? '',
      shots:          (row.shots ?? []).map(s => ({ name: s.name, amount: s.amount })),
      notes:          row.notes ?? '',
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
  const payload = {
    ...form,
    actual_cash: form.actual_cash !== '' ? form.actual_cash : null,
    entry_time:  form.entry_time || null,
    shots: form.shots.filter(s => s.name && s.amount !== ''),
  }
  try {
    if (editing.value) {
      await axios.put(`/api/cash-balance-entries/${editing.value}`, payload)
    } else {
      await axios.post('/api/cash-balance-entries', payload)
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
    await axios.delete(`/api/cash-balance-entries/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetch()
  } finally {
    deleting.value = false
  }
}

onMounted(() => fetch())
</script>
