<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Gold Rate Management</h2>
        <p class="text-sm text-gray-500 mt-0.5">Set daily rates per carat and manage carat types.</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex gap-6">
        <button v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key"
          :class="activeTab === tab.key
            ? 'border-b-2 border-gold-500 text-gold-700 font-semibold'
            : 'text-gray-500 hover:text-gray-700'"
          class="pb-3 text-sm transition-colors">
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- ── Tab 1: Daily Rates ── -->
    <div v-if="activeTab === 'rates'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Set rates form -->
        <div class="card space-y-4">
          <h3 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-gold-500"></span>
            Set Today's Rates
          </h3>
          <div>
            <label class="form-label">Date</label>
            <input v-model="rateForm.date" type="date" class="form-input" />
          </div>

          <div v-if="carats.length === 0" class="text-sm text-gray-400 italic">
            No carat types found. Add them in the Carat Master tab.
          </div>

          <div v-for="carat in activeCarats" :key="carat.id" class="space-y-1">
            <label class="form-label">{{ carat.label }} rate (LKR / gram) *</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium text-sm">LKR</span>
              <input
                :value="displayRates[carat.id]"
                @input="onRateInput(carat.id, $event.target.value)"
                type="text" inputmode="numeric"
                class="form-input pl-12"
                :placeholder="`e.g. ${suggestedRate(carat).toLocaleString('en-LK')}`" />
            </div>
          </div>

          <p v-if="saveError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ saveError }}</p>
          <button @click="saveRates" :disabled="saving || activeCarats.length === 0"
            class="btn-primary w-full">
            {{ saving ? 'Saving…' : 'Save All Rates' }}
          </button>
        </div>

        <!-- Today's status card -->
        <div class="card space-y-4">
          <h3 class="font-semibold text-gray-700">Today's Rate Status</h3>
          <div v-if="todayRates.length" class="space-y-2">
            <div v-for="r in todayRates" :key="r.id"
              class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-3">
              <div>
                <p class="text-xs text-green-600 font-semibold uppercase tracking-wider">{{ r.carat?.label }} ✓</p>
                <p class="text-2xl font-bold text-green-700">LKR {{ lkr(r.rate_per_gram) }}</p>
                <p class="text-xs text-green-600">per gram</p>
              </div>
            </div>
          </div>
          <div v-else class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <p class="text-sm font-medium text-yellow-700">⚠ No rates set for today yet.</p>
            <p class="text-xs text-yellow-600 mt-1">Set rates on the left to enable automatic price calculations.</p>
          </div>

          <!-- Missing carats warning -->
          <div v-if="missingCarats.length" class="bg-orange-50 border border-orange-200 rounded-xl p-3">
            <p class="text-xs font-semibold text-orange-700 mb-1">Missing today's rate for:</p>
            <div class="flex flex-wrap gap-1">
              <span v-for="c in missingCarats" :key="c.id"
                class="badge bg-orange-100 text-orange-700 text-xs">{{ c.label }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- History table -->
      <div class="card p-0 overflow-hidden">
        <div class="px-6 py-4 border-b">
          <h3 class="font-semibold text-gray-700">Rate History (Last 30 Days)</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Date</th>
                <th v-for="c in activeCarats" :key="c.id" class="table-th">{{ c.label }} / g</th>
                <th class="table-th">Set By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(rows, date) in history" :key="date"
                class="hover:bg-gray-50"
                :class="isToday(date) ? 'bg-gold-50' : ''">
                <td class="table-td font-medium">
                  {{ fmtDate(date) }}
                  <span v-if="isToday(date)" class="ml-2 badge bg-gold-100 text-gold-700 text-xs">Today</span>
                </td>
                <td v-for="c in activeCarats" :key="c.id" class="table-td text-gray-700">
                  <span v-if="rateForCarat(rows, c.id)">
                    LKR {{ lkr(rateForCarat(rows, c.id).rate_per_gram) }}
                  </span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="table-td text-gray-400 text-xs">
                  {{ rows[0]?.created_by?.name ?? '—' }}
                </td>
              </tr>
              <tr v-if="!Object.keys(history).length">
                <td :colspan="activeCarats.length + 2" class="table-td text-center text-gray-400 py-8">
                  No rate history yet
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Tab 2: Carat Master ── -->
    <div v-if="activeTab === 'master'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Add carat form -->
        <div class="card space-y-4">
          <h3 class="font-semibold text-gray-700">Add New Carat Type</h3>
          <div>
            <label class="form-label">Label *</label>
            <input v-model="caratForm.label" type="text" class="form-input" placeholder="e.g. 14K" maxlength="10" />
          </div>
          <div>
            <label class="form-label">Purity (0–1) *</label>
            <input v-model.number="caratForm.purity" type="number" min="0.0001" max="1" step="0.0001"
              class="form-input" placeholder="e.g. 0.5833 for 14K" />
            <p class="text-xs text-gray-400 mt-1">24K = 1.0, 22K = 0.9167, 18K = 0.75</p>
          </div>
          <div>
            <label class="form-label">Sort Order</label>
            <input v-model.number="caratForm.sort_order" type="number" min="0" class="form-input" placeholder="0" />
          </div>
          <p v-if="caratError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ caratError }}</p>
          <button @click="addCarat" :disabled="addingCarat || !caratForm.label || !caratForm.purity"
            class="btn-primary w-full">
            {{ addingCarat ? 'Adding…' : 'Add Carat Type' }}
          </button>
        </div>

        <!-- Carat list -->
        <div class="card p-0 overflow-hidden">
          <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-700">Carat Types</h3>
          </div>
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Label</th>
                <th class="table-th">Purity</th>
                <th class="table-th">Status</th>
                <th class="table-th">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="c in carats" :key="c.id" class="hover:bg-gray-50">
                <td class="table-td font-semibold text-gold-700">{{ c.label }}</td>
                <td class="table-td text-gray-600">{{ (c.purity * 100).toFixed(2) }}%</td>
                <td class="table-td">
                  <span :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                    class="badge text-xs">{{ c.is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td class="table-td">
                  <div class="flex items-center gap-2">
                    <button @click="toggleCarat(c)"
                      class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                      {{ c.is_active ? 'Disable' : 'Enable' }}
                    </button>
                    <button @click="deleteCarat(c)"
                      class="text-xs text-red-500 hover:text-red-700 font-medium">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!carats.length">
                <td colspan="4" class="table-td text-center text-gray-400 py-8">No carat types yet</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { fmtDate } from '../utils/date.js'

const tabs = [
  { key: 'rates',  label: 'Daily Rates' },
  { key: 'master', label: 'Carat Master' },
]
const activeTab = ref('rates')

const carats   = ref([])
const todayRates = ref([])
const history  = ref({})
const saving   = ref(false)
const saveError = ref('')

const rateForm    = reactive({ date: new Date().toISOString().split('T')[0], rates: {} })
const displayRates = reactive({})

function onRateInput(caratId, raw) {
  const digits = raw.replace(/[^0-9.]/g, '')
  const num    = parseFloat(digits)
  rateForm.rates[caratId]   = isNaN(num) ? null : num
  displayRates[caratId]     = digits === '' ? '' : Number(digits.replace(/\..*/, '')).toLocaleString('en-LK') + (digits.includes('.') ? '.' + digits.split('.')[1] : '')
}

const activeCarats = computed(() => carats.value.filter(c => c.is_active).sort((a, b) => b.purity - a.purity))

const missingCarats = computed(() => {
  const todayIds = new Set(todayRates.value.map(r => r.carat_id))
  return activeCarats.value.filter(c => !todayIds.has(c.id))
})

// Carat master
const caratForm = reactive({ label: '', purity: '', sort_order: 0 })
const caratError = ref('')
const addingCarat = ref(false)

function lkr(val) {
  return Number(val).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function isToday(d) {
  return new Date(d).toDateString() === new Date().toDateString()
}

function rateForCarat(rows, caratId) {
  return rows.find(r => r.carat_id === caratId)
}

function suggestedRate(carat) {
  const base = 22000
  return Math.round(base * carat.purity)
}

async function load() {
  const { data } = await axios.get('/api/gold-rates')
  carats.value    = data.carats
  todayRates.value = data.today
  history.value   = data.history

  // Pre-fill form with today's rates
  rateForm.rates = {}
  for (const r of data.today) {
    rateForm.rates[r.carat_id]   = r.rate_per_gram
    displayRates[r.carat_id]     = Number(r.rate_per_gram).toLocaleString('en-LK')
  }
}

async function saveRates() {
  saving.value = true; saveError.value = ''
  const rates = activeCarats.value
    .filter(c => rateForm.rates[c.id])
    .map(c => ({ carat_id: c.id, rate_per_gram: rateForm.rates[c.id] }))

  if (!rates.length) {
    saveError.value = 'Enter at least one rate.'; saving.value = false; return
  }

  try {
    await axios.post('/api/gold-rates', { date: rateForm.date, rates })
    await load()
  } catch (e) {
    saveError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally {
    saving.value = false
  }
}

async function addCarat() {
  addingCarat.value = true; caratError.value = ''
  try {
    await axios.post('/api/carats', caratForm)
    caratForm.label = ''; caratForm.purity = ''; caratForm.sort_order = 0
    await load()
  } catch (e) {
    caratError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally {
    addingCarat.value = false
  }
}

async function deleteCarat(carat) {
  if (!confirm(`Delete "${carat.label}"? This cannot be undone.`)) return
  try {
    await axios.delete(`/api/carats/${carat.id}`)
    await load()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not delete carat.')
  }
}

async function toggleCarat(carat) {
  try {
    await axios.patch(`/api/carats/${carat.id}/toggle`)
    await load()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not update carat.')
  }
}

onMounted(load)
</script>
