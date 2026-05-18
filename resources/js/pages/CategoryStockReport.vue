<template>
  <div class="max-w-6xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Category Stock Value</h1>
        <p v-if="data" class="text-sm text-gray-500 mt-0.5">
          As of {{ fmtDate(data.date) }} &nbsp;·&nbsp;
          <span v-if="data.gold_rates?.length">
            Rates: {{ data.gold_rates.map(r => `${r.label} = Rs. ${fmtN(r.rate_per_gram)}/g`).join(' | ') }}
          </span>
          <span v-else class="text-amber-600 font-medium">No gold rate set for today</span>
        </p>
      </div>
      <div class="flex gap-2">
        <button v-if="data" @click="printReport" class="btn-secondary flex items-center gap-1.5">
          <PrinterIcon class="w-4 h-4" /> Print
        </button>
        <button @click="load" :disabled="loading" class="btn-primary flex items-center gap-1.5">
          <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          {{ loading ? 'Loading…' : 'Refresh' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card flex items-center justify-center py-20 gap-3 text-gray-500">
      <ArrowPathIcon class="w-6 h-6 animate-spin" />
      <span>Loading report…</span>
    </div>

    <!-- No rate warning -->
    <div v-else-if="data && !data.gold_rates?.length"
      class="card border-l-4 border-amber-400 bg-amber-50 text-amber-800 p-4 text-sm">
      Today's gold rate has not been set. Values shown are based on the latest available rate.
    </div>

    <template v-if="data && !loading">
      <!-- Summary cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="card text-center py-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Categories</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ data.categories.length }}</p>
        </div>
        <div class="card text-center py-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Pieces</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmtN(data.totals.piece_count) }}</p>
        </div>
        <div class="card text-center py-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Weight</p>
          <p class="text-2xl font-bold text-yellow-700 mt-1">{{ fmtN(data.totals.total_weight) }} g</p>
        </div>
        <div class="card text-center py-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Gold Value (Today)</p>
          <p class="text-2xl font-bold text-green-700 mt-1">
            {{ data.totals.gold_value != null ? 'Rs. ' + fmtN(data.totals.gold_value) : '—' }}
          </p>
        </div>
      </div>

      <!-- Category table -->
      <div class="card overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-800 text-white">
              <th class="table-th text-left">Category</th>
              <th class="table-th text-right">Items</th>
              <th class="table-th text-right">Pieces</th>
              <th class="table-th text-right">Total Weight (g)</th>
              <th class="table-th text-right">Gold Value (LKR)</th>
              <th class="table-th text-right">Cost Value (LKR)</th>
              <th class="table-th text-center w-10"></th>
            </tr>
          </thead>
          <tbody>
            <template v-for="cat in data.categories" :key="cat.category_id">
              <!-- Category row -->
              <tr class="hover:bg-gray-50 cursor-pointer font-medium border-b border-gray-100"
                @click="toggleCategory(cat.category_id)">
                <td class="table-td">
                  <div class="flex items-center gap-2">
                    <ChevronRightIcon class="w-4 h-4 text-gray-400 transition-transform"
                      :class="{ 'rotate-90': expanded.has(cat.category_id) }" />
                    {{ cat.category_name }}
                  </div>
                </td>
                <td class="table-td text-right">{{ cat.item_count }}</td>
                <td class="table-td text-right">{{ cat.piece_count }}</td>
                <td class="table-td text-right font-semibold text-yellow-700">{{ fmtN(cat.total_weight) }}</td>
                <td class="table-td text-right font-semibold text-green-700">
                  {{ cat.gold_value != null ? 'Rs. ' + fmtN(cat.gold_value) : '—' }}
                </td>
                <td class="table-td text-right text-gray-600">Rs. {{ fmtN(cat.cost_value) }}</td>
                <td class="table-td text-center text-gray-400 text-xs">
                  {{ expanded.has(cat.category_id) ? '▲' : '▼' }}
                </td>
              </tr>

              <!-- Product sub-rows -->
              <template v-if="expanded.has(cat.category_id)">
                <tr v-for="p in cat.products" :key="p.id"
                  class="bg-blue-50/40 border-b border-blue-100 text-xs">
                  <td class="table-td pl-10">
                    <span class="font-medium text-gray-800">{{ p.name }}</span>
                    <span v-if="p.sku" class="ml-2 text-gray-400">{{ p.sku }}</span>
                    <span v-if="p.karat" class="ml-2 badge badge-yellow text-[10px]">{{ p.karat }}</span>
                  </td>
                  <td class="table-td text-right text-gray-500">1</td>
                  <td class="table-td text-right">{{ p.qty }}</td>
                  <td class="table-td text-right text-yellow-700">{{ fmtN(p.total_weight) }}</td>
                  <td class="table-td text-right text-green-700">
                    {{ p.gold_value != null ? 'Rs. ' + fmtN(p.gold_value) : '—' }}
                  </td>
                  <td class="table-td text-right text-gray-500">Rs. {{ fmtN(p.cost_value) }}</td>
                  <td class="table-td"></td>
                </tr>
              </template>
            </template>

            <!-- Totals row -->
            <tr class="bg-gray-800 text-white font-bold text-sm">
              <td class="table-td">TOTAL</td>
              <td class="table-td text-right">{{ data.totals.item_count }}</td>
              <td class="table-td text-right">{{ data.totals.piece_count }}</td>
              <td class="table-td text-right">{{ fmtN(data.totals.total_weight) }} g</td>
              <td class="table-td text-right">
                {{ data.totals.gold_value != null ? 'Rs. ' + fmtN(data.totals.gold_value) : '—' }}
              </td>
              <td class="table-td text-right">Rs. {{ fmtN(data.totals.cost_value) }}</td>
              <td class="table-td"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ArrowPathIcon, PrinterIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const data    = ref(null)
const loading = ref(false)
const expanded = ref(new Set())

async function load() {
  loading.value = true
  try {
    const { data: res } = await axios.get('/api/reports/category-stock')
    data.value = res
  } finally {
    loading.value = false
  }
}

function toggleCategory(id) {
  if (expanded.value.has(id)) {
    expanded.value.delete(id)
  } else {
    expanded.value.add(id)
  }
  expanded.value = new Set(expanded.value)
}

function fmtN(v) {
  if (v == null) return '—'
  return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('en-LK', { year: 'numeric', month: 'long', day: 'numeric' })
}

function printReport() {
  if (!data.value) return
  const d = data.value
  const ratesLine = d.gold_rates?.length
    ? d.gold_rates.map(r => `${r.label}: Rs. ${fmtN(r.rate_per_gram)}/g`).join(' &nbsp;|&nbsp; ')
    : 'No gold rate for today'

  const catRows = d.categories.map(cat => {
    const productRows = cat.products.map(p => `
      <tr class="sub">
        <td style="padding-left:32px">${p.name}${p.sku ? ` <span style="color:#9ca3af;font-size:10px">(${p.sku})</span>` : ''}${p.karat ? ` <span style="background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:3px;font-size:9px">${p.karat}</span>` : ''}</td>
        <td class="r">1</td>
        <td class="r">${p.qty}</td>
        <td class="r yw">${fmtN(p.total_weight)}</td>
        <td class="r gn">${p.gold_value != null ? 'Rs. ' + fmtN(p.gold_value) : '—'}</td>
        <td class="r gr">${'Rs. ' + fmtN(p.cost_value)}</td>
      </tr>`).join('')
    return `
      <tr class="cat-row">
        <td><strong>${cat.category_name}</strong></td>
        <td class="r">${cat.item_count}</td>
        <td class="r">${cat.piece_count}</td>
        <td class="r yw"><strong>${fmtN(cat.total_weight)}</strong></td>
        <td class="r gn"><strong>${cat.gold_value != null ? 'Rs. ' + fmtN(cat.gold_value) : '—'}</strong></td>
        <td class="r gr">${'Rs. ' + fmtN(cat.cost_value)}</td>
      </tr>
      ${productRows}`
  }).join('')

  const html = `<!DOCTYPE html><html><head><meta charset="utf-8">
  <title>Category Stock Value Report</title>
  <style>
    @media print { @page { size: A4 landscape; margin: 12mm 15mm; } }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 14px 18px; }
    .hdr { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #111; padding-bottom:8px; margin-bottom:12px; }
    .shop-name { font-size:16px; font-weight:900; text-transform:uppercase; letter-spacing:0.5px; }
    .report-title { font-size:18px; font-weight:900; letter-spacing:1px; text-align:right; }
    .report-sub { font-size:10px; color:#666; text-align:right; margin-top:3px; }
    .rates-bar { background:#f3f4f6; border:1px solid #e5e7eb; padding:5px 10px; border-radius:4px; margin-bottom:12px; font-size:10px; color:#374151; }
    .summary { display:flex; gap:12px; margin-bottom:14px; }
    .sum-box { flex:1; border:1px solid #e5e7eb; border-radius:6px; padding:8px 12px; text-align:center; }
    .sum-label { font-size:9px; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px; }
    .sum-val { font-size:16px; font-weight:800; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    thead tr { background:#1a1a1a; color:#fff; }
    th { padding:6px 8px; font-size:10px; font-weight:700; letter-spacing:0.3px; }
    .r { text-align:right; }
    .cat-row td { padding:6px 8px; background:#f9fafb; border-bottom:2px solid #e5e7eb; font-size:11px; }
    .sub td { padding:4px 8px; border-bottom:1px solid #f3f4f6; font-size:10px; color:#374151; background:#fff; }
    .yw { color:#92400e; font-weight:600; }
    .gn { color:#166534; font-weight:600; }
    .gr { color:#374151; }
    .tot td { background:#1a1a1a; color:#fff; padding:7px 8px; font-weight:700; font-size:11px; }
  </style>
  </head><body>
  <div class="hdr">
    <div>
      <div class="shop-name">${window._shopName || 'Jewellery Shop'}</div>
    </div>
    <div>
      <div class="report-title">Category Stock Value Report</div>
      <div class="report-sub">Date: ${fmtDate(d.date)}</div>
    </div>
  </div>
  <div class="rates-bar">Today's Gold Rates &nbsp;— &nbsp;${ratesLine}</div>
  <div class="summary">
    <div class="sum-box"><div class="sum-label">Categories</div><div class="sum-val">${d.categories.length}</div></div>
    <div class="sum-box"><div class="sum-label">Total Pieces</div><div class="sum-val">${fmtN(d.totals.piece_count)}</div></div>
    <div class="sum-box"><div class="sum-label">Total Weight</div><div class="sum-val" style="color:#92400e">${fmtN(d.totals.total_weight)} g</div></div>
    <div class="sum-box"><div class="sum-label">Gold Value (Today)</div><div class="sum-val" style="color:#166534">${d.totals.gold_value != null ? 'Rs. ' + fmtN(d.totals.gold_value) : '—'}</div></div>
    <div class="sum-box"><div class="sum-label">Cost Value</div><div class="sum-val">Rs. ${fmtN(d.totals.cost_value)}</div></div>
  </div>
  <table>
    <thead>
      <tr>
        <th style="text-align:left">Category / Product</th>
        <th class="r">Items</th>
        <th class="r">Pieces</th>
        <th class="r">Total Weight (g)</th>
        <th class="r">Gold Value (LKR)</th>
        <th class="r">Cost Value (LKR)</th>
      </tr>
    </thead>
    <tbody>
      ${catRows}
      <tr class="tot">
        <td>TOTAL</td>
        <td class="r">${d.totals.item_count}</td>
        <td class="r">${d.totals.piece_count}</td>
        <td class="r">${fmtN(d.totals.total_weight)} g</td>
        <td class="r">${d.totals.gold_value != null ? 'Rs. ' + fmtN(d.totals.gold_value) : '—'}</td>
        <td class="r">Rs. ${fmtN(d.totals.cost_value)}</td>
      </tr>
    </tbody>
  </table>
  <script>window.onload=()=>{ window.print(); window.close(); }<\/script>
  </body></html>`

  const win = window.open('', '_blank', 'width=1100,height=750')
  win.document.write(html)
  win.document.close()
}

onMounted(async () => {
  // grab shop name for print header
  try {
    const { data: b } = await axios.get('/api/shop-branding')
    window._shopName = b?.shop_name ?? ''
  } catch {}
  load()
})
</script>
