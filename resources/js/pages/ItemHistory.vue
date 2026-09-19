<template>
  <div class="space-y-6">

    <!-- Header -->
    <div>
      <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
        <ClipboardDocumentListIcon class="w-5 h-5 text-amber-500" />
        Item History
      </h2>
      <p class="text-sm text-gray-400 mt-0.5">Full purchase, sale and return timeline for any item</p>
    </div>

    <!-- Search -->
    <div class="card">
      <div class="relative" ref="pickerEl">
        <label class="text-xs text-gray-500 block mb-1">Search by name, SKU or barcode</label>
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
          <input
            v-model="search"
            type="text"
            class="form-input pl-9 w-full"
            placeholder="e.g. Earring, 000649, barcode…"
            @input="onSearch"
            @focus="showDrop = true"
            autocomplete="off"
          />
        </div>

        <!-- Dropdown -->
        <div v-if="showDrop && dropProducts.length"
          class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
          <button
            v-for="p in dropProducts" :key="p.id"
            type="button"
            class="w-full text-left px-3 py-2.5 text-sm hover:bg-amber-50 flex items-center justify-between gap-2 border-b last:border-0"
            @mousedown.prevent="select(p)"
          >
            <div>
              <p class="font-medium text-gray-800">{{ p.name }}</p>
              <p class="text-xs text-gray-400">{{ p.category?.name }}</p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-xs text-gray-500">{{ p.sku }}</p>
              <p v-if="p.karat" class="text-xs text-amber-600">{{ p.karat }}</p>
            </div>
          </button>
        </div>
        <p v-else-if="showDrop && search.length > 1 && !searching && !dropProducts.length"
          class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow text-sm text-gray-400 px-3 py-2">
          No products found
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card py-16 flex items-center justify-center gap-2 text-gray-400">
      <ArrowPathIcon class="w-5 h-5 animate-spin" /> Loading…
    </div>

    <!-- Prompt -->
    <div v-else-if="!product" class="card py-16 text-center text-gray-400">
      <ClipboardDocumentListIcon class="w-12 h-12 mx-auto mb-3 text-gray-200" />
      <p>Search and select a product to view its full history</p>
    </div>

    <template v-else>

      <!-- Product info -->
      <div class="card flex gap-4 items-start">
        <div class="w-14 h-14 rounded-xl border bg-gray-100 flex items-center justify-center shrink-0">
          <CubeIcon class="w-7 h-7 text-gray-300" />
        </div>
        <div class="flex-1">
          <p class="font-semibold text-gray-800 text-base">{{ product.name }}</p>
          <p class="text-xs text-gray-400 mt-0.5">
            SKU: {{ product.sku }}
            <span v-if="product.barcode"> · Barcode: {{ product.barcode }}</span>
            <span v-if="product.karat"> · {{ product.karat }}</span>
            <span v-if="product.weight"> · {{ product.weight }}g</span>
          </p>
          <p class="text-xs text-gray-400">{{ product.category?.name }}</p>
        </div>
        <!-- Summary chips -->
        <div class="flex gap-2 flex-wrap justify-end">
          <div class="text-center px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-100">
            <p class="text-xs text-blue-500">Purchased</p>
            <p class="font-bold text-blue-700 text-sm">{{ summary.total_purchased }} pcs</p>
            <p class="text-xs text-blue-400">{{ lkr(summary.total_purchase_value) }}</p>
          </div>
          <div class="text-center px-3 py-1.5 rounded-lg bg-green-50 border border-green-100">
            <p class="text-xs text-green-500">Sold</p>
            <p class="font-bold text-green-700 text-sm">{{ summary.total_sold }} pcs</p>
            <p class="text-xs text-green-400">{{ lkr(summary.total_sale_value) }}</p>
          </div>
          <div class="text-center px-3 py-1.5 rounded-lg bg-orange-50 border border-orange-100">
            <p class="text-xs text-orange-500">Returned</p>
            <p class="font-bold text-orange-700 text-sm">{{ summary.total_returned }} pcs</p>
          </div>
          <div v-if="summary.total_written_off" class="text-center px-3 py-1.5 rounded-lg bg-red-50 border border-red-100">
            <p class="text-xs text-red-500">Written Off</p>
            <p class="font-bold text-red-700 text-sm">{{ summary.total_written_off }} pcs</p>
          </div>
          <div class="text-center px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200">
            <p class="text-xs text-amber-600">Current Stock</p>
            <p class="font-bold text-amber-700 text-sm">{{ summary.current_stock }} pcs</p>
          </div>
        </div>
      </div>

      <!-- Timeline table -->
      <div class="card overflow-x-auto p-0">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th">Date</th>
              <th class="table-th">Reference</th>
              <th class="table-th">Type</th>
              <th class="table-th">Party / Detail</th>
              <th class="table-th text-right">Qty In</th>
              <th class="table-th text-right">Qty Out</th>
              <th class="table-th text-right">Balance</th>
              <th class="table-th text-right">Amount</th>
              <th class="table-th">Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!entries.length">
              <td colspan="9" class="table-td text-center text-gray-400 py-8">No transactions found</td>
            </tr>
            <tr v-for="(e, i) in entries" :key="i"
              :class="[
                'border-b last:border-0',
                e.type === 'created'   ? 'bg-gray-50' :
                e.type === 'purchase'  ? 'hover:bg-blue-50' :
                e.type === 'return'    ? 'hover:bg-orange-50' :
                e.type === 'layaway'   ? 'hover:bg-purple-50' :
                e.type === 'write_off' ? 'hover:bg-red-50' : 'hover:bg-green-50'
              ]">
              <td class="table-td text-xs text-gray-500 whitespace-nowrap">{{ fmtDate(e.date) }}</td>
              <td class="table-td font-mono text-xs text-gray-700">{{ e.ref }}</td>
              <td class="table-td">
                <span :class="typeBadge(e.type)" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                  {{ e.type.replace('_', ' ') }}
                </span>
              </td>
              <td class="table-td">
                <p class="text-gray-800">{{ e.party }}</p>
                <p v-if="e.party_phone" class="text-xs text-gray-400">{{ e.party_phone }}</p>
              </td>
              <td class="table-td text-right">
                <span v-if="e.qty_in" class="font-semibold text-blue-600">+{{ e.qty_in }}</span>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="table-td text-right">
                <span v-if="e.qty_out" class="font-semibold text-red-500">-{{ e.qty_out }}</span>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="table-td text-right">
                <span :class="e.balance > 0 ? 'text-gray-700' : 'text-red-600'" class="font-semibold">
                  {{ e.balance }}
                </span>
              </td>
              <td class="table-td text-right">
                <p v-if="e.total" :class="e.type === 'sale' ? 'font-semibold text-green-700' : 'text-gray-700'">
                  {{ lkr(e.total) }}
                </p>
                <p v-if="e.official && e.official !== e.total" class="text-xs text-gray-400">
                  Official: {{ lkr(e.official) }}
                </p>
                <span v-if="!e.total" class="text-gray-300">—</span>
              </td>
              <td class="table-td text-xs text-gray-500">{{ e.notes ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </template>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import {
  ClipboardDocumentListIcon,
  MagnifyingGlassIcon,
  ArrowPathIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'

const search      = ref('')
const searching   = ref(false)
const showDrop    = ref(false)
const dropProducts = ref([])
const pickerEl    = ref(null)

const loading  = ref(false)
const product  = ref(null)
const entries  = ref([])
const summary  = ref({})

let searchTimer = null

function lkr(v) {
  return 'LKR ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(d) {
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function typeBadge(type) {
  if (type === 'purchase')  return 'bg-blue-100 text-blue-700'
  if (type === 'return')    return 'bg-orange-100 text-orange-700'
  if (type === 'layaway')   return 'bg-purple-100 text-purple-700'
  if (type === 'write_off') return 'bg-red-100 text-red-700'
  if (type === 'created')   return 'bg-gray-100 text-gray-600'
  return 'bg-green-100 text-green-700'
}

function onSearch() {
  showDrop.value = true
  clearTimeout(searchTimer)
  if (search.value.trim().length < 2) { dropProducts.value = []; return }
  searchTimer = setTimeout(async () => {
    searching.value = true
    try {
      const { data } = await axios.get('/api/reports/item-history', { params: { search: search.value } })
      dropProducts.value = data.products ?? []
    } finally {
      searching.value = false
    }
  }, 300)
}

async function select(p) {
  showDrop.value = false
  search.value = `${p.name}${p.sku ? ' · ' + p.sku : ''}`
  loading.value = true
  product.value = null
  entries.value = []
  try {
    const { data } = await axios.get('/api/reports/item-history', {
      params: { search: search.value, product_id: p.id },
    })
    product.value  = data.product
    entries.value  = data.entries ?? []
    summary.value  = data.summary ?? {}
  } finally {
    loading.value = false
  }
}

function onClickOutside(e) {
  if (pickerEl.value && !pickerEl.value.contains(e.target)) showDrop.value = false
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>
