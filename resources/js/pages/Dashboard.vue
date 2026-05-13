<template>
  <div class="space-y-6">
    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard v-for="s in stats" :key="s.label" v-bind="s" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Revenue chart -->
      <div class="lg:col-span-2 card">
        <h3 class="font-semibold text-gray-700 mb-4">Revenue — Last 30 Days</h3>
        <Line v-if="chartData" :data="chartData" :options="chartOptions" class="max-h-64" />
        <div v-else-if="!loaded" class="h-64 flex items-center justify-center text-gray-400">
          <div class="flex items-center gap-2"><span class="animate-spin inline-block w-4 h-4 border-2 border-gray-300 border-t-amber-500 rounded-full"></span> Loading…</div>
        </div>
        <div v-else class="h-64 flex flex-col items-center justify-center text-gray-400 gap-2">
          <svg class="w-10 h-10 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 17l6-6 4 4 8-8" /></svg>
          <span class="text-sm">No sales in the last 30 days</span>
        </div>
      </div>

      <!-- Top products -->
      <div class="card">
        <h3 class="font-semibold text-gray-700 mb-4">Top Products (This Month)</h3>
        <ul class="space-y-3">
          <li v-for="(p, i) in data.top_products" :key="p.id" class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-gold-100 text-gold-700 flex items-center justify-center text-xs font-bold">{{ i+1 }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ p.name }}</p>
              <p class="text-xs text-gray-400">{{ p.total_sold }} sold</p>
            </div>
          </li>
          <li v-if="!data.top_products?.length" class="text-sm text-gray-400">No sales this month</li>
        </ul>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Low stock -->
      <div class="card">
        <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-red-500"></span>
          Low Stock Alerts
        </h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead><tr>
              <th class="table-th">SKU</th>
              <th class="table-th">Name</th>
              <th class="table-th">Stock</th>
              <th class="table-th">Min</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="p in data.low_stock" :key="p.id">
                <td class="table-td font-mono text-xs">{{ p.sku }}</td>
                <td class="table-td">{{ p.name }}</td>
                <td class="table-td"><span class="badge bg-red-100 text-red-700">{{ p.stock_quantity }}</span></td>
                <td class="table-td text-gray-400">{{ p.min_stock_level }}</td>
              </tr>
              <tr v-if="!data.low_stock?.length">
                <td colspan="4" class="table-td text-center text-gray-400">All items are well-stocked ✓</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent sales -->
      <div class="card">
        <h3 class="font-semibold text-gray-700 mb-4">Recent Sales</h3>
        <div class="space-y-3">
          <div v-for="sale in data.recent_sales" :key="sale.id"
            class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ sale.invoice_number }}</p>
              <p class="text-xs text-gray-400">{{ sale.customer?.name ?? 'Walk-in' }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-gray-800">LKR {{ Number(sale.total).toLocaleString() }}</p>
              <span :class="statusClass(sale.payment_status)" class="badge">{{ sale.payment_status }}</span>
            </div>
          </div>
          <p v-if="!data.recent_sales?.length" class="text-sm text-gray-400 text-center">No sales yet</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  PointElement, LineElement, Title, Tooltip, Legend, Filler,
} from 'chart.js'
import StatCard from '@/components/StatCard.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const data = ref({})
const loaded = ref(false)

const stats = computed(() => [
  { label: 'Total Products',    value: data.value.totals?.products        ?? '—', color: 'blue',   icon: '📦' },
  { label: 'Customers',         value: data.value.totals?.customers       ?? '—', color: 'purple', icon: '👥' },
  { label: "Today's Revenue",   value: 'LKR ' + Number(data.value.totals?.revenue_today   ?? 0).toLocaleString(), color: 'gold',  icon: '💰' },
  { label: 'Low Stock Items',   value: data.value.totals?.low_stock_count ?? '—', color: 'red',    icon: '⚠️' },
])

const chartData = computed(() => {
  const sales = data.value.sales_chart
  if (!sales?.length) return null
  return {
    labels: sales.map(s => s.date),
    datasets: [{
      label: 'Revenue',
      data: sales.map(s => s.revenue),
      borderColor: '#d97706',
      backgroundColor: 'rgba(217,119,6,0.1)',
      fill: true,
      tension: 0.4,
    }],
  }
})

const chartOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true } },
}

function statusClass(s) {
  return {
    paid:     'bg-green-100 text-green-700',
    pending:  'bg-yellow-100 text-yellow-700',
    partial:  'bg-blue-100 text-blue-700',
    refunded: 'bg-red-100 text-red-700',
  }[s] ?? 'bg-gray-100 text-gray-700'
}

onMounted(async () => {
  try {
    const { data: d } = await axios.get('/api/dashboard')
    data.value = d
  } finally {
    loaded.value = true
  }
})
</script>
