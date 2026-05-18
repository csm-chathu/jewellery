<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <ClipboardDocumentListIcon class="w-5 h-5 text-amber-500" />
          Stock Ledger
        </h2>
        <p class="text-sm text-gray-400 mt-0.5">Per-product movement history — purchases in, sales out, running balance</p>
      </div>
      <button v-if="ledger" @click="printLedger"
        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg font-medium text-sm shadow-sm">
        <PrinterIcon class="w-4 h-4" /> Print
      </button>
    </div>

    <!-- Filters -->
    <div class="card flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-56">
        <label class="text-xs text-gray-500 block mb-1">Product *</label>
        <select v-model="filters.product_id" class="form-input w-full" @change="load">
          <option :value="null">— Select a product —</option>
          <option v-for="p in products" :key="p.id" :value="p.id">
            {{ p.name }}
            <template v-if="p.sku"> · {{ p.sku }}</template>
            <template v-if="p.karat"> · {{ p.karat }}</template>
          </option>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-500 block mb-1">From</label>
        <input v-model="filters.date_from" type="date" class="form-input w-36" @change="load" />
      </div>
      <div>
        <label class="text-xs text-gray-500 block mb-1">To</label>
        <input v-model="filters.date_to" type="date" class="form-input w-36" @change="load" />
      </div>
      <button @click="filters.date_from=''; filters.date_to=''; load()" class="btn-secondary text-sm">Clear Dates</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card flex items-center justify-center py-16 text-gray-400 gap-2">
      <ArrowPathIcon class="w-5 h-5 animate-spin" /> Loading…
    </div>

    <!-- Prompt -->
    <div v-else-if="!ledger" class="card py-16 text-center text-gray-400">
      <ClipboardDocumentListIcon class="w-12 h-12 mx-auto mb-3 text-gray-200" />
      <p>Select a product to view its stock ledger</p>
    </div>

    <template v-else>

      <!-- Product info + summary cards -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Product info -->
        <div class="card col-span-2 flex gap-4 items-start">
          <img v-if="ledger.product.image"
            :src="ledger.product.image.startsWith('http') ? ledger.product.image : `/storage/${ledger.product.image}`"
            class="w-16 h-16 object-cover rounded-xl border shrink-0" />
          <div v-else class="w-16 h-16 rounded-xl border bg-gray-100 flex items-center justify-center shrink-0">
            <CubeIcon class="w-8 h-8 text-gray-300" />
          </div>
          <div>
            <p class="font-semibold text-gray-800 text-base">{{ ledger.product.name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">
              SKU: {{ ledger.product.sku }}
              <span v-if="ledger.product.karat"> · {{ ledger.product.karat }}</span>
              <span v-if="ledger.product.weight"> · {{ ledger.product.weight }}g</span>
            </p>
            <p class="text-xs text-gray-400">{{ ledger.product.category?.name }}</p>
            <span :class="ledger.current_stock <= ledger.product.min_stock_level ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
              class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded-full text-xs font-medium">
              {{ ledger.current_stock <= ledger.product.min_stock_level ? '⚠ Low Stock' : 'In Stock' }}
            </span>
          </div>
        </div>

        <div class="card text-center py-4">
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Opening Balance</p>
          <p class="text-2xl font-bold text-gray-700">{{ ledger.opening_balance }}</p>
          <p class="text-xs text-gray-400">units</p>
        </div>
        <div class="card text-center py-4 border-l-4 border-green-400">
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total In</p>
          <p class="text-2xl font-bold text-green-700">+{{ ledger.total_in }}</p>
          <p class="text-xs text-gray-400">purchased</p>
        </div>
        <div class="card text-center py-4 border-l-4 border-red-400">
          <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Out</p>
          <p class="text-2xl font-bold text-red-700">−{{ ledger.total_out }}</p>
          <p class="text-xs text-gray-400">sold</p>
        </div>
      </div>

      <!-- Balance summary row -->
      <div class="grid grid-cols-2 gap-4">
        <div class="card flex items-center justify-between px-5 py-4">
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wider">Closing Balance (period)</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ ledger.closing_balance }}</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Current Stock (actual)</p>
            <p class="text-3xl font-bold mt-1" :class="ledger.current_stock <= ledger.product.min_stock_level ? 'text-red-600' : 'text-amber-600'">
              {{ ledger.current_stock }}
            </p>
          </div>
        </div>
        <div class="card flex items-center justify-between px-5 py-4" v-if="ledger.current_stock !== ledger.closing_balance">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-semibold text-gray-700">Balance Discrepancy</p>
              <p class="text-xs text-gray-400 mt-0.5">
                Closing balance ({{ ledger.closing_balance }}) ≠ actual stock ({{ ledger.current_stock }}).
                May be due to date filters excluding some transactions, manual adjustments, or returns.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Ledger table -->
      <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-800 text-white">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Date</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Reference #</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Type</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Party</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-green-300">Stock In</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-red-300">Stock Out</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Balance</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Unit Value</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <!-- Opening balance row -->
            <tr class="bg-gray-50">
              <td class="px-4 py-2.5 text-xs text-gray-500">{{ filters.date_from || 'All time' }}</td>
              <td class="px-4 py-2.5 font-mono text-xs text-gray-400">—</td>
              <td class="px-4 py-2.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                  Opening Balance
                </span>
              </td>
              <td class="px-4 py-2.5 text-gray-400">—</td>
              <td class="px-4 py-2.5 text-right text-gray-400">—</td>
              <td class="px-4 py-2.5 text-right text-gray-400">—</td>
              <td class="px-4 py-2.5 text-right font-bold text-gray-700">{{ ledger.opening_balance }}</td>
              <td class="px-4 py-2.5 text-right text-gray-400">—</td>
            </tr>

            <tr v-if="!ledger.entries.length">
              <td colspan="8" class="px-4 py-10 text-center text-gray-400">No transactions in this period</td>
            </tr>

            <tr v-for="(entry, i) in ledger.entries" :key="i"
              class="hover:bg-gray-50"
              :class="{
                'bg-green-50/40': entry.type === 'purchase',
                'bg-red-50/30':   entry.type === 'sale',
              }">
              <td class="px-4 py-2.5 text-xs text-gray-600 whitespace-nowrap">{{ fmtDate(entry.date) }}</td>
              <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ entry.ref }}</td>
              <td class="px-4 py-2.5">
                <span :class="entry.type === 'purchase'
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                  {{ entry.type === 'purchase' ? '↑ Purchase' : '↓ Sale' }}
                </span>
              </td>
              <td class="px-4 py-2.5 text-sm text-gray-700">{{ entry.party }}</td>
              <td class="px-4 py-2.5 text-right font-semibold text-green-700">
                {{ entry.in > 0 ? '+' + entry.in : '—' }}
              </td>
              <td class="px-4 py-2.5 text-right font-semibold text-red-700">
                {{ entry.out > 0 ? '−' + entry.out : '—' }}
              </td>
              <td class="px-4 py-2.5 text-right font-bold" :class="entry.balance > 0 ? 'text-gray-800' : 'text-red-600'">
                {{ entry.balance }}
              </td>
              <td class="px-4 py-2.5 text-right text-xs text-gray-500">
                {{ entry.type === 'purchase' ? lkr(entry.unit_cost) : lkr(entry.unit_price) }}
              </td>
            </tr>

            <!-- Closing row -->
            <tr v-if="ledger.entries.length" class="bg-gray-100 border-t-2 border-gray-300">
              <td colspan="6" class="px-4 py-2.5 text-xs font-bold text-gray-600 uppercase">Closing Balance</td>
              <td class="px-4 py-2.5 text-right font-bold text-lg text-gray-800">{{ ledger.closing_balance }}</td>
              <td class="px-4 py-2.5"></td>
            </tr>
          </tbody>
        </table>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import {
  ClipboardDocumentListIcon, PrinterIcon, ArrowPathIcon,
  CubeIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const products = ref([])
const ledger   = ref(null)
const loading  = ref(false)

const filters = reactive({
  product_id: null,
  date_from: '',
  date_to: '',
})

function lkr(val) {
  if (!val && val !== 0) return '—'
  return 'LKR ' + Number(val).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(val) {
  if (!val) return '—'
  return new Date(val).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function load() {
  if (!filters.product_id) { ledger.value = null; return }
  loading.value = true
  try {
    const { data } = await axios.get('/api/reports/stock-ledger', { params: filters })
    ledger.value = data
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to load stock ledger')
    ledger.value = null
  } finally {
    loading.value = false
  }
}

function printLedger() {
  if (!ledger.value) return
  const p = ledger.value.product
  const today = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
  const period = filters.date_from || filters.date_to
    ? `${filters.date_from || 'Start'} → ${filters.date_to || 'Today'}`
    : 'All time'

  const typeLabel = (e) => e.type === 'purchase' ? '↑ Purchase' : '↓ Sale'
  const typeColor = (e) => e.type === 'purchase' ? '#15803d' : '#dc2626'
  const rowBg     = (e) => e.type === 'purchase' ? '#f0fdf4' : '#fef2f2'

  const openingRow = `
    <tr style="background:#f9fafb">
      <td>${filters.date_from || 'All time'}</td>
      <td>—</td>
      <td><span style="font-size:10px;color:#6b7280">Opening Balance</span></td>
      <td>—</td>
      <td style="text-align:right">—</td>
      <td style="text-align:right">—</td>
      <td style="text-align:right;font-weight:700">${ledger.value.opening_balance}</td>
      <td style="text-align:right">—</td>
    </tr>`

  const entryRows = ledger.value.entries.map(e => `
    <tr style="background:${rowBg(e)}">
      <td>${new Date(e.date).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</td>
      <td style="font-family:monospace;font-size:10px">${e.ref}</td>
      <td><span style="color:${typeColor(e)};font-weight:600">${typeLabel(e)}</span></td>
      <td>${e.party}</td>
      <td style="text-align:right;color:#15803d;font-weight:600">${e.in > 0 ? '+' + e.in : '—'}</td>
      <td style="text-align:right;color:#dc2626;font-weight:600">${e.out > 0 ? '−' + e.out : '—'}</td>
      <td style="text-align:right;font-weight:700;color:${e.balance > 0 ? '#111' : '#dc2626'}">${e.balance}</td>
      <td style="text-align:right;font-size:10px;color:#6b7280">${e.type === 'purchase' ? (e.unit_cost ? 'LKR ' + Number(e.unit_cost).toLocaleString('en-LK',{minimumFractionDigits:2}) : '—') : (e.unit_price ? 'LKR ' + Number(e.unit_price).toLocaleString('en-LK',{minimumFractionDigits:2}) : '—')}</td>
    </tr>`).join('')

  const win = window.open('', '_blank', 'width=1000,height=800')
  win.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Stock Ledger — ${p.name}</title>
  <style>
    @media print { @page { size: A4 landscape; margin: 10mm; } }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 16px 20px; }
    .hdr { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #1f2937; padding-bottom:12px; margin-bottom:16px; }
    .shop { font-size:18px; font-weight:700; }
    .meta { font-size:10px; color:#6b7280; margin-top:2px; }
    .title { font-size:15px; font-weight:700; text-align:right; }
    .ref { font-size:10px; color:#6b7280; text-align:right; margin-top:3px; }
    .product-info { background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:8px 12px; margin-bottom:14px; font-size:11px; }
    .summary { display:flex; gap:12px; margin-bottom:14px; }
    .sum-box { flex:1; background:#f3f4f6; border-radius:6px; padding:8px 12px; text-align:center; }
    .sum-label { font-size:9px; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
    .sum-val { font-size:20px; font-weight:700; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    th { background:#1f2937; color:#fff; padding:6px 8px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.04em; }
    td { padding:5px 8px; border-bottom:1px solid #e5e7eb; }
    tfoot td { background:#f3f4f6; font-weight:700; border-top:2px solid #6b7280; font-size:12px; }
    .foot { margin-top:16px; font-size:9px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:6px; }
  </style></head><body>
  <div class="hdr">
    <div>
      <div class="shop">Stock Ledger</div>
      <div class="meta">Period: ${period} · Printed: ${today}</div>
    </div>
    <div>
      <div class="title">${p.name}</div>
      <div class="ref">SKU: ${p.sku}${p.karat ? ' · ' + p.karat : ''}${p.weight ? ' · ' + p.weight + 'g' : ''}</div>
      <div class="ref">${p.category?.name ?? ''}</div>
    </div>
  </div>
  <div class="summary">
    <div class="sum-box"><div class="sum-label">Opening</div><div class="sum-val" style="color:#374151">${ledger.value.opening_balance}</div></div>
    <div class="sum-box"><div class="sum-label">Total In</div><div class="sum-val" style="color:#15803d">+${ledger.value.total_in}</div></div>
    <div class="sum-box"><div class="sum-label">Total Out</div><div class="sum-val" style="color:#dc2626">−${ledger.value.total_out}</div></div>
    <div class="sum-box"><div class="sum-label">Closing Balance</div><div class="sum-val" style="color:#1d4ed8">${ledger.value.closing_balance}</div></div>
    <div class="sum-box"><div class="sum-label">Actual Stock</div><div class="sum-val" style="color:${ledger.value.current_stock <= p.min_stock_level ? '#dc2626' : '#92400e'}">${ledger.value.current_stock}</div></div>
  </div>
  <table>
    <thead><tr>
      <th>Date</th><th>Reference #</th><th>Type</th><th>Party</th>
      <th style="text-align:right;color:#86efac">Stock In</th>
      <th style="text-align:right;color:#fca5a5">Stock Out</th>
      <th style="text-align:right">Balance</th>
      <th style="text-align:right">Unit Value</th>
    </tr></thead>
    <tbody>${openingRow}${entryRows}</tbody>
    <tfoot><tr>
      <td colspan="4">TOTALS</td>
      <td style="text-align:right;color:#15803d">+${ledger.value.total_in}</td>
      <td style="text-align:right;color:#dc2626">−${ledger.value.total_out}</td>
      <td style="text-align:right;color:#1d4ed8">${ledger.value.closing_balance}</td>
      <td></td>
    </tr></tfoot>
  </table>
  <div class="foot">Stock Ledger — ${p.name} · Generated ${today}</div>
  </body></html>`)
  win.document.close()
  win.addEventListener('load', () => { win.focus(); win.print() })
}

onMounted(async () => {
  const { data } = await axios.get('/api/products', { params: { per_page: 500 } })
  products.value = data.data ?? data
})
</script>
