<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex gap-3">
        <input v-model="search" type="search" placeholder="PO number…" class="form-input w-44" @input="debouncedFetch" />
        <select v-model="supplierFilter" class="form-input w-44" @change="fetch">
          <option value="">All suppliers</option>
          <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <router-link to="/purchases/new" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Purchase
      </router-link>
    </div>

    <!-- Cheque due soon banner -->
    <div v-if="dueSoonCheques.length" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 space-y-2">
      <div class="flex items-center gap-2 text-amber-800 font-semibold text-sm">
        <ExclamationTriangleIcon class="w-4 h-4 shrink-0" />
        {{ dueSoonCheques.length }} cheque{{ dueSoonCheques.length > 1 ? 's' : '' }} need{{ dueSoonCheques.length === 1 ? 's' : '' }} to be settled soon
      </div>
      <div v-for="c in dueSoonCheques" :key="c.id"
        class="flex items-center justify-between text-xs text-amber-700 bg-amber-100 rounded-lg px-3 py-2">
        <span>
          <span class="font-semibold">{{ c.purchase_number }}</span>
          · {{ c.supplier?.name }}
          · Cheque #{{ c.cheque_number }} ({{ c.cheque_bank_name }})
        </span>
        <span :class="chequeUrgencyClass(c)" class="font-semibold">
          {{ chequeDueLabel(c) }}
        </span>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr>
          <th class="table-th">PO Number</th><th class="table-th">Supplier</th>
          <th class="table-th">Date</th><th class="table-th">Total</th>
          <th class="table-th">Payment</th><th class="table-th">Status</th><th class="table-th">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
          <template v-if="loading">
            <tr v-for="n in 7" :key="n" class="animate-pulse">
              <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded font-mono"></div></td>
              <td class="table-td"><div class="h-3.5 w-24 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-16 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-16 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-5 w-16 bg-gray-200 rounded-full"></div></td>
              <td class="table-td">
                <div class="flex items-center gap-2">
                  <div class="h-6 w-14 bg-gray-200 rounded-md"></div>
                  <div class="h-6 w-24 bg-gray-200 rounded-md"></div>
                  <div class="h-6 w-24 bg-gray-200 rounded-md"></div>
                </div>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr v-for="p in purchases.data" :key="p.id"
              :class="isDueSoon(p) ? 'bg-amber-50 hover:bg-amber-100' : 'hover:bg-gray-50'">
              <td class="table-td font-mono text-xs font-medium">{{ p.purchase_number }}</td>
              <td class="table-td">{{ p.supplier?.name }}</td>
              <td class="table-td text-gray-500 text-xs">{{ fmtDate(p.purchased_at) }}</td>
              <td class="table-td font-semibold">LKR {{ Number(p.total).toLocaleString() }}</td>
              <td class="table-td text-xs text-gray-600">
                <div class="flex flex-col gap-0.5">
                  <span class="uppercase">{{ p.payment_method || 'cash' }}</span>
                  <span v-if="p.cheque_settled_at" class="text-green-600 font-semibold flex items-center gap-1">
                    <CheckCircleIcon class="w-3 h-3" /> Settled
                  </span>
                  <span v-else-if="isDueSoon(p)" :class="chequeUrgencyClass(p)"
                    class="flex items-center gap-1 font-semibold">
                    <ExclamationTriangleIcon class="w-3 h-3" />{{ chequeDueLabel(p) }}
                  </span>
                </div>
              </td>
              <td class="table-td">
                <span :class="statusClass(p.status)" class="badge">{{ p.status }}</span>
              </td>
              <td class="table-td">
                <div class="flex items-center gap-2">
                  <button @click="openView(p)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <EyeIcon class="w-3.5 h-3.5" /> View
                  </button>
                  <button
                    v-if="p.payment_method === 'cheque' && !p.cheque_settled_at"
                    @click="openSettle(p)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700 hover:bg-purple-200 whitespace-nowrap"
                  >
                    <BanknotesIcon class="w-3.5 h-3.5" /> Settle Cheque
                  </button>
                  <button
                    v-if="p.status !== 'received' && p.status !== 'cancelled'"
                    @click="openGrn(p)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 whitespace-nowrap"
                  >
                    <CheckCircleIcon class="w-3.5 h-3.5" /> Receive GRN
                  </button>
                  <button @click="del(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!purchases.data?.length"><td colspan="7" class="table-td text-center text-gray-400 py-8">No purchases</td></tr>
          </template>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ purchases.total ?? 0 }} records</span>
        <div class="flex gap-2">
          <button @click="page--; fetch()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetch()" :disabled="page>=purchases.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- View Modal -->
    <Teleport to="body">
      <div v-if="view.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">

          <!-- Header -->
          <div class="flex items-start justify-between px-6 py-4 border-b">
            <div>
              <h2 class="text-lg font-semibold text-gray-800">{{ view.data?.purchase_number }}</h2>
              <p class="text-xs text-gray-500 mt-0.5">{{ fmtDate(view.data?.purchased_at) }} · {{ view.data?.user?.name }}</p>
            </div>
            <button @click="view.open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none mt-0.5">✕</button>
          </div>

          <div v-if="view.loading" class="px-6 py-12 text-center text-gray-400 text-sm">Loading…</div>

          <div v-else class="px-6 py-4 space-y-5">
            <!-- Summary row -->
            <div class="grid grid-cols-3 gap-4 text-sm">
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-400 mb-1">Supplier</p>
                <p class="font-semibold text-gray-800">{{ view.data?.supplier?.name ?? '—' }}</p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-400 mb-1">Payment</p>
                <p class="font-semibold text-gray-800 uppercase">{{ view.data?.payment_method || 'cash' }}</p>
                <p v-if="view.data?.cheque_number" class="text-xs text-gray-500 mt-0.5">
                  {{ view.data.cheque_number }} · {{ view.data.cheque_bank_name }}
                </p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-400 mb-1">Status</p>
                <span :class="statusClass(view.data?.status)" class="badge">{{ view.data?.status }}</span>
              </div>
            </div>

            <!-- Items table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="table-th">Product</th>
                    <th class="table-th">SKU</th>
                    <th class="table-th text-center">Qty</th>
                    <th class="table-th text-right">Buy Price</th>
                    <th class="table-th text-right">Sell Price</th>
                    <th class="table-th text-right">Line Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="item in view.data?.items" :key="item.id" class="hover:bg-gray-50">
                    <td class="table-td font-medium">
                      <div class="flex items-center gap-2">
                        <img v-if="item.product?.image" :src="item.product.image"
                          class="w-8 h-8 rounded object-cover border border-gray-200 shrink-0" />
                        <div v-else class="w-8 h-8 rounded bg-gray-100 border border-gray-200 shrink-0 flex items-center justify-center text-[9px] text-gray-400">IMG</div>
                        {{ item.product?.name ?? '—' }}
                      </div>
                    </td>
                    <td class="table-td font-mono text-xs text-gray-500">{{ item.product?.sku ?? '—' }}</td>
                    <td class="table-td text-center">{{ item.quantity }}</td>
                    <td class="table-td text-right">LKR {{ Number(item.unit_cost).toLocaleString() }}</td>
                    <td class="table-td text-right text-gold-700 font-medium">LKR {{ Number(item.selling_price ?? 0).toLocaleString() }}</td>
                    <td class="table-td text-right font-semibold">LKR {{ Number(item.total).toLocaleString() }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50 border-t text-sm">
                  <tr>
                    <td colspan="5" class="table-td text-right text-gray-500">Subtotal</td>
                    <td class="table-td text-right">LKR {{ Number(view.data?.subtotal ?? 0).toLocaleString() }}</td>
                  </tr>
                  <tr v-if="view.data?.tax">
                    <td colspan="5" class="table-td text-right text-gray-500">Tax</td>
                    <td class="table-td text-right">LKR {{ Number(view.data.tax).toLocaleString() }}</td>
                  </tr>
                  <tr class="font-bold text-gray-800">
                    <td colspan="5" class="table-td text-right">Total</td>
                    <td class="table-td text-right text-base">LKR {{ Number(view.data?.total ?? 0).toLocaleString() }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Notes -->
            <div v-if="view.data?.notes" class="bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 text-sm text-amber-800">
              <span class="font-semibold">Notes: </span>{{ view.data.notes }}
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Settle Cheque Modal -->
    <Teleport to="body">
      <div v-if="settle.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Settle Cheque</h2>
            <button @click="settle.open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
          </div>
          <div class="px-6 py-4 space-y-4">
            <!-- Cheque summary -->
            <div class="bg-purple-50 border border-purple-100 rounded-lg px-4 py-3 text-sm space-y-1">
              <div class="flex justify-between"><span class="text-gray-500">PO</span><span class="font-semibold">{{ settle.purchase?.purchase_number }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Supplier</span><span>{{ settle.purchase?.supplier?.name }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Cheque #</span><span class="font-mono">{{ settle.purchase?.cheque_number }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Bank</span><span>{{ settle.purchase?.cheque_bank_name }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Cheque Date</span><span>{{ settle.purchase?.cheque_date }}</span></div>
              <div class="flex justify-between font-bold text-gray-800 pt-1 border-t border-purple-100 mt-1">
                <span>Amount</span><span>LKR {{ Number(settle.purchase?.total ?? 0).toLocaleString() }}</span>
              </div>
            </div>

            <!-- Account selector -->
            <div>
              <label class="form-label">Settle From Account <span class="text-red-500">*</span></label>
              <select v-model="settle.account_id" class="form-input">
                <option value="">— select account —</option>
                <option v-for="a in accounts" :key="a.id" :value="a.id">
                  {{ a.code }} – {{ a.name }}
                </option>
              </select>
            </div>

            <!-- GL info -->
            <div class="bg-gray-50 rounded-lg px-3 py-2 text-xs text-gray-500">
              GL entry on settle:
              <span class="font-medium text-gray-700">
                Dr Cheques Payable (2050) → Cr {{ settleAccountLabel }}
              </span>
            </div>

            <div>
              <label class="form-label">Settlement Date</label>
              <input v-model="settle.date" type="date" class="form-input" />
            </div>
            <div>
              <label class="form-label">Notes (optional)</label>
              <input v-model="settle.notes" type="text" class="form-input" placeholder="e.g. Cleared via Bank XYZ" />
            </div>

            <p v-if="settle.error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ settle.error }}</p>

            <div class="flex justify-end gap-3 pt-1">
              <button @click="settle.open = false" class="btn-secondary">Cancel</button>
              <button @click="submitSettle" :disabled="settle.saving" class="btn-primary bg-purple-600 hover:bg-purple-700">
                {{ settle.saving ? 'Settling…' : 'Confirm Settlement & Post to GL' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- GRN Modal -->
    <Teleport to="body">
      <div v-if="grn.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <div>
              <h2 class="text-lg font-semibold text-gray-800">Receive GRN — {{ grn.purchase?.purchase_number }}</h2>
              <p class="text-xs text-gray-500 mt-0.5">Confirm quantities and set buy / sell prices. Stock and product prices will update on save.</p>
            </div>
            <button @click="grn.open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
          </div>

          <div class="px-6 py-4 space-y-4">
            <!-- Items table -->
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="table-th">Product</th>
                    <th class="table-th w-20">Ordered</th>
                    <th class="table-th w-20">Received Qty</th>
                    <th class="table-th w-32">Buy Price (LKR)</th>
                    <th class="table-th w-32">Sell Price (LKR)</th>
                    <th class="table-th w-28">Line Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="row in grn.items" :key="row.id">
                    <td class="table-td font-medium">{{ row.product_name }}</td>
                    <td class="table-td text-center text-gray-500">{{ row.ordered_qty }}</td>
                    <td class="table-td">
                      <input v-model.number="row.quantity" type="number" min="0" class="form-input w-full" />
                    </td>
                    <td class="table-td">
                      <input v-model.number="row.unit_cost" type="number" min="0" class="form-input w-full" @input="calcLine(row)" />
                    </td>
                    <td class="table-td">
                      <input v-model.number="row.selling_price" type="number" min="0" class="form-input w-full" />
                    </td>
                    <td class="table-td font-semibold text-gray-700">
                      LKR {{ (row.unit_cost * row.quantity).toLocaleString() }}
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50 border-t font-semibold">
                  <tr>
                    <td colspan="5" class="table-td text-right">Total</td>
                    <td class="table-td">LKR {{ grnTotal.toLocaleString() }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="grn.notes" rows="2" class="form-input"></textarea>
            </div>

            <p v-if="grn.error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ grn.error }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button @click="grn.open = false" class="btn-secondary">Cancel</button>
              <button @click="submitGrn" :disabled="grn.saving" class="btn-primary">
                {{ grn.saving ? 'Saving…' : 'Confirm Receipt & Update Stock' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

import axios from 'axios'
import { PlusIcon, TrashIcon, CheckCircleIcon, EyeIcon, ExclamationTriangleIcon, BanknotesIcon } from '@heroicons/vue/24/outline'
import { fmtDate } from '../utils/date.js'

const purchases      = ref({ data: [] })
const suppliers      = ref([])
const accounts       = ref([])
const search         = ref(''); const page = ref(1)
const supplierFilter = ref('')
const loading        = ref(false)

// Cheque due-soon helpers
function chequeDaysUntil(p) {
  if (p.payment_method !== 'cheque' || !p.cheque_date) return null
  const today = new Date(); today.setHours(0,0,0,0)
  const due   = new Date(p.cheque_date); due.setHours(0,0,0,0)
  return Math.round((due - today) / 86400000)
}
function isDueSoon(p) {
  const d = chequeDaysUntil(p)
  return d !== null && d <= 2 && !p.cheque_settled_at
}
function chequeDueLabel(p) {
  const d = chequeDaysUntil(p)
  if (d === null) return ''
  if (d < 0)  return `Overdue by ${Math.abs(d)}d`
  if (d === 0) return 'Due today!'
  if (d === 1) return 'Due tomorrow'
  return `Due in ${d} days`
}
function chequeUrgencyClass(p) {
  const d = chequeDaysUntil(p)
  if (d === null) return ''
  return d < 0 ? 'text-red-600' : d === 0 ? 'text-red-500' : 'text-amber-600'
}

const dueSoonCheques = computed(() =>
  (purchases.value.data ?? []).filter(p => isDueSoon(p))
)

const settle = reactive({ open: false, saving: false, error: '', purchase: null, date: '', notes: '', account_id: '' })

const settleAccountLabel = computed(() => {
  const a = accounts.value.find(x => x.id === settle.account_id)
  return a ? `${a.name} (${a.code})` : 'Bank (1010)'
})

function openSettle(p) {
  settle.purchase   = p
  settle.date       = p.cheque_date ?? new Date().toISOString().slice(0, 10)
  settle.notes      = ''
  settle.error      = ''
  settle.account_id = ''
  settle.open       = true
}

async function submitSettle() {
  if (!settle.account_id) { settle.error = 'Please select the account to settle from.'; return }
  settle.saving = true; settle.error = ''
  try {
    await axios.post(`/api/purchases/${settle.purchase.id}/settle-cheque`, {
      settled_date: settle.date,
      notes:        settle.notes,
      account_id:   settle.account_id,
    })
    settle.open = false
    fetch()
  } catch (e) {
    settle.error = e.response?.data?.message ?? 'Failed to settle cheque'
  } finally { settle.saving = false }
}

const view = reactive({ open: false, loading: false, data: null })

async function openView(purchase) {
  view.data = null; view.loading = true; view.open = true
  const { data } = await axios.get(`/api/purchases/${purchase.id}`)
  view.data = data
  view.loading = false
}

const grn = reactive({
  open: false, saving: false, error: '',
  purchase: null, items: [], notes: '',
})

const grnTotal = computed(() =>
  grn.items.reduce((sum, r) => sum + r.unit_cost * r.quantity, 0)
)

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value=1; fetch() }, 400) }

async function fetch() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/purchases', { params: { page: page.value, search: search.value, supplier_id: supplierFilter.value } })
    purchases.value = data
  } finally {
    loading.value = false
  }
}

function statusClass(s) {
  return { received:'bg-green-100 text-green-700', pending:'bg-yellow-100 text-yellow-700', partial:'bg-blue-100 text-blue-700', cancelled:'bg-red-100 text-red-700' }[s] ?? 'bg-gray-100 text-gray-700'
}

async function openGrn(purchase) {
  const { data } = await axios.get(`/api/purchases/${purchase.id}`)
  grn.purchase = data
  grn.notes    = data.notes ?? ''
  grn.error    = ''
  grn.items    = data.items.map(i => ({
    id:            i.id,
    product_name:  i.product?.name ?? '—',
    ordered_qty:   i.quantity,
    quantity:      i.quantity,
    unit_cost:     i.unit_cost,
    selling_price: i.selling_price ?? i.product?.selling_price ?? 0,
  }))
  grn.open = true
}

function calcLine(row) {
  // just triggers reactivity — totals are computed
}

async function submitGrn() {
  grn.saving = true; grn.error = ''
  try {
    await axios.post(`/api/purchases/${grn.purchase.id}/receive`, {
      items: grn.items.map(r => ({
        id:            r.id,
        quantity:      r.quantity,
        unit_cost:     r.unit_cost,
        selling_price: r.selling_price,
      })),
      notes: grn.notes,
    })
    grn.open = false
    fetch()
  } catch (e) {
    grn.error = e.response?.data?.message ?? 'Failed to receive GRN'
  } finally {
    grn.saving = false
  }
}

async function del(p) {
  if (!confirm(`Delete purchase order ${p.purchase_number}?`)) return
  await axios.delete(`/api/purchases/${p.id}`); fetch()
}

onMounted(async () => {
  const [suppRes, accRes] = await Promise.all([
    axios.get('/api/suppliers/all'),
    axios.get('/api/accounts/all'),
  ])
  suppliers.value = suppRes.data
  accounts.value  = accRes.data
  fetch()
})
</script>
