<template>
  <div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
          <ScaleIcon class="w-6 h-6 text-amber-500" /> Opening Balances
        </h2>
        <p class="text-sm text-gray-500 mt-0.5">Enter your starting balances once when setting up the system for the first time.</p>
      </div>
    </div>

    <div v-if="loading" class="card text-center text-gray-400 py-12">Loading accounts…</div>

    <template v-else>
      <!-- Date -->
      <div class="card flex items-end gap-4 py-4">
        <div>
          <label class="form-label">Opening Balance Date</label>
          <input v-model="asOfDate" type="date" class="form-input w-44" />
        </div>
        <p class="text-xs text-gray-400 pb-1">Usually your business start date or the date you started using this software.</p>
      </div>

      <!-- Totals bar -->
      <div class="grid grid-cols-3 gap-3">
        <div class="card text-center py-3">
          <p class="text-xs text-gray-500 uppercase tracking-wider">Total Assets</p>
          <p class="text-lg font-bold text-gray-800 mt-1">LKR {{ fmt(totalAssets) }}</p>
        </div>
        <div class="card text-center py-3">
          <p class="text-xs text-gray-500 uppercase tracking-wider">Total Liabilities + Equity</p>
          <p class="text-lg font-bold text-gray-800 mt-1">LKR {{ fmt(totalLiabEquity) }}</p>
        </div>
        <div class="card text-center py-3">
          <p class="text-xs text-gray-500 uppercase tracking-wider">Difference (auto to Capital)</p>
          <p class="text-lg font-bold mt-1" :class="Math.abs(difference) < 0.01 ? 'text-green-600' : 'text-amber-600'">
            {{ Math.abs(difference) < 0.01 ? '✓ Balanced' : 'LKR ' + fmt(Math.abs(difference)) }}
          </p>
        </div>
      </div>

      <!-- Assets -->
      <div class="card space-y-0 p-0 overflow-hidden">
        <div class="px-5 py-3 bg-blue-50 border-b border-blue-100 flex items-center gap-2">
          <span class="text-sm font-semibold text-blue-800">Assets</span>
          <span class="text-xs text-blue-500">(cash, bank, inventory, receivables…)</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="acct in assets" :key="acct.id" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50">
            <div class="w-14 text-xs font-mono text-gray-400">{{ acct.code }}</div>
            <div class="flex-1 text-sm text-gray-700">{{ acct.name }}</div>
            <div class="flex items-center gap-1.5">
              <span class="text-xs text-gray-400">LKR</span>
              <input v-model.number="balances[acct.id]" type="number" min="0" step="0.01"
                class="form-input w-36 text-right"
                placeholder="0.00" />
            </div>
          </div>
        </div>
      </div>

      <!-- Liabilities -->
      <div class="card space-y-0 p-0 overflow-hidden">
        <div class="px-5 py-3 bg-red-50 border-b border-red-100 flex items-center gap-2">
          <span class="text-sm font-semibold text-red-800">Liabilities</span>
          <span class="text-xs text-red-400">(loans, payables, customer deposits…)</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="acct in liabilities" :key="acct.id" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50">
            <div class="w-14 text-xs font-mono text-gray-400">{{ acct.code }}</div>
            <div class="flex-1 text-sm text-gray-700">{{ acct.name }}</div>
            <div class="flex items-center gap-1.5">
              <span class="text-xs text-gray-400">LKR</span>
              <input v-model.number="balances[acct.id]" type="number" min="0" step="0.01"
                class="form-input w-36 text-right"
                placeholder="0.00" />
            </div>
          </div>
        </div>
      </div>

      <!-- Equity -->
      <div class="card space-y-0 p-0 overflow-hidden">
        <div class="px-5 py-3 bg-green-50 border-b border-green-100 flex items-center gap-2">
          <span class="text-sm font-semibold text-green-800">Equity</span>
          <span class="text-xs text-green-500">(owner's capital, retained earnings…)</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="acct in equity" :key="acct.id" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50">
            <div class="w-14 text-xs font-mono text-gray-400">{{ acct.code }}</div>
            <div class="flex-1 text-sm text-gray-700">{{ acct.name }}</div>
            <div class="flex items-center gap-1.5">
              <span class="text-xs text-gray-400">LKR</span>
              <input v-model.number="balances[acct.id]" type="number" min="0" step="0.01"
                class="form-input w-36 text-right"
                placeholder="0.00" />
            </div>
          </div>
        </div>
      </div>

      <div v-if="Math.abs(difference) > 0.01" class="text-xs bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-4 py-2.5">
        The difference of <strong>LKR {{ fmt(Math.abs(difference)) }}</strong> will be automatically added to <strong>Owner's Capital (3000)</strong> to balance the books.
      </div>

      <!-- Save -->
      <div class="flex items-center gap-3">
        <button @click="save" :disabled="saving"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white rounded-lg font-medium transition-colors">
          <ArrowPathIcon v-if="saving" class="w-4 h-4 animate-spin" />
          <CheckCircleIcon v-else class="w-4 h-4" />
          {{ saving ? 'Saving…' : 'Save Opening Balances' }}
        </button>
        <span v-if="saved" class="text-sm text-green-600 flex items-center gap-1.5">
          <CheckCircleIcon class="w-4 h-4" /> Saved successfully!
        </span>
        <span v-if="error" class="text-sm text-red-600">{{ error }}</span>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { ScaleIcon, ArrowPathIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'

const loading  = ref(true)
const saving   = ref(false)
const saved    = ref(false)
const error    = ref('')

const accounts  = ref([])
const balances  = ref({})
const asOfDate  = ref(new Date().toISOString().split('T')[0])

const assets      = computed(() => accounts.value.filter(a => a.type === 'asset'))
const liabilities = computed(() => accounts.value.filter(a => a.type === 'liability'))
const equity      = computed(() => accounts.value.filter(a => a.type === 'equity'))

const totalAssets     = computed(() => assets.value.reduce((s, a) => s + (balances.value[a.id] || 0), 0))
const totalLiabEquity = computed(() => [...liabilities.value, ...equity.value].reduce((s, a) => s + (balances.value[a.id] || 0), 0))
const difference      = computed(() => totalAssets.value - totalLiabEquity.value)

function fmt(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(async () => {
  const { data } = await axios.get('/api/opening-balances')
  accounts.value = data.accounts
  if (data.entry_date) asOfDate.value = data.entry_date
  const init = {}
  data.accounts.forEach(a => { init[a.id] = data.balances[a.id] ?? null })
  balances.value = init
  loading.value = false
})

async function save() {
  saving.value = true
  saved.value  = false
  error.value  = ''
  try {
    const payload = Object.entries(balances.value)
      .map(([account_id, amount]) => ({ account_id: Number(account_id), amount: amount || 0 }))
    await axios.post('/api/opening-balances', { date: asOfDate.value, balances: payload })
    saved.value = true
    setTimeout(() => { saved.value = false }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save.'
  } finally {
    saving.value = false
  }
}
</script>
