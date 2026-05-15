<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen text-gray-400">
      <svg class="w-6 h-6 animate-spin mr-3" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
      </svg>
      Loading receipt…
    </div>

    <!-- Not found -->
    <div v-else-if="!sale" class="flex flex-col items-center justify-center min-h-screen text-gray-400 px-4">
      <div class="text-6xl mb-4">🔍</div>
      <h2 class="text-xl font-semibold text-gray-700 mb-2">Receipt not found</h2>
      <p class="text-sm text-center">This link may be invalid or the receipt is no longer available.</p>
    </div>

    <!-- Receipt -->
    <div v-else class="max-w-lg mx-auto px-4 py-8">

      <!-- Branding header -->
      <div class="text-center mb-6">
        <img v-if="shop.logo_url" :src="shop.logo_url" alt="logo"
          class="h-14 mx-auto object-contain mb-3" />
        <h1 class="text-xl font-bold uppercase tracking-wide text-gray-800">{{ shop.shop_name || 'Jewellery Store' }}</h1>
        <p v-if="shop.address" class="text-xs text-gray-500 mt-0.5">{{ shop.address }}</p>
        <p v-if="shop.phone" class="text-xs text-gray-500">{{ shop.phone }}</p>
      </div>

      <!-- Invoice card -->
      <div class="bg-white rounded-2xl shadow-md overflow-hidden">

        <!-- Invoice header stripe -->
        <div class="bg-gray-800 px-6 py-4 text-white">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs uppercase tracking-wider text-gray-400 mb-0.5">Invoice</p>
              <p class="text-lg font-bold font-mono">{{ sale.invoice_number }}</p>
            </div>
            <div class="text-right">
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                :class="statusBadgeClass">
                {{ statusLabel }}
              </span>
            </div>
          </div>
          <div class="mt-2 flex gap-4 text-xs text-gray-400">
            <span>Date: <span class="text-gray-200">{{ fmtDate(sale.sold_at || sale.created_at) }}</span></span>
            <span v-if="sale.payment_method">Method: <span class="text-gray-200 capitalize">{{ sale.payment_method.replace('_', ' ') }}</span></span>
          </div>
        </div>

        <!-- Customer section -->
        <div v-if="sale.customer" class="px-6 py-3 bg-blue-50 border-b border-blue-100">
          <p class="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-0.5">Customer</p>
          <p class="text-sm font-semibold text-gray-800">{{ sale.customer.name }}</p>
          <p v-if="sale.customer.phone" class="text-xs text-gray-500">{{ sale.customer.phone }}</p>
        </div>

        <!-- Items -->
        <div class="px-6 py-4">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Items</p>
          <div class="space-y-3">
            <div v-for="item in sale.items" :key="item.id"
              class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 last:border-b-0 last:pb-0">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-800">{{ item.product?.name ?? item.description ?? 'Item' }}</p>
                <p v-if="item.product?.sku" class="text-xs text-gray-400">SKU: {{ item.product.sku }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                  {{ item.quantity }} × LKR {{ lkr(item.unit_price) }}
                  <span v-if="Number(item.discount) > 0"> − LKR {{ lkr(item.discount) }}</span>
                </p>
              </div>
              <p class="text-sm font-semibold text-gray-800 shrink-0">LKR {{ lkr(item.total) }}</p>
            </div>
          </div>
        </div>

        <!-- Totals -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 space-y-1.5">
          <div class="flex justify-between text-sm text-gray-600">
            <span>Subtotal</span>
            <span>LKR {{ lkr(sale.subtotal) }}</span>
          </div>
          <div v-if="Number(sale.discount) > 0" class="flex justify-between text-sm text-red-600">
            <span>Discount</span>
            <span>-LKR {{ lkr(sale.discount) }}</span>
          </div>
          <div v-if="Number(sale.tax) > 0" class="flex justify-between text-sm text-gray-600">
            <span>Tax ({{ sale.tax_rate }}%)</span>
            <span>+LKR {{ lkr(sale.tax) }}</span>
          </div>
          <div class="flex justify-between font-bold text-gray-800 text-base pt-2 border-t border-gray-200">
            <span>Total</span>
            <span class="text-amber-600">LKR {{ lkr(sale.total) }}</span>
          </div>
          <div class="flex justify-between text-sm text-gray-600">
            <span>Amount Paid</span>
            <span>LKR {{ lkr(sale.amount_paid) }}</span>
          </div>
          <div v-if="Number(sale.amount_paid) < Number(sale.total)"
            class="flex justify-between text-sm font-semibold text-red-600">
            <span>Balance Due</span>
            <span>LKR {{ lkr(Number(sale.total) - Number(sale.amount_paid)) }}</span>
          </div>
        </div>

        <!-- Booking info (if applicable) -->
        <div v-if="sale.sale_type === 'booking'" class="px-6 py-3 bg-purple-50 border-t border-purple-100">
          <p class="text-xs font-semibold text-purple-700 uppercase tracking-wider mb-1">Booking Info</p>
          <div class="text-xs text-purple-800 space-y-0.5">
            <p v-if="sale.booking_expires_at">Expires: <strong>{{ fmtDate(sale.booking_expires_at) }}</strong></p>
            <p v-if="sale.delivered_at">Collected: <strong>{{ fmtDate(sale.delivered_at) }}</strong></p>
            <p>Status: <strong>{{ sale.delivery_status === 'booked' ? 'Pending Collection' : 'Collected' }}</strong></p>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 text-center border-t border-gray-100">
          <p class="text-sm font-semibold text-gray-700">Thank you for your purchase!</p>
          <p v-if="shop.shop_name" class="text-xs text-gray-400 mt-1">{{ shop.shop_name }}</p>
          <p class="text-xs text-gray-300 mt-3">This is a digital receipt. Please keep for your records.</p>
        </div>

      </div>

      <!-- Print button -->
      <div class="text-center mt-6">
        <button @click="window.print()"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-medium text-sm shadow-sm transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z" />
          </svg>
          Print Receipt
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { fmtDate } from '../utils/date.js'

const route  = useRoute()
const sale   = ref(null)
const shop   = ref({ shop_name: '', address: '', phone: '', logo_url: '' })
const loading = ref(true)

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const statusLabel = computed(() => {
  if (!sale.value) return ''
  if (sale.value.sale_type === 'booking') {
    return sale.value.delivery_status === 'booked' ? 'Booking — Pending' : 'Booking — Collected'
  }
  return sale.value.payment_status === 'paid' ? 'Paid' : sale.value.payment_status
})

const statusBadgeClass = computed(() => {
  if (!sale.value) return 'bg-gray-600 text-white'
  if (sale.value.payment_status === 'paid') return 'bg-green-500 text-white'
  if (sale.value.payment_status === 'partial') return 'bg-yellow-400 text-gray-900'
  return 'bg-gray-600 text-white'
})

onMounted(async () => {
  try {
    const [saleRes, brandRes] = await Promise.all([
      axios.get(`/api/sales/public/${route.params.token}`),
      axios.get('/api/shop-branding').catch(() => ({ data: {} })),
    ])
    sale.value = saleRes.data.sale
    const b = saleRes.data.shop ?? brandRes.data
    Object.keys(shop.value).forEach(k => { if (b[k] != null) shop.value[k] = b[k] })
  } catch {
    sale.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style>
@media print {
  @page { size: A5; margin: 10mm; }
  button { display: none !important; }
}
</style>
