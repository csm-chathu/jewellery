<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
      <router-link to="/purchases" class="text-gray-500 hover:text-gray-700 text-sm">← Back</router-link>
      <h2 class="text-xl font-semibold text-gray-800">New Purchase Order</h2>
    </div>

    <div class="grid grid-cols-3 gap-6">
      <div class="col-span-2 space-y-4">
        <div class="card">
          <h3 class="font-semibold mb-4 text-gray-700">Items</h3>

          <div v-for="(item, i) in form.items" :key="i"
               class="mb-4 pb-4 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">

            <!-- Toggle + remove -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex rounded-lg overflow-hidden border border-gray-200 text-xs font-medium">
                <button
                  @click="item.is_new = false; item.product_id = ''"
                  :class="!item.is_new ? 'bg-gold-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                  class="px-3 py-1 transition-colors"
                >Existing</button>
                <button
                  @click="item.is_new = true; item.product_id = null"
                  :class="item.is_new ? 'bg-gold-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                  class="px-3 py-1 border-l border-gray-200 transition-colors"
                >+ New Item</button>
              </div>
              <button @click="form.items.splice(i,1)" class="text-red-400 hover:text-red-600 text-sm">Remove</button>
            </div>

            <!-- Existing product -->
            <div v-if="!item.is_new" class="grid grid-cols-[1fr_72px_120px] gap-2">
              <div>
                <label class="form-label">Product</label>
                <div class="relative">
                  <!-- Search input -->
                  <input
                    v-model="item._search"
                    type="text"
                    class="form-input pr-8"
                    :placeholder="item.product_id ? selectedProductLabel(item) : 'Search by name or SKU…'"
                    @focus="item._open = true"
                    @input="item.product_id = ''; item._open = true"
                    @keydown.escape="item._open = false"
                    @keydown.enter.prevent="pickFirst(item)"
                    autocomplete="off"
                  />
                  <button v-if="item.product_id" @click="clearProduct(item)"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm leading-none">✕</button>
                  <!-- Dropdown -->
                  <ul v-if="item._open && filteredProducts(item).length"
                    class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-52 overflow-y-auto text-sm">
                    <li
                      v-for="p in filteredProducts(item)" :key="p.id"
                      @mousedown.prevent="selectProduct(item, p)"
                      class="px-3 py-2 hover:bg-gold-50 cursor-pointer flex items-center justify-between gap-2"
                    >
                      <span class="font-medium truncate">{{ p.name }}</span>
                      <span class="text-xs text-gray-400 shrink-0">{{ p.sku }} · {{ p.karat || '—' }}</span>
                    </li>
                  </ul>
                  <p v-if="item._open && item._search && !filteredProducts(item).length"
                    class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow text-sm text-gray-400 px-3 py-2">
                    No products found
                  </p>
                </div>
              </div>
              <div>
                <label class="form-label">Qty</label>
                <input v-model.number="item.quantity" type="number" min="1" class="form-input" />
              </div>
              <div>
                <label class="form-label">Buy Price (LKR)</label>
                <input v-model.number="item.unit_cost" type="number" min="0" class="form-input" />
              </div>
            </div>

            <!-- New product -->
            <div v-else class="space-y-3">
              <!-- Basic info -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Item Name *</label>
                  <input v-model="item.new_product.name" class="form-input" placeholder="e.g. Gold Necklace 22K" />
                </div>
                <div>
                  <label class="form-label">Category *</label>
                  <select v-model="item.new_product.category_id" class="form-input">
                    <option value="">Select category…</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
              </div>

              <!-- Material / Karat / Weight -->
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="form-label">Material</label>
                  <input v-model="item.new_product.material" class="form-input" placeholder="Gold, Silver…" />
                </div>
                <div>
                  <label class="form-label">Karat</label>
                  <select v-model="item.new_product.karat" class="form-input">
                    <option value="">— None —</option>
                    <option v-for="k in carats" :key="k.id" :value="k.label">{{ k.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Weight (g)</label>
                  <input v-model.number="item.new_product.weight" type="number" min="0" step="0.01" class="form-input" />
                </div>
              </div>

              <!-- Qty / Buy -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Qty *</label>
                  <input v-model.number="item.quantity" type="number" min="1" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Buy Price (LKR) *</label>
                  <input v-model.number="item.unit_cost" type="number" min="0" class="form-input" />
                </div>
              </div>

              <!-- Image -->
              <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                <SmartImageUploader
                  :ref="el => { if (el) uploaderRefs[i] = el }"
                  v-model="item.new_product.images"
                  label="Product Image"
                  :multiple="false"
                  folder="products"
                />
              </div>
            </div>

            <!-- Line total -->
            <div class="text-right text-xs text-gray-500 mt-2">
              Line total: <span class="font-semibold text-gray-700">LKR {{ (item.unit_cost * item.quantity).toLocaleString() }}</span>
            </div>
          </div>

          <button @click="addItem" class="btn-secondary text-sm mt-2">+ Add Item</button>
        </div>
      </div>

      <div class="space-y-4">
        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700">Supplier *</h3>
          <select v-model="form.supplier_id" required class="form-input">
            <option value="">Select supplier…</option>
            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <div class="card space-y-3">
          <h3 class="font-semibold text-gray-700">Details</h3>
          <div><label class="form-label">Status</label>
            <select v-model="form.status" class="form-input">
              <option value="received">Received</option>
              <option value="pending">Pending</option>
              <option value="partial">Partial</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div><label class="form-label">Payment Method</label>
            <select v-model="form.payment_method" class="form-input">
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="credit">Credit</option>
            </select>
          </div>
          <template v-if="form.payment_method === 'cheque'">
            <div><label class="form-label">Cheque Number *</label>
              <input v-model="form.cheque_number" class="form-input" />
            </div>
            <div><label class="form-label">Cheque Date *</label>
              <input v-model="form.cheque_date" type="date" class="form-input" />
            </div>
            <div><label class="form-label">Cheque Bank Name *</label>
              <input v-model="form.cheque_bank_name" class="form-input" />
            </div>
          </template>
          <div><label class="form-label">Tax (LKR)</label>
            <input v-model.number="form.tax" type="number" min="0" class="form-input" />
          </div>
          <div class="border-t pt-3 space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>LKR {{ subtotal.toLocaleString() }}</span></div>
            <div class="flex justify-between font-bold text-gray-800 text-base"><span>Total</span><span>LKR {{ total.toLocaleString() }}</span></div>
          </div>
        </div>

        <div class="card"><label class="form-label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="form-input"></textarea>
        </div>

        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
        <button @click="submit" :disabled="saving || !form.supplier_id || !form.items.length" class="btn-primary w-full">
          {{ saving ? 'Saving…' : 'Create Purchase Order' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import SmartImageUploader from '@/components/SmartImageUploader.vue'

const router     = useRouter()
const products   = ref([])
const suppliers  = ref([])
const categories = ref([])
const carats     = ref([])
const saving     = ref(false)
const error      = ref('')

const uploaderRefs = ref({})

function newItem() {
  return {
    is_new: false,
    product_id: '',
    _search: '',
    _open: false,
    new_product: { name: '', category_id: '', material: '', karat: '', weight: null, images: [] },
    quantity: 1,
    unit_cost: 0,
  }
}

const form = reactive({
  supplier_id: '', status: 'received', payment_method: 'cash',
  cheque_number: '', cheque_date: '', cheque_bank_name: '',
  tax: 0, notes: '',
  items: [newItem()],
})

function addItem() { form.items.push(newItem()) }

function filteredProducts(item) {
  const q = (item._search ?? '').toLowerCase().trim()
  if (!q) return products.value.slice(0, 50)
  return products.value.filter(p =>
    p.name.toLowerCase().includes(q) ||
    p.sku.toLowerCase().includes(q) ||
    (p.barcode ?? '').toLowerCase().includes(q)
  ).slice(0, 50)
}

function selectedProductLabel(item) {
  const p = products.value.find(p => p.id === item.product_id)
  return p ? `${p.name} (${p.sku})` : ''
}

function selectProduct(item, p) {
  item.product_id    = p.id
  item._search       = ''
  item._open         = false
  item.unit_cost     = item.unit_cost || p.purchase_price || 0
}

function clearProduct(item) {
  item.product_id = ''; item._search = ''; item._open = false
  item.unit_cost = 0
}

function pickFirst(item) {
  const results = filteredProducts(item)
  if (results.length) selectProduct(item, results[0])
}

function prefillPrices(item) {
  const p = products.value.find(p => p.id === item.product_id)
  if (p) {
    if (!item.unit_cost) item.unit_cost = p.purchase_price ?? 0
  }
}

const subtotal = computed(() => form.items.reduce((a, i) => a + (i.unit_cost * i.quantity), 0))
const total    = computed(() => subtotal.value + (form.tax || 0))

async function submit() {
  saving.value = true; error.value = ''
  try {
    // Upload any pending images first
    for (const [i, item] of form.items.entries()) {
      if (item.is_new && uploaderRefs.value[i]) {
        await uploaderRefs.value[i].uploadPending()
      }
    }

    const payload = {
      ...form,
      items: form.items.map(i => {
        if (i.is_new) {
          const img = i.new_product.images?.[0] ?? null
          return {
            product_id:    null,
            new_product: {
              name:        i.new_product.name,
              category_id: i.new_product.category_id,
              material:    i.new_product.material || null,
              karat:       i.new_product.karat    || null,
              weight:      i.new_product.weight   || null,
              image_url:        img?.url        ?? null,
              image_public_id:  img?.public_id  ?? null,
            },
            quantity:  i.quantity,
            unit_cost: i.unit_cost,
          }
        }
        return {
          product_id: i.product_id || null,
          quantity:   i.quantity,
          unit_cost:  i.unit_cost,
        }
      }),
    }
    await axios.post('/api/purchases', payload)
    router.push('/purchases')
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Error saving purchase'
  } finally { saving.value = false }
}

function closeAllDropdowns() { form.items.forEach(i => { i._open = false }) }
document.addEventListener('click', closeAllDropdowns)
onBeforeUnmount(() => document.removeEventListener('click', closeAllDropdowns))

onMounted(async () => {
  const [p, s, c, k] = await Promise.all([
    axios.get('/api/products', { params: { per_page: 500 } }),
    axios.get('/api/suppliers/all'),
    axios.get('/api/categories/all'),
    axios.get('/api/carats'),
  ])
  products.value   = p.data.data
  suppliers.value  = s.data
  categories.value = c.data
  carats.value     = k.data.filter(c => c.is_active)
})
</script>
