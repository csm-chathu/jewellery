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
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Product
      </button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="table-th">SKU</th>
              <th class="table-th">Barcode</th>
              <th class="table-th">Name</th>
              <th class="table-th">Category</th>
              <th class="table-th">Material / Karat</th>
              <th class="table-th">Stock</th>
              <th class="table-th">Buy Price</th>
              <th class="table-th">Sell Price</th>
              <th class="table-th">Status</th>
              <th class="table-th">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50">
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
              <td class="table-td">
                <span :class="p.stock_quantity <= p.min_stock_level ? 'badge bg-red-100 text-red-700' : 'badge bg-green-100 text-green-700'">
                  {{ p.stock_quantity }}
                </span>
              </td>
              <td class="table-td">LKR {{ Number(p.purchase_price).toLocaleString() }}</td>
              <td class="table-td font-semibold text-gold-700">LKR {{ Number(p.selling_price).toLocaleString() }}</td>
              <td class="table-td">
                <span :class="p.is_active ? 'badge bg-green-100 text-green-700' : 'badge bg-gray-100 text-gray-500'">
                  {{ p.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="table-td">
                <div class="flex items-center gap-2">
                  <button @click="reprintBarcode(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200">
                    <PrinterIcon class="w-3.5 h-3.5" /> Print Barcode
                  </button>
                  <button @click="openEdit(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                    <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                  </button>
                  <button @click="deleteProduct(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!products.data?.length">
              <td colspan="10" class="table-td text-center text-gray-400 py-8">No products found</td>
            </tr>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PencilSquareIcon, PlusIcon, PrinterIcon, TrashIcon } from '@heroicons/vue/24/outline'
import JsBarcode from 'jsbarcode'
import ProductModal from '@/components/ProductModal.vue'

const products      = ref({ data: [] })
const categories    = ref([])
const suppliers     = ref([])
const search        = ref('')
const categoryFilter = ref('')
const lowStockOnly  = ref(false)
const page          = ref(1)
const showModal     = ref(false)
const editing       = ref(null)

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { page.value = 1; fetchProducts() }, 400)
}

async function fetchProducts() {
  const params = { page: page.value, search: search.value, category_id: categoryFilter.value }
  if (lowStockOnly.value) params.low_stock = 1
  const { data } = await axios.get('/api/products', { params })
  products.value = data
}

async function fetchRefs() {
  const [c, s] = await Promise.all([axios.get('/api/categories/all'), axios.get('/api/suppliers/all')])
  categories.value = c.data
  suppliers.value  = s.data
}

function openCreate() { editing.value = null; showModal.value = true }
function openEdit(p)   { editing.value = p;    showModal.value = true }

function createBarcodeSvg(value) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
  JsBarcode(svg, value, {
    format: 'CODE128',
    width: 1.8,
    height: 56,
    margin: 0,
    displayValue: false,
  })
  return svg.outerHTML
}

function printProductBarcode(product) {
  if (!product?.sku) return
  // Use custom barcode if set, otherwise fall back to SKU
  const barcodeValue = product.barcode?.trim() || product.sku
  const popup = window.open('', '_blank', 'width=150,height=120')
  if (!popup) {
    alert('Popup blocked. Allow popups to print barcode labels.')
    return
  }

  const barcodeSvg = createBarcodeSvg(barcodeValue)
  const safeName  = (product.name ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeSku   = product.sku.replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safeBarcode = barcodeValue.replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const safePrice = Number(product.selling_price ?? 0).toLocaleString('en-LK', { minimumFractionDigits: 2 })
  const safeMeta  = [
    product.karat ? product.karat.toUpperCase() : '',
    product.weight ? `${product.weight}g` : '',
  ].filter(Boolean).join(' · ')

  popup.document.write(`<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Label - ${safeSku}</title>
  <style>
    @page { size: 30mm 20mm; margin: 0; }
    * { box-sizing: border-box; }
    body {
      margin: 0; padding: 0;
      font-family: 'Courier New', monospace;
      background: #fff;
      width: 30mm;
    }
    .label {
      width: 30mm;
      height: 20mm;
      padding: 0.5mm;
      text-align: center;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
    }
    .shop  { display: none; }
    .name  { font-size: 3pt; font-weight: bold; margin: 0.2mm 0; word-break: break-word; line-height: 1; }
    .meta  { display: none; }
    svg    { width: 100%; height: 15mm; display: block; flex-grow: 1; }
    .sku   { font-size: 3pt; letter-spacing: 0.5px; margin: 0.2mm 0; line-height: 1; }
    .price { display: none; }
    hr     { display: none; }
  </style>
</head>
<body>
  <div class="label">
    <div class="name">${safeName}</div>
    ${barcodeSvg}
    <div class="sku">${safeBarcode}</div>
  </div>
  <script>window.onload = function(){ window.print(); }<\/script>
</body>
</html>`)
  popup.document.close()
}

function reprintBarcode(product) {
  printProductBarcode(product)
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
    printProductBarcode(payload.product)
  }
}

onMounted(() => { fetchProducts(); fetchRefs() })
</script>
