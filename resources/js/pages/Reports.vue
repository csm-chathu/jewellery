<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Reports</h2>
        <p class="text-sm text-gray-500 mt-0.5">Metal balance, profit/loss analysis, and sales summary</p>
      </div>
      <div class="flex gap-2">
        <button @click="activeTab = 'metal'" :class="tabClass('metal')">Metal Balance</button>
        <button @click="activeTab = 'pnl'" :class="tabClass('pnl')">Rate P&L</button>
        <button @click="activeTab = 'sales'" :class="tabClass('sales')">Sales Summary</button>
        <button @click="activeTab = 'tax'" :class="tabClass('tax')">Tax Settings</button>
      </div>
    </div>

    <!-- Metal Balance Report -->
    <div v-if="activeTab === 'metal'" class="space-y-4">
      <div v-if="metalData" class="space-y-4">
        <!-- Summary cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Gold Weight</p>
            <p class="text-2xl font-bold text-gold-700 mt-1">{{ metalData.totals.total_weight_g }}g</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Gold Value @ Rate</p>
            <p class="text-xl font-bold text-gold-600 mt-1">
              {{ metalData.totals.gold_value_lkr ? 'LKR ' + lkr(metalData.totals.gold_value_lkr) : '—' }}
            </p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Cost Value (Purchase)</p>
            <p class="text-xl font-bold text-gray-700 mt-1">LKR {{ lkr(metalData.totals.cost_value_lkr) }}</p>
          </div>
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Sell Value (Retail)</p>
            <p class="text-xl font-bold text-green-700 mt-1">LKR {{ lkr(metalData.totals.sell_value_lkr) }}</p>
          </div>
        </div>

        <!-- Gold rate notice -->
        <div v-if="metalData.gold_rate" class="bg-gold-50 border border-gold-200 rounded-xl px-4 py-3 text-sm text-gold-800">
          Based on today's rate: <strong>LKR {{ lkr(metalData.gold_rate.rate_per_gram) }}/g (24K)</strong>
          &nbsp;·&nbsp; {{ metalData.date }}
        </div>
        <div v-else class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-sm text-yellow-700">
          ⚠ No gold rate set for today — gold value calculations unavailable.
          <router-link to="/gold-rates" class="underline font-medium ml-1">Set rate →</router-link>
        </div>

        <!-- By karat table -->
        <div class="card p-0 overflow-hidden">
          <div class="px-6 py-4 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-700">Metal Balance by Karat</h3>
          </div>
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Karat</th>
                <th class="table-th">Purity</th>
                <th class="table-th">Pieces</th>
                <th class="table-th">Total Weight (g)</th>
                <th class="table-th">Gold Value (LKR)</th>
                <th class="table-th">Cost Value (LKR)</th>
                <th class="table-th">Sell Value (LKR)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in metalData.by_karat" :key="row.karat" class="hover:bg-gray-50">
                <td class="table-td font-bold text-gold-700 uppercase">{{ row.karat }}</td>
                <td class="table-td text-gray-500">{{ row.purity }}%</td>
                <td class="table-td">{{ row.piece_count }} pcs</td>
                <td class="table-td font-mono">{{ row.total_weight_g }}g</td>
                <td class="table-td font-semibold text-gold-700">{{ row.gold_value_lkr ? lkr(row.gold_value_lkr) : '—' }}</td>
                <td class="table-td text-gray-600">{{ lkr(row.cost_value_lkr) }}</td>
                <td class="table-td text-green-700">{{ lkr(row.sell_value_lkr) }}</td>
              </tr>
              <tr class="bg-gray-50 font-semibold">
                <td class="table-td" colspan="3">Totals</td>
                <td class="table-td font-mono">{{ metalData.totals.total_weight_g }}g</td>
                <td class="table-td text-gold-700">{{ metalData.totals.gold_value_lkr ? lkr(metalData.totals.gold_value_lkr) : '—' }}</td>
                <td class="table-td">{{ lkr(metalData.totals.cost_value_lkr) }}</td>
                <td class="table-td text-green-700">{{ lkr(metalData.totals.sell_value_lkr) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- Rate P&L Report -->
    <div v-if="activeTab === 'pnl'" class="space-y-4">
      <div v-if="pnlData" class="space-y-4">
        <!-- Summary -->
        <div class="grid grid-cols-3 gap-4">
          <div class="card text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Unrealized P&L</p>
            <p class="text-2xl font-bold mt-1"
              :class="pnlData.total_unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ pnlData.total_unrealized_pnl >= 0 ? '+' : '' }}LKR {{ lkr(pnlData.total_unrealized_pnl) }}
            </p>
          </div>
          <div class="card text-center col-span-2">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Based on Gold Rate</p>
            <p class="text-xl font-semibold text-gold-700 mt-1">
              LKR {{ lkr(pnlData.gold_rate?.rate_per_gram) }}/g (24K) · {{ pnlData.date }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Unrealized P&L = Current gold value − Purchase price</p>
          </div>
        </div>

        <div class="card p-0 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Product</th>
                <th class="table-th">Karat</th>
                <th class="table-th">Weight (g)</th>
                <th class="table-th">Stock</th>
                <th class="table-th">Cost/Unit</th>
                <th class="table-th">Gold Value Now</th>
                <th class="table-th">P&L / Unit</th>
                <th class="table-th">Total P&L</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="r in pnlData.products" :key="r.id" class="hover:bg-gray-50">
                <td class="table-td font-medium text-sm">{{ r.name }}</td>
                <td class="table-td text-gold-700 uppercase">{{ r.karat }}</td>
                <td class="table-td font-mono text-sm">{{ r.weight_g }}g</td>
                <td class="table-td">{{ r.stock }}</td>
                <td class="table-td text-gray-600">LKR {{ lkr(r.cost_per_unit) }}</td>
                <td class="table-td text-gold-700">LKR {{ lkr(r.gold_value_now) }}</td>
                <td class="table-td">
                  <span :class="(r.gold_value_now - r.cost_per_unit) >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ (r.gold_value_now - r.cost_per_unit) >= 0 ? '+' : '' }}LKR {{ lkr(r.gold_value_now - r.cost_per_unit) }}
                    <span v-if="r.pnl_percent" class="text-xs ml-1">({{ r.pnl_percent > 0 ? '+' : '' }}{{ r.pnl_percent }}%)</span>
                  </span>
                </td>
                <td class="table-td font-semibold">
                  <span :class="r.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ r.unrealized_pnl >= 0 ? '+' : '' }}LKR {{ lkr(r.unrealized_pnl) }}
                  </span>
                </td>
              </tr>
              <tr v-if="!pnlData.products.length">
                <td colspan="8" class="table-td text-center text-gray-400 py-8">No gold products in stock</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="pnlError" class="card text-center text-yellow-600 py-12">{{ pnlError }}</div>
      <div v-else class="card text-center text-gray-400 py-12">Loading…</div>
    </div>

    <!-- Sales Summary -->
    <div v-if="activeTab === 'sales'" class="space-y-4">
      <div class="card flex gap-4 items-end flex-wrap">
        <div>
          <label class="form-label">From</label>
          <input v-model="salesFrom" type="date" class="form-input" />
        </div>
        <div>
          <label class="form-label">To</label>
          <input v-model="salesTo" type="date" class="form-input" />
        </div>
        <button @click="loadSalesSummary" class="btn-primary">Generate</button>
      </div>

      <div v-if="salesSummary" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card text-center">
          <p class="text-xs text-gray-500">Transactions</p>
          <p class="text-2xl font-bold text-gray-800 mt-1">{{ salesSummary.totals.count }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500">Total Revenue</p>
          <p class="text-xl font-bold text-gold-700 mt-1">LKR {{ lkr(salesSummary.totals.total_revenue) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500">Gold Value</p>
          <p class="text-xl font-bold text-gold-600 mt-1">LKR {{ lkr(salesSummary.totals.gold_value) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500">Gemstone Value</p>
          <p class="text-xl font-bold text-purple-600 mt-1">LKR {{ lkr(salesSummary.totals.gemstone_value) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500">Making Charges</p>
          <p class="text-xl font-bold text-blue-600 mt-1">LKR {{ lkr(salesSummary.totals.making_charges) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500">Wastage Collected</p>
          <p class="text-xl font-bold text-orange-600 mt-1">LKR {{ lkr(salesSummary.totals.wastage) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500">Total Tax</p>
          <p class="text-xl font-bold text-red-600 mt-1">LKR {{ lkr(salesSummary.totals.total_tax) }}</p>
        </div>
        <div class="card text-center">
          <p class="text-xs text-gray-500">Total Discounts</p>
          <p class="text-xl font-bold text-gray-600 mt-1">LKR {{ lkr(salesSummary.totals.total_discount) }}</p>
        </div>
      </div>
    </div>

    <!-- Tax Settings -->
    <div v-if="activeTab === 'tax'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="font-semibold text-gray-700">VAT / Tax Settings</h3>
        <button @click="openTaxModal(null)" class="btn-primary text-sm flex items-center gap-2">
          <PlusIcon class="w-4 h-4" /> Add Tax
        </button>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Name</th>
              <th class="table-th">Rate (%)</th>
              <th class="table-th">Applies To</th>
              <th class="table-th">Status</th>
              <th class="table-th">Description</th>
              <th class="table-th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="t in taxList" :key="t.id" class="hover:bg-gray-50">
              <td class="table-td font-semibold">{{ t.name }}</td>
              <td class="table-td text-blue-700 font-mono">{{ t.rate }}%</td>
              <td class="table-td capitalize text-sm">{{ t.applies_to.replace('_', ' ') }}</td>
              <td class="table-td">
                <span :class="t.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="badge">
                  {{ t.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="table-td text-gray-400 text-sm">{{ t.description ?? '—' }}</td>
              <td class="table-td text-right">
                <div class="flex justify-end gap-1.5">
                  <button @click="openTaxModal(t)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </button>
                  <button @click="deleteTax(t)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tax modal -->
      <div v-if="showTaxModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold">{{ editingTax ? 'Edit Tax' : 'Add Tax Setting' }}</h3>
            <button @click="showTaxModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <form @submit.prevent="saveTax" class="p-6 space-y-4">
            <div>
              <label class="form-label">Name *</label>
              <input v-model="taxForm.name" required class="form-input" placeholder="e.g. VAT, GST" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Rate (%) *</label>
                <input v-model.number="taxForm.rate" type="number" min="0" max="100" step="0.01" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Applies To *</label>
                <select v-model="taxForm.applies_to" required class="form-input">
                  <option value="all">All</option>
                  <option value="gold_only">Gold Only</option>
                  <option value="gemstone_only">Gemstone Only</option>
                  <option value="making_charges">Making Charges</option>
                </select>
              </div>
            </div>
            <div>
              <label class="form-label">Description</label>
              <input v-model="taxForm.description" class="form-input" />
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="taxForm.is_active" class="w-4 h-4 rounded" />
              <span class="text-sm font-medium text-gray-700">Active (available at POS)</span>
            </label>
            <p v-if="taxError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ taxError }}</p>
            <div class="flex gap-3">
              <button type="button" @click="showTaxModal = false" class="btn-secondary flex-1">Cancel</button>
              <button type="submit" :disabled="taxSaving" class="btn-primary flex-1">
                {{ taxSaving ? 'Saving…' : 'Save' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const activeTab = ref('metal')

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function tabClass(tab) {
  return activeTab.value === tab
    ? 'btn-primary text-sm px-3 py-1.5'
    : 'btn-secondary text-sm px-3 py-1.5'
}

// --- Metal Balance ---
const metalData = ref(null)

async function loadMetal() {
  const { data } = await axios.get('/api/reports/metal-balance')
  metalData.value = data
}

// --- P&L ---
const pnlData  = ref(null)
const pnlError = ref('')

async function loadPnl() {
  try {
    const { data } = await axios.get('/api/reports/rate-pnl')
    pnlData.value = data
  } catch (e) {
    pnlError.value = e.response?.data?.message ?? 'Failed to load P&L data'
  }
}

// --- Sales Summary ---
const salesSummary = ref(null)
const salesFrom    = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const salesTo      = ref(new Date().toISOString().split('T')[0])

async function loadSalesSummary() {
  const { data } = await axios.get('/api/reports/sales-summary', { params: { date_from: salesFrom.value, date_to: salesTo.value } })
  salesSummary.value = data
}

// --- Tax Settings ---
const taxList      = ref([])
const showTaxModal = ref(false)
const editingTax   = ref(null)
const taxSaving    = ref(false)
const taxError     = ref('')

const taxForm = reactive({ name: '', rate: 0, applies_to: 'all', is_active: true, description: '' })

async function loadTaxes() {
  const { data } = await axios.get('/api/tax-settings')
  taxList.value = data
}

function openTaxModal(t) {
  editingTax.value = t; taxError.value = ''
  Object.assign(taxForm, { name: t?.name ?? '', rate: t?.rate ?? 0, applies_to: t?.applies_to ?? 'all', is_active: t?.is_active ?? true, description: t?.description ?? '' })
  showTaxModal.value = true
}

async function saveTax() {
  taxSaving.value = true; taxError.value = ''
  try {
    if (editingTax.value) await axios.put(`/api/tax-settings/${editingTax.value.id}`, taxForm)
    else                  await axios.post('/api/tax-settings', taxForm)
    showTaxModal.value = false
    loadTaxes()
  } catch (e) {
    taxError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
  } finally { taxSaving.value = false }
}

async function deleteTax(t) {
  if (!confirm(`Delete tax "${t.name}"?`)) return
  await axios.delete(`/api/tax-settings/${t.id}`)
  loadTaxes()
}

onMounted(() => { loadMetal(); loadPnl(); loadSalesSummary(); loadTaxes() })
</script>
