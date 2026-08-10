<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Daily Stock Value Report</h1>
        <p class="text-sm text-gray-500 mt-1">Current stock value by category as of today</p>
      </div>
      <button @click="load" class="btn-secondary flex items-center gap-2 text-sm">
        <ArrowPathIcon class="w-4 h-4" /> Refresh
      </button>
    </div>

    <!-- Summary cards -->
    <div v-if="totals" class="grid grid-cols-3 gap-4">
      <div class="card p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Categories</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ categories.length }}</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Items (SKUs)</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ totals.item_count }}</p>
      </div>
      <div class="card p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Pieces</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ totals.piece_count }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <div v-if="loading" class="text-center py-12 text-gray-400">Loading…</div>
      <table v-else class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-900 text-white text-xs uppercase">
            <th class="px-4 py-3 text-left w-8"></th>
            <th class="px-4 py-3 text-left">Category</th>
            <th class="px-4 py-3 text-right">Items (SKUs)</th>
            <th class="px-4 py-3 text-right">Pieces in Stock</th>
            <th class="px-4 py-3 text-right">Total Weight (g)</th>
            <th class="px-4 py-3 text-right">Gold Value (LKR)</th>
            <th class="px-4 py-3 text-right">Cost Value (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="cat in categories" :key="cat.category_id">
            <!-- Category row -->
            <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                @click="toggle(cat.category_id)">
              <td class="px-4 py-3 text-gray-400">
                <ChevronDownIcon v-if="expanded.has(cat.category_id)" class="w-4 h-4" />
                <ChevronRightIcon v-else class="w-4 h-4" />
              </td>
              <td class="px-4 py-3 font-semibold text-gray-800">{{ cat.category_name }}</td>
              <td class="px-4 py-3 text-right font-medium">{{ cat.item_count }}</td>
              <td class="px-4 py-3 text-right font-bold text-amber-700">{{ cat.piece_count }}</td>
              <td class="px-4 py-3 text-right text-gray-600">{{ cat.total_weight ? cat.total_weight + ' g' : '—' }}</td>
              <td class="px-4 py-3 text-right text-emerald-700">{{ cat.gold_value != null ? lkr(cat.gold_value) : '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ lkr(cat.cost_value) }}</td>
            </tr>
            <!-- Expanded product rows -->
            <template v-if="expanded.has(cat.category_id)">
              <tr v-for="p in cat.products" :key="p.id"
                  class="border-b border-gray-50 bg-gray-50 text-xs text-gray-600">
                <td class="px-4 py-2"></td>
                <td class="px-4 py-2 pl-10">
                  <span class="font-medium text-gray-700">{{ p.name }}</span>
                  <span class="ml-2 text-gray-400">{{ p.sku }}</span>
                  <span v-if="p.karat" class="ml-1 bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full">{{ p.karat }}</span>
                </td>
                <td class="px-4 py-2 text-right">1</td>
                <td class="px-4 py-2 text-right font-semibold text-gray-800">{{ p.qty }}</td>
                <td class="px-4 py-2 text-right">{{ p.weight_g ? p.weight_g + ' g' : '—' }}</td>
                <td class="px-4 py-2 text-right text-emerald-700">{{ p.gold_value != null ? lkr(p.gold_value) : '—' }}</td>
                <td class="px-4 py-2 text-right text-gray-700">{{ lkr(p.cost_value) }}</td>
              </tr>
            </template>
          </template>

          <!-- Totals row -->
          <tr v-if="totals" class="bg-gray-900 text-white text-sm font-bold">
            <td class="px-4 py-3"></td>
            <td class="px-4 py-3">TOTAL</td>
            <td class="px-4 py-3 text-right">{{ totals.item_count }}</td>
            <td class="px-4 py-3 text-right text-amber-300">{{ totals.piece_count }}</td>
            <td class="px-4 py-3 text-right">{{ totals.total_weight ? totals.total_weight + ' g' : '—' }}</td>
            <td class="px-4 py-3 text-right text-emerald-300">{{ totals.gold_value ? lkr(totals.gold_value) : '—' }}</td>
            <td class="px-4 py-3 text-right">{{ lkr(totals.cost_value) }}</td>
          </tr>

          <tr v-if="!loading && !categories.length">
            <td colspan="7" class="text-center py-10 text-gray-400">No stock found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ArrowPathIcon, ChevronDownIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const categories = ref([])
const totals     = ref(null)
const loading    = ref(false)
const expanded   = ref(new Set())

const lkr = (v) => 'Rs. ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

function toggle(id) {
  if (expanded.value.has(id)) expanded.value.delete(id)
  else expanded.value.add(id)
  expanded.value = new Set(expanded.value)
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/reports/category-stock')
    categories.value = data.categories
    totals.value     = data.totals
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
