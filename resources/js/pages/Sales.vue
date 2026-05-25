<template>
  <div class="space-y-5">

    <!-- Header + actions -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
          <input v-model="search" type="search" placeholder="Search invoice, customer…"
            class="form-input pl-8 w-52" @input="debouncedFetch" />
        </div>
        <input v-model="dateFrom" type="date" class="form-input w-36" @change="fetchData" title="From date" />
        <span class="text-gray-400 text-xs">to</span>
        <input v-model="dateTo"   type="date" class="form-input w-36" @change="fetchData" title="To date" />
        <select v-model="statusFilter" class="form-input w-32" @change="fetchData">
          <option value="">All status</option>
          <option value="paid">Paid</option>
          <option value="pending">Pending</option>
          <option value="partial">Partial</option>
          <option value="refunded">Refunded</option>
        </select>
        <select v-model="typeFilter" class="form-input w-32" @change="fetchData">
          <option value="">All types</option>
          <option value="instant">Instant</option>
          <option value="booking">Booking</option>
        </select>
        <button v-if="search || dateFrom || dateTo || statusFilter || typeFilter" @click="clearFilters"
          class="text-xs text-gray-400 hover:text-gray-600 underline">Clear</button>
      </div>
      <router-link to="/sales/new"
        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-sm shadow-sm transition-colors shrink-0">
        <PlusIcon class="w-4 h-4" /> New Sale
      </router-link>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
          <ReceiptPercentIcon class="w-5 h-5 text-blue-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Sales</p>
          <p class="text-2xl font-bold text-gray-800">{{ sales.total ?? 0 }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
          <BanknotesIcon class="w-5 h-5 text-amber-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Revenue</p>
          <p class="text-xl font-bold text-amber-700">LKR {{ totalRevenue }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
          <CheckCircleIcon class="w-5 h-5 text-green-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Paid</p>
          <p class="text-2xl font-bold text-green-700">{{ paidCount }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
          <ChartBarIcon class="w-5 h-5 text-purple-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Avg Sale</p>
          <p class="text-xl font-bold text-purple-700">LKR {{ avgSale }}</p>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th w-36">Invoice</th>
              <th class="table-th">Customer</th>
              <th class="table-th">Items</th>
              <th class="table-th w-28">Date</th>
              <th class="table-th w-24">Type</th>
              <th class="table-th w-36 text-right">Total</th>
              <th class="table-th w-32">Payment</th>
              <th class="table-th w-24">Delivery</th>
              <th class="table-th w-24">Status</th>
              <th class="table-th w-28 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading">
              <td colspan="10" class="table-td text-center py-10 text-gray-400">
                <div class="flex items-center justify-center gap-2">
                  <ArrowPathIcon class="w-4 h-4 animate-spin" /> Loading…
                </div>
              </td>
            </tr>
            <template v-else>
              <tr v-for="s in sales.data" :key="s.id"
                class="hover:bg-amber-50/40 transition-colors cursor-default group">
                <td class="table-td">
                  <span class="font-mono text-xs font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">
                    {{ s.invoice_number }}
                  </span>
                </td>
                <td class="table-td">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 font-bold text-xs shrink-0">
                      {{ (s.customer?.name ?? 'W')[0].toUpperCase() }}
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-800">{{ s.customer?.name ?? 'Walk-in' }}</p>
                      <p v-if="s.customer?.phone" class="text-xs text-gray-400">{{ s.customer.phone }}</p>
                    </div>
                  </div>
                </td>
                <td class="table-td">
                  <div class="space-y-1">
                    <div v-for="item in s.items" :key="item.id" class="text-xs">
                      <span class="font-medium text-gray-800">{{ item.product?.name }}</span>
                      <span v-if="item.product?.category?.name" class="ml-1 text-gray-400">({{ item.product.category.name }})</span>
                      <span v-if="item.product?.weight" class="ml-1 text-amber-600 font-medium">{{ item.product.weight }}g</span>
                      <span v-if="item.quantity > 1" class="ml-1 text-gray-400">×{{ item.quantity }}</span>
                    </div>
                  </div>
                </td>
                <td class="table-td text-xs text-gray-500">
                  <div>{{ fmtDate(s.sold_at) }}</div>
                  <div class="text-gray-400">{{ formatTime(s.sold_at) }}</div>
                </td>
                <td class="table-td">
                  <span class="badge text-xs" :class="s.sale_type === 'booking' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'">
                    {{ s.sale_type || 'instant' }}
                  </span>
                </td>
                <td class="table-td text-right">
                  <span class="font-bold text-amber-700">LKR {{ Number(s.official_total ?? s.total).toLocaleString() }}</span>
                  <p v-if="s.official_total != null && s.official_total !== s.total" class="text-xs text-gray-400 mt-0.5">
                    Billed: {{ Number(s.total).toLocaleString() }}
                  </p>
                </td>
                <td class="table-td">
                  <span :class="methodClass(s.payment_method)"
                    class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                    {{ s.payment_method?.replace('_', ' ') }}
                  </span>
                </td>
                <td class="table-td text-xs">
                  <span class="badge" :class="deliveryClass(s.delivery_status)">{{ s.delivery_status || 'delivered' }}</span>
                </td>
                <td class="table-td">
                  <span :class="statusClass(s.payment_status)" class="badge capitalize">{{ s.payment_status }}</span>
                </td>
                <td class="table-td text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <router-link :to="`/sales/${s.id}`"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                      <PrinterIcon class="w-3.5 h-3.5" /> Receipt
                    </router-link>
                    <button @click="del(s)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                    <button v-if="s.sale_type === 'booking' && s.delivery_status === 'booked'"
                      @click="openSettle(s)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200">
                      Settle
                    </button>
                    <button v-if="['paid','partial'].includes(s.payment_status)"
                      @click="openReturn(s)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-700 hover:bg-orange-200">
                      <ArrowUturnLeftIcon class="w-3.5 h-3.5" /> Return
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!sales.data?.length">
                <td colspan="10" class="table-td text-center py-12">
                  <div class="flex flex-col items-center gap-2 text-gray-400">
                    <ReceiptPercentIcon class="w-10 h-10 opacity-30" />
                    <span>No sales found</span>
                    <router-link to="/sales/new" class="text-amber-600 hover:underline text-sm font-medium">Create your first sale →</router-link>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-5 py-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-sm text-gray-500 bg-gray-50/50">
        <span class="text-xs">
          Showing <strong class="text-gray-700">{{ sales.from ?? 0 }}–{{ sales.to ?? 0 }}</strong>
          of <strong class="text-gray-700">{{ sales.total ?? 0 }}</strong> records
        </span>
        <div class="flex items-center gap-1">
          <button @click="page--; fetchData()" :disabled="page<=1"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border border-gray-200 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            <ChevronLeftIcon class="w-3.5 h-3.5" /> Prev
          </button>
          <span class="px-3 py-1.5 text-xs font-semibold text-gray-700">Page {{ page }} / {{ sales.last_page ?? 1 }}</span>
          <button @click="page++; fetchData()" :disabled="page>=(sales.last_page ?? 1)"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border border-gray-200 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            Next <ChevronRightIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ── Return Modal ── -->
    <div v-if="returnModal" class="fixed inset-0 z-50 bg-black/40 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
          <div>
            <h3 class="font-semibold text-gray-800">Process Return</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ returnSale?.invoice_number }} · {{ returnSale?.customer?.name ?? 'Walk-in' }}</p>
          </div>
          <button @click="returnModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>

        <div class="overflow-y-auto flex-1 p-6 space-y-5">
          <!-- Loading items -->
          <div v-if="returnLoading" class="text-center py-8 text-gray-400">
            <ArrowPathIcon class="w-5 h-5 animate-spin mx-auto mb-2" /> Loading items…
          </div>
          <template v-else>
            <!-- Items selection -->
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Select Items to Return</p>
              <div class="space-y-2">
                <div v-for="item in returnItems" :key="item.sale_item_id"
                  class="flex items-center gap-3 p-3 rounded-lg border"
                  :class="item.returnQty > 0 ? 'border-orange-300 bg-orange-50' : 'border-gray-200'">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ item.product_name }}</p>
                    <p class="text-xs text-gray-400">
                      Sold: {{ item.sold_qty }} &nbsp;·&nbsp;
                      Already returned: {{ item.already_returned }} &nbsp;·&nbsp;
                      Returnable: <span class="font-semibold text-orange-700">{{ item.returnable }}</span>
                    </p>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="text-xs text-gray-500 mb-1">LKR {{ fmtN(item.unit_price) }}/pc</p>
                    <input v-model.number="item.returnQty" type="number" min="0" :max="item.returnable"
                      class="form-input w-20 text-center text-sm"
                      :disabled="item.returnable === 0" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Refund amount preview -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 flex justify-between items-center">
              <span class="text-sm text-gray-600">Calculated Refund Amount</span>
              <span class="text-lg font-bold text-orange-700">LKR {{ fmtN(returnRefundAmount) }}</span>
            </div>

            <!-- Refund method -->
            <div>
              <label class="form-label">Refund Method</label>
              <select v-model="returnForm.refund_method" class="form-input">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="store_credit">Store Credit</option>
                <option value="none">No Refund</option>
              </select>
            </div>

            <!-- Reason -->
            <div>
              <label class="form-label">Reason <span class="text-red-500">*</span></label>
              <input v-model="returnForm.reason" type="text" class="form-input"
                placeholder="e.g. Defective item, Customer changed mind…" />
            </div>

            <!-- Notes -->
            <div>
              <label class="form-label">Notes (optional)</label>
              <textarea v-model="returnForm.notes" class="form-input" rows="2" />
            </div>

            <p v-if="returnError" class="text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ returnError }}</p>
          </template>
        </div>

        <div class="flex justify-end gap-2 px-6 py-4 border-t shrink-0">
          <button @click="returnModal = false" class="btn-secondary">Cancel</button>
          <button @click="submitReturn" :disabled="returnSubmitting || returnRefundAmount === 0"
            class="btn-primary bg-orange-600 hover:bg-orange-700 border-orange-600">
            {{ returnSubmitting ? 'Processing…' : `Process Return — LKR ${fmtN(returnRefundAmount)}` }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="settleModal" class="fixed inset-0 z-50 bg-black/40 p-4 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Settle Booking</h3>
        <p class="text-sm text-gray-500">Invoice {{ settleSale?.invoice_number }} · Remaining: LKR {{ settleRemaining }}</p>
        <div>
          <label class="form-label">Payment Method</label>
          <select v-model="settleForm.payment_method" class="form-input">
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="form-label">Payment Amount</label>
          <input v-model.number="settleForm.payment_amount" type="number" min="0" step="0.01" class="form-input" />
        </div>
        <div>
          <label class="form-label">Delivered At</label>
          <input v-model="settleForm.delivered_at" type="datetime-local" class="form-input" />
        </div>
        <p v-if="settleError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">{{ settleError }}</p>
        <div class="flex justify-end gap-2">
          <button @click="settleModal = false" class="btn-secondary">Cancel</button>
          <button @click="submitSettle" class="btn-primary" :disabled="settling">{{ settling ? 'Posting…' : 'Settle & Deliver' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import {
  PlusIcon, TrashIcon, EyeIcon, MagnifyingGlassIcon,
  ReceiptPercentIcon, BanknotesIcon, CheckCircleIcon, PrinterIcon,
  ChartBarIcon, ArrowPathIcon, ChevronLeftIcon, ChevronRightIcon,
  ArrowUturnLeftIcon,
} from '@heroicons/vue/24/outline'
import { fmtDate } from '../utils/date.js'

const router       = useRouter()
const sales        = ref({ data: [] })
const search       = ref('')
const page         = ref(1)
const dateFrom     = ref('')
const dateTo       = ref('')
const statusFilter = ref('')
const typeFilter   = ref('')
const loading      = ref(false)
const settleModal  = ref(false)
const settleSale   = ref(null)
const settleForm   = ref({ payment_method: 'cash', payment_amount: 0, delivered_at: '' })
const settleError  = ref('')
const settling     = ref(false)

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value = 1; fetchData() }, 400) }

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/sales', {
      params: {
        page:        page.value,
        search:      search.value,
        date_from:   dateFrom.value,
        date_to:     dateTo.value,
        status:      statusFilter.value,
        sale_type:   typeFilter.value,
      },
    })
    sales.value = data
  } finally { loading.value = false }
}

function clearFilters() {
  search.value = ''; dateFrom.value = ''; dateTo.value = ''
  statusFilter.value = ''; typeFilter.value = ''
  page.value = 1; fetchData()
}

const realTotal = (s) => Number(s.official_total ?? s.total)
const totalRevenue = computed(() => {
  const sum = (sales.value.data ?? []).reduce((acc, s) => acc + realTotal(s), 0)
  return Number(sum).toLocaleString()
})
const paidCount = computed(() => (sales.value.data ?? []).filter(s => s.payment_status === 'paid').length)
const avgSale = computed(() => {
  const d = sales.value.data ?? []
  if (!d.length) return '0'
  return Number(d.reduce((a, s) => a + realTotal(s), 0) / d.length).toLocaleString('en-LK', { maximumFractionDigits: 0 })
})

function formatTime(d) {
  return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

function statusClass(s) {
  return {
    paid:     'bg-green-100 text-green-700',
    pending:  'bg-yellow-100 text-yellow-700',
    partial:  'bg-blue-100 text-blue-700',
    refunded: 'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-700'
}
function methodClass(m) {
  return {
    cash:          'bg-green-50 text-green-600',
    card:          'bg-blue-50 text-blue-600',
    bank_transfer: 'bg-purple-50 text-purple-600',
    cheque:        'bg-orange-50 text-orange-600',
  }[m] ?? 'bg-gray-50 text-gray-600'
}

function deliveryClass(s) {
  return {
    delivered: 'bg-green-100 text-green-700',
    booked: 'bg-yellow-100 text-yellow-700',
    cancelled: 'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

const settleRemaining = computed(() => {
  if (!settleSale.value) return '0.00'
  return Number(Math.max(0, Number(settleSale.value.total) - Number(settleSale.value.amount_paid))).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
})

function openSettle(sale) {
  settleSale.value = sale
  settleForm.value = {
    payment_method: 'cash',
    payment_amount: Math.max(0, Number(sale.total) - Number(sale.amount_paid)),
    delivered_at: new Date().toISOString().slice(0, 16),
  }
  settleError.value = ''
  settleModal.value = true
}

async function submitSettle() {
  if (!settleSale.value) return
  settling.value = true
  settleError.value = ''
  try {
    await axios.post(`/api/sales/${settleSale.value.id}/settle-booking`, settleForm.value)
    settleModal.value = false
    router.push(`/sales/${settleSale.value.id}`)
  } catch (e) {
    settleError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Failed to settle booking'
  } finally {
    settling.value = false
  }
}

async function del(s) {
  if (!confirm(`Delete invoice ${s.invoice_number}?\nThis will restore stock. This action cannot be undone.`)) return
  await axios.delete(`/api/sales/${s.id}`)
  fetchData()
}

// ── Sales Return ──────────────────────────────────────────────────────────────

const returnModal      = ref(false)
const returnSale       = ref(null)
const returnItems      = ref([])
const returnLoading    = ref(false)
const returnSubmitting = ref(false)
const returnError      = ref('')
const returnForm       = ref({ refund_method: 'cash', reason: '', notes: '' })

const returnRefundAmount = computed(() => {
  return returnItems.value.reduce((sum, item) => {
    return sum + (item.returnQty > 0 ? item.unit_price * item.returnQty : 0)
  }, 0)
})

function fmtN(v) {
  return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function openReturn(sale) {
  returnSale.value  = sale
  returnItems.value = []
  returnError.value = ''
  returnForm.value  = { refund_method: 'cash', reason: '', notes: '' }
  returnModal.value = true
  returnLoading.value = true

  try {
    // Load full sale with items
    const [{ data: fullSale }, { data: existingReturns }] = await Promise.all([
      axios.get(`/api/sales/${sale.id}`),
      axios.get(`/api/sales/${sale.id}/returns`),
    ])

    // Tally already-returned quantities per sale_item_id
    const returnedQtyMap = {}
    for (const ret of existingReturns) {
      for (const ri of ret.items ?? []) {
        returnedQtyMap[ri.sale_item_id] = (returnedQtyMap[ri.sale_item_id] ?? 0) + ri.quantity
      }
    }

    returnItems.value = fullSale.items.map(item => {
      const alreadyReturned = returnedQtyMap[item.id] ?? 0
      const returnable      = item.quantity - alreadyReturned
      return {
        sale_item_id:     item.id,
        product_name:     item.product?.name ?? `Item #${item.product_id}`,
        sold_qty:         item.quantity,
        already_returned: alreadyReturned,
        returnable,
        unit_price:       returnable > 0 ? item.total / item.quantity : 0,
        returnQty:        0,
      }
    }).filter(i => i.returnable > 0)
  } catch {
    returnError.value = 'Failed to load sale items. Please try again.'
  } finally {
    returnLoading.value = false
  }
}

async function submitReturn() {
  returnError.value = ''
  if (!returnForm.value.reason.trim()) {
    returnError.value = 'Reason is required.'
    return
  }
  const selectedItems = returnItems.value.filter(i => i.returnQty > 0)
  if (!selectedItems.length) {
    returnError.value = 'Select at least one item to return.'
    return
  }

  returnSubmitting.value = true
  try {
    await axios.post(`/api/sales/${returnSale.value.id}/return`, {
      reason:        returnForm.value.reason,
      refund_method: returnForm.value.refund_method,
      notes:         returnForm.value.notes,
      items:         selectedItems.map(i => ({ sale_item_id: i.sale_item_id, quantity: i.returnQty })),
    })
    returnModal.value = false
    fetchData()
  } catch (e) {
    returnError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Failed to process return.'
  } finally {
    returnSubmitting.value = false
  }
}

onMounted(fetchData)
</script>
