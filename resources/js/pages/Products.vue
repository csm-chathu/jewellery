<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <input v-model="search" type="search" placeholder="Search products…" class="form-input w-64" @input="debouncedFetch" />
        <select v-model="categoryFilter" class="form-input w-44" @change="fetchProducts">
          <option value="">All categories</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <label class="flex items-center gap-1 text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" v-model="lowStockOnly" @change="fetchProducts" class="rounded text-gold-600" />
          Low stock only
        </label>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="selected.size > 0"
          @click="printSelected"
          class="btn-primary flex items-center gap-2"
        >
          <PrinterIcon class="w-4 h-4" />
          Print {{ selected.size }} Barcode{{ selected.size === 1 ? '' : 's' }}
        </button>
        <button @click="openCreate" class="btn-primary flex items-center gap-2">
          <PlusIcon class="w-4 h-4" /> Add Product
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th w-8">
                <input type="checkbox" :checked="allPageSelected" @change="toggleAll" class="rounded text-gold-600" />
              </th>
              <th class="table-th">SKU</th>
              <th class="table-th">Barcode</th>
              <th class="table-th">Name</th>
              <th class="table-th">Category</th>
              <th class="table-th">Material / Karat</th>
              <th class="table-th">Weight</th>
              <th class="table-th">Stock</th>
              <th class="table-th">Buy Price</th>
              <th class="table-th">Status</th>
              <th class="table-th">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <!-- Skeleton -->
            <template v-if="loading">
              <tr v-for="n in 8" :key="n" class="animate-pulse">
                <td class="table-td"><div class="h-4 w-4 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-3.5 w-16 bg-gray-200 rounded font-mono"></div></td>
                <td class="table-td"><div class="h-3.5 w-20 bg-gray-100 rounded"></div></td>
                <td class="table-td">
                  <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-md bg-gray-200 shrink-0"></div>
                    <div class="h-3.5 w-28 bg-gray-200 rounded"></div>
                  </div>
                </td>
                <td class="table-td"><div class="h-3.5 w-20 bg-gray-100 rounded"></div></td>
                <td class="table-td"><div class="h-3.5 w-16 bg-gray-100 rounded"></div></td>
                <td class="table-td"><div class="h-3.5 w-10 bg-gray-100 rounded"></div></td>
                <td class="table-td"><div class="h-5 w-10 bg-gray-200 rounded-full"></div></td>
                <td class="table-td"><div class="h-3.5 w-20 bg-gray-100 rounded"></div></td>
                <td class="table-td"><div class="h-5 w-14 bg-gray-200 rounded-full"></div></td>
                <td class="table-td">
                  <div class="flex gap-2">
                    <div class="h-6 w-14 bg-gray-200 rounded-md"></div>
                    <div class="h-6 w-10 bg-gray-200 rounded-md"></div>
                    <div class="h-6 w-14 bg-gray-200 rounded-md"></div>
                  </div>
                </td>
              </tr>
            </template>
            <template v-else>
            <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50" :class="selected.has(p.id) ? 'bg-emerald-50' : ''">
              <td class="table-td">
                <input type="checkbox" :checked="selected.has(p.id)" @change="toggleOne(p)" class="rounded text-gold-600" />
              </td>
              <td class="table-td font-mono text-xs">{{ p.sku }}</td>
              <td class="table-td font-mono text-xs text-gray-700">{{ p.barcode || '—' }}</td>
              <td class="table-td">
                <div class="flex items-center gap-2">
                  <img
                    v-if="p.image"
                    :src="p.image"
                    alt="product"
                    class="w-9 h-9 rounded-md object-cover border border-gray-200"
                  />
                  <div
                    v-else
                    class="w-9 h-9 rounded-md bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] text-gray-400"
                  >
                    IMG
                  </div>
                  <span class="font-medium">{{ p.name }}</span>
                </div>
              </td>
              <td class="table-td text-gray-500">{{ p.category?.name }}</td>
              <td class="table-td">{{ p.material }} {{ p.karat ? `(${p.karat})` : '' }}</td>
              <td class="table-td text-gray-500">{{ p.weight ? Number(p.weight).toFixed(2) + 'g' : '—' }}</td>
              <td class="table-td">
                <span :class="p.stock_quantity <= p.min_stock_level ? 'badge bg-red-100 text-red-700' : 'badge bg-green-100 text-green-700'">
                  {{ p.stock_quantity }}
                </span>
              </td>
              <td class="table-td">LKR {{ Number(p.purchase_price).toLocaleString() }}</td>
              <td class="table-td">
                <span :class="p.is_active ? 'badge bg-green-100 text-green-700' : 'badge bg-gray-100 text-gray-500'">
                  {{ p.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="table-td">
                <div class="flex items-center gap-2">
                  <button @click="printOne(p)" :disabled="printingId === p.id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 whitespace-nowrap disabled:opacity-60">
                    <svg v-if="printingId === p.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    <PrinterIcon v-else class="w-3.5 h-3.5" />
                    {{ printingId === p.id ? 'Printing…' : 'Print' }}
                  </button>
                  <button @click="openEdit(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </button>
                  <button @click="openWriteOff(p)" :disabled="p.stock_quantity < 1"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 disabled:opacity-40 disabled:cursor-not-allowed">
                    <ExclamationTriangleIcon class="w-3.5 h-3.5" /> Damage
                  </button>
                  <button @click="deleteProduct(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!products.data?.length">
              <td colspan="11" class="table-td text-center text-gray-400 py-8">No products found</td>
            </tr>
            </template>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
        <span>{{ products.from }}–{{ products.to }} of {{ products.total }}</span>
        <div class="flex gap-2">
          <button @click="page--; fetchProducts()" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetchProducts()" :disabled="page >= products.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <ProductModal v-if="showModal" :product="editing" :categories="categories" :suppliers="suppliers"
      @close="showModal = false" @saved="onSaved" />

    <!-- Write-Off (Damage) Modal -->
    <Teleport to="body">
      <div v-if="writeOffTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
          <div class="flex items-center justify-between p-5 border-b">
            <div>
              <h2 class="text-lg font-bold text-gray-900">Record Stock Write-Off</h2>
              <p class="text-sm text-gray-500 mt-0.5">
                {{ writeOffTarget.name }} &nbsp;·&nbsp;
                Current stock: <strong>{{ writeOffTarget.stock_quantity }}</strong>
              </p>
            </div>
            <button @click="writeOffTarget = null" class="text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <form @submit.prevent="submitWriteOff" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Quantity *</label>
                <input v-model.number="writeOffForm.quantity" type="number" min="1"
                  :max="writeOffTarget.stock_quantity" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Reason *</label>
                <select v-model="writeOffForm.reason" required class="form-input">
                  <option value="damaged">Damaged</option>
                  <option value="lost">Lost</option>
                  <option value="stolen">Stolen</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>

            <div>
              <label class="form-label">Estimated Value (LKR) *</label>
              <input v-model.number="writeOffForm.estimated_value" type="number" step="0.01" min="0"
                required class="form-input" />
              <p class="text-xs text-gray-400 mt-1">Auto-filled from purchase price × quantity. Adjust if needed.</p>
            </div>

            <div>
              <label class="form-label">Loss / Expense Account (DR) *</label>
              <select v-model="writeOffForm.debit_account_id" required class="form-input">
                <option value="">— Select account —</option>
                <option v-for="a in allAccounts" :key="a.id" :value="a.id">
                  {{ a.code }} — {{ a.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="form-label">Inventory / Asset Account (CR) *</label>
              <select v-model="writeOffForm.credit_account_id" required class="form-input">
                <option value="">— Select account —</option>
                <option v-for="a in allAccounts" :key="a.id" :value="a.id">
                  {{ a.code }} — {{ a.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="writeOffForm.notes" rows="2" class="form-input"
                placeholder="Optional details about the damage…"></textarea>
            </div>

            <p v-if="writeOffError" class="text-sm text-red-600">{{ writeOffError }}</p>

            <div class="flex justify-end gap-3 pt-1">
              <button type="button" @click="writeOffTarget = null" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="writeOffSaving"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-60">
                <ExclamationTriangleIcon class="w-4 h-4" />
                {{ writeOffSaving ? 'Saving…' : 'Record Write-Off' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { PencilSquareIcon, PlusIcon, PrinterIcon, TrashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import JsBarcode from 'jsbarcode'
import ProductModal from '@/components/ProductModal.vue'

const products       = ref({ data: [] })
const categories     = ref([])
const suppliers      = ref([])
const search         = ref('')
const categoryFilter = ref('')
const lowStockOnly   = ref(false)
const page           = ref(1)
const showModal      = ref(false)
const editing        = ref(null)
const printingId     = ref(null)
const selected       = ref(new Set())

// maps product id → product object so we can build labels for selected items
const productMap     = ref({})

// Write-off (damage) state
const writeOffTarget = ref(null)
const writeOffSaving = ref(false)
const writeOffError  = ref('')
const allAccounts    = ref([])
const writeOffForm   = reactive({
  quantity: 1, reason: 'damaged', estimated_value: '',
  debit_account_id: '', credit_account_id: '', notes: '',
})

const allPageSelected = computed(() => {
  const ids = products.value.data?.map(p => p.id) ?? []
  return ids.length > 0 && ids.every(id => selected.value.has(id))
})

function toggleOne(p) {
  const s = new Set(selected.value)
  if (s.has(p.id)) s.delete(p.id)
  else s.add(p.id)
  selected.value = s
}

function toggleAll() {
  const ids = products.value.data?.map(p => p.id) ?? []
  const s = new Set(selected.value)
  if (allPageSelected.value) {
    ids.forEach(id => s.delete(id))
  } else {
    ids.forEach(id => s.add(id))
  }
  selected.value = s
}

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; fetchProducts() }, 400)
}

const loading = ref(false)

async function fetchProducts() {
  loading.value = true
  try {
  const params = { page: page.value, search: search.value, category_id: categoryFilter.value }
  if (lowStockOnly.value) params.low_stock = 1
  const { data } = await axios.get('/api/products', { params })
  products.value = data
  // keep map updated so selected products retain their data
  data.data?.forEach(p => { productMap.value[p.id] = p })
  } finally { loading.value = false }
}

async function fetchRefs() {
  const [c, s, a] = await Promise.all([
    axios.get('/api/categories/all'),
    axios.get('/api/suppliers/all'),
    axios.get('/api/accounts/all'),
  ])
  categories.value = c.data
  suppliers.value  = s.data
  allAccounts.value = a.data
}

function openWriteOff(p) {
  writeOffTarget.value = p
  Object.assign(writeOffForm, {
    quantity: 1, reason: 'damaged', notes: '',
    estimated_value: p.purchase_price ? +p.purchase_price : '',
    debit_account_id: '', credit_account_id: '',
  })
  writeOffError.value = ''
}

watch(() => writeOffForm.quantity, q => {
  const p = writeOffTarget.value
  if (p?.purchase_price && q >= 1) {
    writeOffForm.estimated_value = +(p.purchase_price * q).toFixed(2)
  }
})

async function submitWriteOff() {
  writeOffSaving.value = true; writeOffError.value = ''
  try {
    await axios.post(`/api/products/${writeOffTarget.value.id}/write-off`, writeOffForm)
    writeOffTarget.value = null
    fetchProducts()
  } catch (e) {
    writeOffError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Failed to record write-off.'
  } finally {
    writeOffSaving.value = false
  }
}

function openCreate() { editing.value = null; showModal.value = true }
function openEdit(p)   { editing.value = p;    showModal.value = true }

function createBarcodeSvg(value) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
  JsBarcode(svg, value, {
    format: 'CODE128',
    width: 2,
    height: 60,
    margin: 0,
    marginLeft: 3,
    marginRight: 3,
    displayValue: false,
  })
  svg.setAttribute('preserveAspectRatio', 'none')
  return svg.outerHTML
}

function buildLabelHtml(product) {
  const barcodeValue = product.barcode?.trim() || product.sku
  const barcodeSvg   = createBarcodeSvg(barcodeValue)
  const safeName     = (product.name ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeBarcode  = barcodeValue.replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeWeight   = product.weight != null ? parseFloat(product.weight).toFixed(2) + 'g' : ''
  return `
  <div class="label">
    <div class="header">
      <div class="name">${safeName}</div>
      ${safeWeight ? `<div class="weight">${safeWeight}</div>` : ''}
    </div>
    ${barcodeSvg}
    <div class="sku">${safeBarcode}</div>
  </div>`
}

function buildPrintHtml(labelsList) {
  return `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @media print {
      @page { size: 1.181in 0.787in; margin: 0; }
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      width: 30mm;
      margin: 0; padding: 0;
      background: #fff;
      font-family: Arial, Helvetica, sans-serif;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .label {
      width: 30mm; height: 20mm;
      padding: 1.5mm 1.5mm 0.5mm;
      display: flex; flex-direction: column;
      align-items: center; justify-content: space-between;
      overflow: hidden;
      page-break-after: always;
      break-after: page;
    }
    .label:last-child { page-break-after: avoid; break-after: avoid; }
    .header {
      display: flex; align-items: baseline; justify-content: space-between;
      width: 100%; gap: 1.5mm; flex-shrink: 0; line-height: 1;
    }
    .name {
      font-size: 6.5pt; font-weight: 700;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      flex: 1;
    }
    .weight {
      font-size: 6pt; font-weight: 600; white-space: nowrap; flex-shrink: 0;
      color: #333;
    }
    svg { width: 100%; height: 9.5mm; display: block; flex-shrink: 0; }
    .sku {
      font-size: 8pt; font-weight: 700; letter-spacing: 1px;
      text-align: center; margin-top: 0.4mm; line-height: 1;
    }
  </style>
</head>
<body>
${labelsList.join('\n')}
</body>
</html>`
}

function firePrint(html) {
  if (window.electronAPI?.printBarcode) {
    window.electronAPI.printBarcode(html)
    return
  }
  const iframe = document.createElement('iframe')
  iframe.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;opacity:0;border:none;'
  document.body.appendChild(iframe)
  iframe.contentDocument.open()
  iframe.contentDocument.write(html)
  iframe.contentDocument.close()
  iframe.contentWindow.addEventListener('load', () => {
    iframe.contentWindow.print()
    setTimeout(() => document.body.removeChild(iframe), 2000)
  })
}

function printOne(product) {
  if (!product?.sku) return
  printingId.value = product.id
  firePrint(buildPrintHtml([buildLabelHtml(product)]))
  setTimeout(() => { printingId.value = null }, 2500)
}

function printSelected() {
  const items = [...selected.value]
    .map(id => productMap.value[id])
    .filter(p => p?.sku)
  if (!items.length) return
  firePrint(buildPrintHtml(items.map(buildLabelHtml)))
  selected.value = new Set()
}

async function deleteProduct(p) {
  if (!confirm(`Delete "${p.name}"?`)) return
  await axios.delete(`/api/products/${p.id}`)
  fetchProducts()
}

async function onSaved(payload) {
  showModal.value = false
  await fetchProducts()
  if (payload?.isNew && payload?.product) {
    printOne(payload.product)
  }
}

onMounted(() => { fetchProducts(); fetchRefs() })
</script>
