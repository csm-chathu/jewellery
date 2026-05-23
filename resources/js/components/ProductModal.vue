<template>
  <!-- Modal backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h3 class="text-lg font-semibold">{{ product ? 'Edit Product' : 'Add Product' }}</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <form @submit.prevent="submit" class="overflow-y-auto p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="form-label">Name *</label>
            <input v-model="form.name" required class="form-input" />
          </div>
          <div>
            <label class="form-label">Barcode
              <span class="text-xs font-normal text-gray-400 ml-1">(scan or type — optional)</span>
            </label>
            <input v-model="form.barcode" class="form-input font-mono" placeholder="e.g. 8901234567890" />
          </div>
          <div class="relative" ref="categoryDropdownRef">
            <label class="form-label">Category *</label>
            <input
              v-model="categorySearch"
              type="text"
              class="form-input"
              :placeholder="selectedCategoryName || 'Search category…'"
              @focus="categoryOpen = true"
              @input="categoryOpen = true"
              autocomplete="off"
            />
            <div
              v-if="categoryOpen && filteredCategories.length"
              class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"
            >
              <button
                v-for="c in filteredCategories"
                :key="c.id"
                type="button"
                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100"
                :class="form.category_id === c.id ? 'bg-gold-50 font-medium text-gold-700' : ''"
                @mousedown.prevent="selectCategory(c)"
              >
                {{ c.name }}
              </button>
            </div>
            <input type="hidden" :value="form.category_id" required />
          </div>
          <div>
            <label class="form-label">Supplier</label>
            <select v-model="form.supplier_id" class="form-input">
              <option value="">— None —</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Material</label>
            <select v-model="form.material" class="form-input">
              <option value="">— Select —</option>
              <option v-for="m in materials" :key="m" :value="m">{{ m }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Karat</label>
            <select v-model="form.karat" class="form-input" @change="tryAutoCalculate">
              <option value="">— Select —</option>
              <option v-for="c in karats" :key="c.id" :value="c.label">{{ c.label }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Weight (g)</label>
            <input v-model="form.weight" type="number" step="0.001" min="0" class="form-input" @input="tryAutoCalculate" />
          </div>
          <div>
            <label class="form-label">Gemstone</label>
            <input v-model="form.gemstone" class="form-input" placeholder="e.g. Diamond" />
          </div>
          <div>
            <label class="form-label">Gemstone Weight (ct)</label>
            <input v-model="form.gemstone_weight" type="number" step="0.001" min="0" class="form-input" />
          </div>
          <div>
            <label class="form-label">Gemstone Value (LKR)</label>
            <input v-model="form.gemstone_value" type="number" step="0.01" min="0" class="form-input" />
          </div>
          <div>
            <label class="form-label">Gemstone Quality</label>
            <input v-model="form.gemstone_quality" class="form-input" placeholder="e.g. VS1, SI2" />
          </div>
          <div>
            <label class="form-label">Color</label>
            <input v-model="form.color" class="form-input" />
          </div>
          <div>
            <label class="form-label">Size</label>
            <input v-model="form.size" class="form-input" />
          </div>

          <!-- Making charges -->
          <div>
            <label class="form-label">Making Charge Type</label>
            <select v-model="form.making_charge_type" class="form-input">
              <option value="per_gram">Per Gram</option>
              <option value="per_piece">Per Piece</option>
              <option value="percentage">Percentage (%)</option>
            </select>
          </div>
          <div>
            <label class="form-label">Making Charge (LKR / %)</label>
            <input v-model="form.making_charge" type="number" step="0.01" min="0" class="form-input" />
          </div>
          <div>
            <label class="form-label">Wastage (%)</label>
            <input v-model="form.wastage_percent" type="number" step="0.01" min="0" max="100" class="form-input" placeholder="e.g. 2.5" />
          </div>
          <div>
            <label class="form-label">Purchase Price (LKR) *</label>
            <input v-model="form.purchase_price" type="number" step="0.01" min="0" required class="form-input" />
          </div>
          <div>
            <label class="form-label">Stock Quantity *</label>
            <input v-model="form.stock_quantity" type="number" min="0" required class="form-input" />
          </div>
          <div>
            <label class="form-label">Min Stock Level</label>
            <input v-model="form.min_stock_level" type="number" min="0" class="form-input" />
          </div>
          <div class="col-span-2">
            <label class="form-label">Description</label>
            <textarea v-model="form.description" rows="2" class="form-input"></textarea>
          </div>
          <div class="col-span-2">
            <SmartImageUploader
              ref="productImageUploader"
              v-model="productImages"
              label="Product Image"
              :multiple="false"
              :max-items="1"
              folder="jewellery/products"
              :tags="['product']"
            />
          </div>
          <div class="col-span-2 flex items-center gap-2">
            <input id="active" type="checkbox" v-model="form.is_active" class="rounded text-gold-600" />
            <label for="active" class="text-sm text-gray-700">Active</label>
          </div>
        </div>

        <!-- Gold rate auto-price panel -->
        <div v-if="goldCalc" class="bg-gold-50 border border-gold-200 rounded-xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-gold-800">
              💰 Gold Rate: LKR {{ lkr(goldCalc.rate_per_gram) }}/g ({{ form.karat?.toUpperCase() }}) · {{ goldCalc.date }}
            </p>
            <span class="badge bg-gold-100 text-gold-700 text-xs uppercase">{{ form.karat }}</span>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-white rounded-lg p-3 border border-gold-200">
              <p class="text-xs text-gray-500 mb-1">Calculated ({{ form.weight }}g × {{ form.karat }})</p>
              <p class="font-bold text-gold-700 text-base">LKR {{ lkr(goldCalc.price) }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 border border-gold-200 space-y-1.5">
              <p class="text-xs text-gray-500">Markup %</p>
              <div class="flex items-center gap-2">
                <input v-model.number="markup" type="number" min="0" max="500" step="1"
                  class="form-input py-1 text-sm w-20" />
                <span class="text-xs text-gray-400">%</span>
              </div>
            </div>
          </div>
          <button type="button" @click="applyCalculatedPrices"
            class="w-full inline-flex justify-center items-center gap-2 px-4 py-2 rounded-lg bg-gold-600 text-white text-sm font-medium hover:bg-gold-700 transition-colors">
            Apply Calculated Prices
          </button>
        </div>
        <div v-else-if="form.material === 'Gold' && form.karat && form.weight"
          class="text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
          ⚠ No gold rate set for today. <router-link to="/gold-rates" class="underline font-medium">Set it here →</router-link>
        </div>

        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
      </form>

      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
        <button @click="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import SmartImageUploader from '@/components/SmartImageUploader.vue'

const props = defineProps({ product: Object, categories: Array, suppliers: Array })
const emit  = defineEmits(['close', 'saved'])

const materials = ['Gold', 'Silver', 'Platinum', 'White Gold', 'Rose Gold', 'Titanium']
const karats    = ref([])  // loaded from carat master

const form = reactive({
  name: '', description: '', category_id: '', supplier_id: '',
  material: '', weight: '', karat: '', making_charge_type: 'per_gram', making_charge: 0, wastage_percent: 0,
  size: '', color: '', gemstone: '', gemstone_weight: '', gemstone_value: 0, gemstone_quality: '',
  purchase_price: '', stock_quantity: 0, min_stock_level: 5,
  is_active: true, barcode: '',
})

const saving = ref(false)
const error  = ref('')
const productImages = ref([])
const productImageUploader = ref(null)

// Searchable category
const categorySearch = ref('')
const categoryOpen   = ref(false)
const categoryDropdownRef = ref(null)

const filteredCategories = computed(() => {
  const q = categorySearch.value.trim().toLowerCase()
  if (!q) return props.categories ?? []
  return (props.categories ?? []).filter(c => c.name.toLowerCase().includes(q))
})

const selectedCategoryName = computed(() =>
  (props.categories ?? []).find(c => c.id === form.category_id)?.name ?? ''
)

function selectCategory(c) {
  form.category_id = c.id
  categorySearch.value = ''
  categoryOpen.value = false
}

function onClickOutside(e) {
  if (categoryDropdownRef.value && !categoryDropdownRef.value.contains(e.target)) {
    categoryOpen.value = false
  }
}

onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))

// --- Gold rate auto-calculation ---
const goldRateMap = ref({})   // { '24k': {rate_per_gram, carat, date}, ... }
const goldCalc    = ref(null) // calculated result { price, rate_per_gram, date }
const markup      = ref(30)   // default 30% markup for selling price

const KARAT_PURITY = { '9k': 9/24, '14k': 14/24, '18k': 18/24, '22k': 22/24, '24k': 1 }

function rateForKarat(karatStr) {
  const key  = karatStr?.toLowerCase()
  if (goldRateMap.value[key]) return goldRateMap.value[key].rate_per_gram
  const r24k = goldRateMap.value['24k']?.rate_per_gram
  return r24k ? r24k * (KARAT_PURITY[key] ?? 1) : null
}

function lkr(val) {
  return Number(val).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function loadGoldRate() {
  try {
    const [ratesRes, caratsRes] = await Promise.all([
      axios.get('/api/gold-rates/today'),
      axios.get('/api/carats'),
    ])
    const rates = Array.isArray(ratesRes.data) ? ratesRes.data : []
    goldRateMap.value = Object.fromEntries(rates.map(r => [r.carat?.label?.toLowerCase(), r]).filter(([k]) => k))
    karats.value = (Array.isArray(caratsRes.data) ? caratsRes.data : [])
      .filter(c => c.is_active)
      .sort((a, b) => b.purity - a.purity)
    tryAutoCalculate()
  } catch {}
}

function tryAutoCalculate() {
  const isGoldMaterial = ['Gold', 'White Gold', 'Rose Gold'].includes(form.material)
  if (!isGoldMaterial || !form.karat || !form.weight) { goldCalc.value = null; return }
  const rate = rateForKarat(form.karat)
  if (!rate) { goldCalc.value = null; return }
  const price   = rate * parseFloat(form.weight)
  const refRate = goldRateMap.value[form.karat?.toLowerCase()] ?? goldRateMap.value['24k']
  goldCalc.value = {
    price,
    rate_per_gram: refRate?.rate_per_gram ?? rate,
    date: refRate?.date ?? null,
  }
}

function applyCalculatedPrices() {
  if (!goldCalc.value) return
  form.purchase_price = Math.round(goldCalc.value.price * 100) / 100
}

// Watch material change to re-evaluate
watch(() => form.material, tryAutoCalculate)
watch(markup, tryAutoCalculate)
// ----------------------------------

onMounted(() => {
  if (props.product) {
    Object.assign(form, props.product)
    productImages.value = props.product.image
      ? [{ url: props.product.image, public_id: props.product.image_public_id || null }]
      : []
  } else {
    productImages.value = []
  }
  loadGoldRate()
  document.addEventListener('mousedown', onClickOutside)
})

async function submit() {
  saving.value = true
  error.value  = ''
  try {
    if (productImageUploader.value) {
      await productImageUploader.value.uploadPending()
    }

    const imageMeta = productImages.value[0] || null
    form.image_url = imageMeta?.url || null
    form.image_public_id = imageMeta?.public_id || null

    let savedProduct
    if (props.product) {
      const { data } = await axios.put(`/api/products/${props.product.id}`, form)
      savedProduct = data
    } else {
      const { data } = await axios.post('/api/products', form)
      savedProduct = data
    }
    emit('saved', { product: savedProduct, isNew: !props.product })
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(', ') : (e.response?.data?.message ?? 'Error saving')
  } finally {
    saving.value = false
  }
}
</script>
