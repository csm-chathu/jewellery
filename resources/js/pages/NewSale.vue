<template>
  <div class="space-y-0">

    <!-- Top bar -->
    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-3">
        <router-link to="/sales"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
          <ArrowLeftIcon class="w-4 h-4" /> Back to Sales
        </router-link>
        <span class="text-gray-300">/</span>
        <h2 class="text-lg font-semibold text-gray-800">New Sale</h2>
      </div>
      <div v-if="goldRate24k" class="flex items-center gap-2 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-full">
        <SparklesIcon class="w-4 h-4 text-amber-500" />
        <span class="text-xs font-semibold text-amber-700">Gold Rate: LKR {{ Number(goldRate24k.rate_per_gram).toLocaleString() }}/g today</span>
      </div>
    </div>

    <!-- POS layout: Cart left | Summary right -->
    <div class="flex gap-5 items-start">

      <!-- ───── LEFT: Cart ───── -->
      <div class="flex-1 min-w-0 space-y-4">

        <!-- Cart header -->
        <div class="card">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
              <ShoppingCartIcon class="w-4 h-4 text-amber-500" /> Cart Items
              <span v-if="form.items.length" class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ form.items.length }}
              </span>
            </h3>
            <button @click="addItem"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition-colors">
              <PlusIcon class="w-4 h-4" /> Add Item
            </button>
          </div>

          <!-- Barcode scanner input -->
          <div class="mb-3">
            <div class="relative">
              <QrCodeIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
              <input
                v-model="barcodeInput"
                type="text"
                placeholder="Scan barcode (or type barcode/SKU) and press Enter..."
                class="form-input pl-9 text-sm font-mono"
                @keyup.enter="scanBarcode"
                @keyup.tab.prevent="scanBarcode"
              />
            </div>
            <p v-if="barcodeError" class="mt-1 text-xs text-red-600 flex items-center gap-1">
              <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ barcodeError }}
            </p>
          </div>

          <!-- Empty state -->
          <div v-if="!form.items.length"
            class="flex flex-col items-center justify-center py-10 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl">
            <ShoppingCartIcon class="w-12 h-12 opacity-20 mb-2" />
            <p class="text-sm">No items yet</p>
            <button @click="addItem" class="mt-2 text-amber-600 hover:underline text-sm font-medium">+ Add first item</button>
          </div>

          <!-- Cart item rows -->
          <div class="space-y-3">
            <div v-for="(item, i) in form.items" :key="i" class="border border-gray-200 rounded-xl">

              <!-- Item header row -->
              <div class="bg-gray-50 px-4 py-2.5 flex items-start gap-3 border-b border-gray-100">
                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 text-xs font-bold flex items-center justify-center shrink-0 mt-2">{{ i + 1 }}</span>
                <div class="relative flex-1">
                  <input
                    v-model="item.product_search"
                    type="text"
                    class="form-input w-full font-medium"
                    placeholder="Type product name, SKU, or barcode..."
                    @input="item.product_search?.trim() ? openProductDropdown(item) : (item.product_dropdown_open = false)"
                    @focus="item.product_search?.trim() ? openProductDropdown(item) : null"
                    @keyup.enter.prevent="openProductDropdown(item)"
                  />
                  <div v-if="item.product_dropdown_open" class="absolute left-0 right-0 top-full z-50 mt-1 max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                    <button
                      v-for="p in searchProducts(item.product_search)"
                      :key="p.id"
                      type="button"
                      class="flex w-full items-start justify-between gap-3 px-3 py-2 text-left hover:bg-amber-50 border-b border-gray-100 last:border-b-0"
                      @mousedown.prevent="selectProduct(item, p)"
                    >
                      <div class="shrink-0 w-10 h-10 rounded-md bg-gray-100 overflow-hidden flex items-center justify-center">
                        <img v-if="p.image" :src="p.image" :alt="p.name" class="w-full h-full object-cover" />
                        <span v-else class="text-gray-300 text-lg">💎</span>
                      </div>
                      <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ p.name }}</p>
                        <p class="text-[11px] text-gray-500 truncate">
                          SKU: {{ p.sku }}<span v-if="p.barcode"> · Barcode: {{ p.barcode }}</span>
                        </p>
                      </div>
                      <div class="shrink-0 text-right text-[11px] text-gray-500">
                        <p>{{ p.karat ? p.karat : '—' }}</p>
                        <p>Stock: {{ p.stock_quantity }}</p>
                      </div>
                    </button>
                    <div v-if="!searchProducts(item.product_search).length" class="px-3 py-2 text-sm text-gray-400">
                      No products found
                    </div>
                  </div>
                </div>
                <button @click="removeItem(i)"
                  class="w-7 h-7 flex items-center justify-center rounded-full text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors shrink-0 mt-1">
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>

              <!-- Item fields -->
              <div class="px-4 py-3 space-y-3">
                <div class="grid grid-cols-3 gap-3">
                  <div>
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Quantity</label>
                    <input v-model.number="item.quantity" type="number" min="1" class="form-input text-center font-semibold" @input="recalcItem(item)" />
                  </div>
                  <div>
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Unit Price (LKR)</label>
                    <input v-model.number="item.unit_price" type="number" min="0" class="form-input" @input="recalcItem(item)" />
                  </div>
                  <div>
                    <label class="text-xs font-medium text-gray-500 mb-1 block">Item Discount (LKR)</label>
                    <input v-model.number="item.discount" type="number" min="0" class="form-input" @input="recalcItem(item)" />
                  </div>
                </div>

                <!-- Value breakdown (gold items) -->
                <div v-if="item.product_ref" class="bg-amber-50/60 border border-amber-100 rounded-lg p-3 space-y-2">
                  <div class="flex items-center gap-2">
                    <img
                      v-if="item.product_ref.image"
                      :src="item.product_ref.image"
                      alt="product"
                      class="w-10 h-10 rounded-md object-cover border border-amber-200"
                    />
                    <div class="min-w-0">
                      <p class="text-xs font-semibold text-gray-700 truncate">{{ item.product_ref.name }}</p>
                      <p class="text-[11px] text-gray-500">{{ item.product_ref.sku }}</p>
                    </div>
                  </div>
                  <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                    <SparklesIcon class="w-3.5 h-3.5" /> Value Breakdown
                  </p>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-if="item.product_ref.karat">
                      <label class="text-xs text-gray-500 block mb-1">Gold Value (LKR)</label>
                      <input v-model.number="item.gold_value" type="number" min="0" step="0.01" class="form-input text-sm" @input="recalcItem(item)" />
                      <p v-if="item.gold_value_auto" class="text-xs text-amber-600 mt-0.5">≈ {{ lkr(item.gold_value_auto) }} auto</p>
                    </div>
                    <div v-if="item.product_ref.gemstone">
                      <label class="text-xs text-gray-500 block mb-1">Gemstone (LKR)</label>
                      <input v-model.number="item.gemstone_value" type="number" min="0" step="0.01" class="form-input text-sm" @input="recalcItem(item)" />
                      <p class="text-xs text-gray-400 mt-0.5">{{ item.product_ref.gemstone }}</p>
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 block mb-1">Making Charge (LKR)</label>
                      <input v-model.number="item.making_charge" type="number" min="0" step="0.01" class="form-input text-sm" @input="recalcItem(item)" />
                      <p v-if="item.making_charge_auto" class="text-xs text-gray-400 mt-0.5">≈ {{ lkr(item.making_charge_auto) }} auto</p>
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 block mb-1">Wastage (LKR)</label>
                      <input v-model.number="item.wastage_amount" type="number" min="0" step="0.01" class="form-input text-sm" @input="recalcItem(item)" />
                      <p v-if="item.wastage_auto" class="text-xs text-gray-400 mt-0.5">≈ {{ lkr(item.wastage_auto) }} auto</p>
                    </div>
                  </div>
                  <!-- Line total summary -->
                  <div class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1.5 border-t border-amber-100 text-xs text-gray-500">
                    <span v-if="item.product_ref.karat">Gold: <strong class="text-amber-700">LKR {{ lkr(item.gold_value * item.quantity) }}</strong></span>
                    <span v-if="item.gemstone_value">Gems: <strong>LKR {{ lkr(item.gemstone_value) }}</strong></span>
                    <span v-if="item.making_charge">Making: <strong>LKR {{ lkr(item.making_charge) }}</strong></span>
                    <span v-if="item.wastage_amount">Wastage: <strong>LKR {{ lkr(item.wastage_amount) }}</strong></span>
                    <span class="ml-auto font-bold text-gray-800 text-sm">
                      Line: <span class="text-amber-700">LKR {{ lkr(item._lineTotal) }}</span>
                    </span>
                  </div>
                </div>
                <div v-else-if="item.unit_price > 0" class="text-right text-sm text-gray-500">
                  Line Total: <strong class="text-amber-700">LKR {{ lkr(item._lineTotal) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="card">
          <label class="form-label flex items-center gap-1.5 mb-2">
            <ChatBubbleLeftIcon class="w-4 h-4 text-gray-400" /> Sale Notes (optional)
          </label>
          <textarea v-model="form.notes" rows="2" placeholder="Any special instructions or remarks…" class="form-input resize-none"></textarea>
        </div>
      </div>

      <!-- ───── RIGHT: Order summary ───── -->
      <div class="w-80 xl:w-96 shrink-0 space-y-4 sticky top-4">

        <!-- Customer -->
        <div class="card space-y-2">
          <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
              <UserIcon class="w-4 h-4 text-blue-500" /> Customer
            </h3>
            <button @click="showNewCustomer = !showNewCustomer" type="button"
              class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-md transition-colors"
              :class="showNewCustomer ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
              <UserPlusIcon class="w-3.5 h-3.5" />
              {{ showNewCustomer ? 'Cancel' : 'New Customer' }}
            </button>
          </div>

          <!-- Quick new-customer form -->
          <div v-if="showNewCustomer" class="bg-blue-50 border border-blue-200 rounded-lg p-3 space-y-2">
            <p class="text-xs font-semibold text-blue-700 mb-1 flex items-center gap-1.5">
              <UserPlusIcon class="w-3.5 h-3.5" /> Quick Add Customer
            </p>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-xs text-gray-500 block mb-1">Name <span class="text-red-400">*</span></label>
                <input v-model="newCustomer.name" type="text" placeholder="Full name"
                  class="form-input text-sm" @keyup.enter="saveNewCustomer" />
              </div>
              <div>
                <label class="text-xs text-gray-500 block mb-1">NIC</label>
                <input v-model="newCustomer.nic" type="text" placeholder="NIC (optional)"
                  class="form-input text-sm" />
              </div>
            </div>
            <div>
              <label class="text-xs text-gray-500 block mb-1">Phone</label>
              <input v-model="newCustomer.phone" type="tel" placeholder="Phone number"
                class="form-input text-sm" @keyup.enter="saveNewCustomer" />
            </div>
            <div>
              <label class="text-xs text-gray-500 block mb-1">Address</label>
              <input v-model="newCustomer.address" type="text" placeholder="Address (optional)"
                class="form-input text-sm" />
            </div>
            <p v-if="newCustomerError" class="text-xs text-red-600">{{ newCustomerError }}</p>
            <button @click="saveNewCustomer" :disabled="savingCustomer || !newCustomer.name.trim()" type="button"
              class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors">
              <ArrowPathIcon v-if="savingCustomer" class="w-3.5 h-3.5 animate-spin" />
              <CheckCircleIcon v-else class="w-3.5 h-3.5" />
              {{ savingCustomer ? 'Saving…' : 'Save & Select' }}
            </button>
          </div>

          <!-- Existing customer selector -->
          <select v-if="!showNewCustomer" v-model="form.customer_id" class="form-input">
            <option value="">Walk-in / No customer</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">
              {{ c.name }}{{ c.phone ? ` · ${c.phone}` : '' }}
            </option>
          </select>

          <!-- Selected customer chip -->
          <div v-if="!showNewCustomer && selectedCustomer" class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-2.5 py-1.5">
            <div class="w-6 h-6 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 text-xs font-bold shrink-0">
              {{ selectedCustomer.name[0].toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-blue-800 truncate">{{ selectedCustomer.name }}</p>
              <p v-if="selectedCustomer.phone" class="text-xs text-blue-500">{{ selectedCustomer.phone }}</p>
            </div>
          </div>

          <div v-if="selectedCustomer?.id_number && !selectedCustomer?.kyc_verified"
            class="text-xs bg-yellow-50 border border-yellow-200 text-yellow-700 rounded px-2 py-1.5 flex items-center gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> KYC not verified for this customer
          </div>
        </div>

        <!-- Payment -->
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700 flex items-center gap-2 mb-1">
            <CreditCardIcon class="w-4 h-4 text-green-500" /> Payment
          </h3>
          <div>
            <label class="form-label">Sale Type</label>
            <select v-model="form.sale_type" class="form-input" @change="onSaleTypeChange">
              <option value="instant">Instant Sale (deliver now)</option>
              <option value="booking">Booking (advance now, deliver later)</option>
            </select>
          </div>
          <div v-if="form.sale_type === 'booking'">
            <label class="form-label">Booking Expiry (max 3 months)</label>
            <input v-model="form.booking_expires_at" type="date" class="form-input" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">Method</label>
              <select v-model="form.payment_method" class="form-input">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>
            <div>
              <label class="form-label">Status</label>
              <select v-model="form.payment_status" class="form-input" @change="onPaymentStatusChange">
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="partial">Partial</option>
              </select>
            </div>
          </div>
          <div>
            <label class="form-label">Overall Discount (LKR)</label>
            <input v-model.number="form.discount" type="number" min="0" class="form-input" @input="recalc" />
          </div>

          <!-- Tax -->
          <div class="border-t pt-3">
            <div class="flex items-center justify-between mb-2">
              <label class="form-label mb-0 flex items-center gap-1.5">
                <CalculatorIcon class="w-3.5 h-3.5 text-gray-400" /> Tax
              </label>
              <select v-model="selectedTaxId" class="form-input text-xs py-1 w-32" @change="applyTax">
                <option value="">No Tax</option>
                <option v-for="t in taxes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.rate }}%)</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-xs text-gray-400">Rate (%)</label>
                <input v-model.number="form.tax_rate" type="number" min="0" step="0.01" class="form-input mt-1" @input="recalc" />
              </div>
              <div>
                <label class="text-xs text-gray-400">Tax Amount (LKR)</label>
                <input v-model.number="form.tax" type="number" class="form-input mt-1 bg-gray-50 text-gray-500" readonly />
              </div>
            </div>
          </div>
        </div>

        <!-- Order total card (dark) -->
        <div class="card bg-gray-800 text-white">
          <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-1.5">
            <ReceiptPercentIcon class="w-3.5 h-3.5" /> Order Summary
          </h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-400">Subtotal</span>
              <span class="font-medium">LKR {{ lkr(subtotal) }}</span>
            </div>
            <div v-if="goldTotal > 0" class="flex justify-between text-xs">
              <span class="text-gray-500 pl-3">↳ Gold value</span>
              <span class="text-amber-400">LKR {{ lkr(goldTotal) }}</span>
            </div>
            <div v-if="gemTotal > 0" class="flex justify-between text-xs">
              <span class="text-gray-500 pl-3">↳ Gemstones</span>
              <span class="text-purple-300">LKR {{ lkr(gemTotal) }}</span>
            </div>
            <div v-if="mcTotal > 0" class="flex justify-between text-xs">
              <span class="text-gray-500 pl-3">↳ Making charges</span>
              <span class="text-blue-300">LKR {{ lkr(mcTotal) }}</span>
            </div>
            <div v-if="wasteTotal > 0" class="flex justify-between text-xs">
              <span class="text-gray-500 pl-3">↳ Wastage</span>
              <span class="text-orange-300">LKR {{ lkr(wasteTotal) }}</span>
            </div>
            <div v-if="form.discount > 0" class="flex justify-between">
              <span class="text-gray-400">Discount</span>
              <span class="text-red-400">-LKR {{ lkr(form.discount) }}</span>
            </div>
            <div v-if="form.tax > 0" class="flex justify-between">
              <span class="text-gray-400">Tax ({{ form.tax_rate }}%)</span>
              <span class="text-blue-300">+LKR {{ lkr(form.tax) }}</span>
            </div>
            <div class="border-t border-gray-600 pt-2 flex justify-between text-lg font-bold">
              <span>Total</span>
              <span class="text-amber-400">LKR {{ lkr(total) }}</span>
            </div>
          </div>

          <div class="mt-3 pt-3 border-t border-gray-600">
            <label class="text-xs text-gray-400 mb-1 block">Amount Paid (LKR)</label>
            <input v-model.number="form.amount_paid" type="number" min="0"
              class="w-full bg-gray-700 text-white border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div v-if="form.payment_status === 'partial' && form.amount_paid < total"
            class="mt-2 text-xs text-yellow-300 bg-yellow-900/40 rounded px-2 py-1.5 flex items-center gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" />
            Balance due: LKR {{ lkr(total - form.amount_paid) }}
          </div>
          <div v-if="form.payment_status === 'paid' && form.amount_paid > total"
            class="mt-2 text-xs text-green-300 bg-green-900/40 rounded px-2 py-1.5 flex items-center gap-1.5">
            Change to return: LKR {{ lkr(form.amount_paid - total) }}
          </div>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg flex items-center gap-2">
          <ExclamationTriangleIcon class="w-4 h-4 shrink-0" /> {{ error }}
        </p>

        <!-- Submit button -->
        <button @click="submit" :disabled="saving || !form.items.length"
          class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl font-bold text-base shadow-md transition-colors">
          <CheckCircleIcon v-if="!saving" class="w-5 h-5" />
          <ArrowPathIcon v-else class="w-5 h-5 animate-spin" />
          {{ saving ? 'Processing…' : 'Complete Sale' }}
        </button>
        <p class="text-center text-xs text-gray-400">
          {{ form.items.length }} item{{ form.items.length !== 1 ? 's' : '' }} · Total: LKR {{ lkr(total) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import {
  ArrowLeftIcon, PlusIcon, XMarkIcon, SparklesIcon,
  ShoppingCartIcon, UserIcon, UserPlusIcon, CreditCardIcon, CalculatorIcon,
  ReceiptPercentIcon, CheckCircleIcon, ArrowPathIcon,
  ExclamationTriangleIcon, ChatBubbleLeftIcon, QrCodeIcon,
} from '@heroicons/vue/24/outline'

const router        = useRouter()
const products      = ref([])
const customers     = ref([])
const taxes         = ref([])
const goldRateMap   = ref({})  // { '24k': {rate_per_gram, carat}, '22k': ... }
const goldRate24k   = computed(() => goldRateMap.value['24k'] ?? null)
const saving        = ref(false)
const error         = ref('')
const selectedTaxId    = ref('')
const showNewCustomer  = ref(false)
const savingCustomer   = ref(false)
const newCustomerError = ref('')
const newCustomer      = reactive({ name: '', phone: '', nic: '', address: '' })

// Barcode scanner
const barcodeInput = ref('')
const barcodeError = ref('')
let barcodeClearTimer = null

function scanBarcode() {
  const code = barcodeInput.value.trim()
  barcodeInput.value = ''
  if (!code) return

  const product = products.value.find(p =>
    p.barcode?.toLowerCase() === code.toLowerCase() ||
    p.sku?.toLowerCase() === code.toLowerCase()
  )
  if (!product) {
    barcodeError.value = `Barcode/SKU "${code}" not found`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    return
  }
  if (product.stock_quantity < 1) {
    barcodeError.value = `"${product.name}" is out of stock`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    return
  }

  // If product already in cart, increment quantity
  const existing = form.items.find(i => i.product_id == product.id)
  if (existing) {
    existing.quantity++
    recalcItem(existing)
  } else {
    const item = newItem()
    item.product_id = product.id
    form.items.push(item)
    fillProduct(item)
  }
  barcodeError.value = ''
}

const KARAT_PURITY = { '9k': 9/24, '14k': 14/24, '18k': 18/24, '22k': 22/24, '24k': 1 }

function rateForKarat(karatStr) {
  const key = karatStr?.toLowerCase()
  if (goldRateMap.value[key]) return goldRateMap.value[key].rate_per_gram
  const r24k = goldRateMap.value['24k']?.rate_per_gram
  if (!r24k) return null
  return r24k * (KARAT_PURITY[key] ?? 1)
}

function addMonths(date, months) {
  const d = new Date(date)
  d.setMonth(d.getMonth() + months)
  return d.toISOString().slice(0, 10)
}

const form = reactive({
  customer_id: '', payment_method: 'cash', payment_status: 'paid',
  discount: 0, tax: 0, tax_rate: 0, amount_paid: 0, notes: '',
  sale_type: 'instant', booking_expires_at: addMonths(new Date(), 3),
  items: [],
})

function normalizeText(value) {
  return String(value ?? '').toLowerCase().trim()
}

function searchProducts(term) {
  const query = normalizeText(term)
  const list = products.value.filter((product) => {
    if (!query) return true
    return [product.name, product.sku, product.barcode, product.category?.name]
      .some(field => normalizeText(field).includes(query))
  })

  return list
    .filter(product => product.stock_quantity > 0 || product.stock_quantity === 0)
    .slice(0, 20)
}

function openProductDropdown(item) {
  item.product_dropdown_open = true
}

function selectProduct(item, product) {
  item.product_id = product.id
  item.product_search = [product.name, product.sku ? `SKU: ${product.sku}` : null, product.barcode ? `Barcode: ${product.barcode}` : null]
    .filter(Boolean)
    .join(' · ')
  item.product_dropdown_open = false
  fillProduct(item)
}

const selectedCustomer = computed(() =>
  customers.value.find(c => c.id == form.customer_id) ?? null
)

async function saveNewCustomer() {
  if (!newCustomer.name.trim()) return
  savingCustomer.value = true; newCustomerError.value = ''
  try {
    const { data } = await axios.post('/api/customers', {
      name:    newCustomer.name.trim(),
      phone:   newCustomer.phone.trim() || null,
      nic:     newCustomer.nic.trim() || null,
      address: newCustomer.address.trim() || null,
    })
    customers.value.unshift(data)
    form.customer_id = data.id
    showNewCustomer.value = false
    newCustomer.name = ''; newCustomer.phone = ''; newCustomer.nic = ''; newCustomer.address = ''
  } catch (e) {
    newCustomerError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Could not save customer'
  } finally { savingCustomer.value = false }
}

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function newItem() {
  return {
    product_id: '', product_search: '', product_dropdown_open: false,
    quantity: 1, unit_price: 0, discount: 0,
    gold_value: 0, gemstone_value: 0, making_charge: 0, wastage_amount: 0,
    gold_value_auto: 0, making_charge_auto: 0, wastage_auto: 0,
    product_ref: null, _lineTotal: 0,
  }
}

function addItem()     { form.items.push(newItem()) }
function removeItem(i) { form.items.splice(i, 1); recalc() }

function fillProduct(item) {
  const p = products.value.find(x => x.id == item.product_id)
  if (!p) { item.product_ref = null; return }
  item.product_ref = p
  item.product_search = [p.name, p.sku ? `SKU: ${p.sku}` : null, p.barcode ? `Barcode: ${p.barcode}` : null]
    .filter(Boolean)
    .join(' · ')

  // Reset all breakdown fields to 0
  item.gold_value         = 0
  item.gold_value_auto    = 0
  item.gemstone_value     = 0
  item.making_charge      = 0
  item.making_charge_auto = 0
  item.wastage_amount     = 0
  item.wastage_auto       = 0

  if (p.weight && p.karat) {
    const rate = rateForKarat(p.karat)
if (rate) {
      const goldVal = Math.round(parseFloat(rate) * parseFloat(p.weight) * 100) / 100
      item.gold_value_auto = goldVal
      item.gold_value      = goldVal
    }
  }

  // Hint values for display only — NOT applied to breakdown fields
  if (p.making_charge > 0) {
    const mc = p.making_charge_type === 'per_gram'   ? p.making_charge * parseFloat(p.weight ?? 0)
             : p.making_charge_type === 'per_piece'  ? p.making_charge
             : p.making_charge_type === 'percentage' ? (item.gold_value * p.making_charge / 100)
             : 0
    item.making_charge_auto = Math.round(mc * 100) / 100
  }

  if (p.wastage_percent > 0 && p.weight && p.karat) {
    const rate = rateForKarat(p.karat)
    if (rate) {
      item.wastage_auto = Math.round(parseFloat(rate) * parseFloat(p.weight) * (p.wastage_percent / 100) * 100) / 100
    }
  }

  // unit_price = gold value only (all other breakdown fields are 0)
  item.unit_price = item.gold_value
  item._lineTotal = Math.round((item.unit_price * (item.quantity || 1)) * 100) / 100
  recalc()
}

function recalcItem(item) {
  if (item.product_ref?.karat) {
    // Always sum breakdown fields; when all are 0 this equals gold_value
    item.unit_price = Math.round(
      ((item.gold_value || 0) + (item.gemstone_value || 0) + (item.making_charge || 0) + (item.wastage_amount || 0)) * 100
    ) / 100
  }
  item._lineTotal = Math.round(((item.unit_price || 0) * (item.quantity || 1) - (item.discount || 0)) * 100) / 100
  recalc()
}

const subtotal   = computed(() => form.items.reduce((s, i) => s + (i._lineTotal || 0), 0))
const goldTotal  = computed(() => form.items.reduce((s, i) => s + ((i.gold_value || 0) * (i.quantity || 1)), 0))
const gemTotal   = computed(() => form.items.reduce((s, i) => s + (i.gemstone_value || 0), 0))
const mcTotal    = computed(() => form.items.reduce((s, i) => s + (i.making_charge || 0), 0))
const wasteTotal = computed(() => form.items.reduce((s, i) => s + (i.wastage_amount || 0), 0))
const total      = computed(() => Math.max(0, subtotal.value - (form.discount || 0) + (form.tax || 0)))

function recalc() {
  if (form.tax_rate > 0) {
    form.tax = Math.round(subtotal.value * (form.tax_rate / 100) * 100) / 100
  }
  if (form.payment_status === 'paid') {
    form.amount_paid = total.value
  }
}

function onPaymentStatusChange() {
  if (form.payment_status === 'paid') form.amount_paid = total.value
  if (form.payment_status === 'pending') form.amount_paid = 0
}

function onSaleTypeChange() {
  if (form.sale_type === 'booking') {
    if (!form.customer_id) form.payment_status = 'partial'
    if (!form.booking_expires_at) form.booking_expires_at = addMonths(new Date(), 3)
  } else {
    form.payment_status = 'paid'
    form.amount_paid = total.value
  }
}

function applyTax() {
  const t = taxes.value.find(x => x.id == selectedTaxId.value)
  if (t) { form.tax_rate = t.rate; recalc() }
  else   { form.tax_rate = 0; form.tax = 0 }
}

async function submit() {
  saving.value = true; error.value = ''
  try {
    const { data } = await axios.post('/api/sales', {
      customer_id:    form.customer_id || null,
      payment_method: form.payment_method,
      payment_status: form.payment_status,
      sale_type:      form.sale_type,
      booking_expires_at: form.sale_type === 'booking' ? form.booking_expires_at : null,
      discount:       form.discount,
      tax:            form.tax,
      tax_rate:       form.tax_rate,
      amount_paid:    form.amount_paid,
      notes:          form.notes,
      total:          total.value,
      subtotal:       subtotal.value,
      items: form.items.map(i => ({
        product_id:     i.product_id,
        quantity:       i.quantity,
        unit_price:     i.unit_price,
        discount:       i.discount,
        gold_value:     i.gold_value,
        gemstone_value: i.gemstone_value,
        making_charge:  i.making_charge,
        wastage_amount: i.wastage_amount,
      })),
    })
    router.push(`/sales/${data.id}`)
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'An error occurred. Please try again.'
  } finally { saving.value = false }
}

onMounted(async () => {
  const [p, c, t, gr] = await Promise.all([
    axios.get('/api/products', { params: { per_page: 2000 } }),
    axios.get('/api/customers/all'),
    axios.get('/api/tax-settings'),
    axios.get('/api/gold-rates/today').catch(() => ({ data: null })),
  ])
  products.value  = p.data.data
  customers.value = c.data
  taxes.value     = t.data.filter(x => x.is_active)
  const rates = Array.isArray(gr.data) ? gr.data : []
  goldRateMap.value = Object.fromEntries(rates.map(r => [r.carat?.label?.toLowerCase(), r]).filter(([k]) => k))
})
</script>

