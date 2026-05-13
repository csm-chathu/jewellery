<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
      <router-link to="/purchases" class="text-gray-500 hover:text-gray-700 text-sm">← Back</router-link>
      <h2 class="text-xl font-semibold text-gray-800">New Purchase Order</h2>
    </div>

    <div class="grid grid-cols-3 gap-6">
      <div class="col-span-2 space-y-4">
        <div class="card">
          <h3 class="font-semibold mb-3 text-gray-700">Items</h3>
          <div v-for="(item, i) in form.items" :key="i" class="flex items-center gap-2 mb-2">
            <select v-model="item.product_id" class="form-input flex-1">
              <option value="">Select product…</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (SKU: {{ p.sku }})</option>
            </select>
            <input v-model.number="item.quantity"  type="number" min="1" placeholder="Qty"  class="form-input w-20" @change="recalc" />
            <input v-model.number="item.unit_cost" type="number" min="0" placeholder="Cost" class="form-input w-28" @change="recalc" />
            <button @click="form.items.splice(i,1); recalc()" class="text-red-400 hover:text-red-600 text-lg">✕</button>
          </div>
          <button @click="form.items.push({product_id:'',quantity:1,unit_cost:0})" class="btn-secondary text-sm mt-2">+ Add Item</button>
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
            <input v-model.number="form.tax" type="number" min="0" class="form-input" @input="recalc" />
          </div>
          <div class="border-t pt-3 space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>LKR {{ subtotal.toFixed(2) }}</span></div>
            <div class="flex justify-between font-bold text-gray-800 text-base"><span>Total</span><span>LKR {{ total.toFixed(2) }}</span></div>
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router   = useRouter()
const products  = ref([])
const suppliers = ref([])
const saving    = ref(false)
const error     = ref('')

const form = reactive({
  supplier_id:'', status:'received', payment_method:'cash',
  cheque_number:'', cheque_date:'', cheque_bank_name:'',
  tax:0, notes:'', items:[{product_id:'',quantity:1,unit_cost:0}],
})

const subtotal = computed(() => form.items.reduce((a,i) => a + (i.unit_cost * i.quantity), 0))
const total    = computed(() => subtotal.value + (form.tax || 0))
function recalc() {}

async function submit() {
  saving.value = true; error.value = ''
  try {
    await axios.post('/api/purchases', form)
    router.push('/purchases')
  } catch(e) {
    error.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors??{}).flat().join(', ') ?? 'Error'
  } finally { saving.value = false }
}

onMounted(async () => {
  const [p, s] = await Promise.all([axios.get('/api/products',{params:{per_page:200}}), axios.get('/api/suppliers/all')])
  products.value  = p.data.data
  suppliers.value = s.data
})
</script>
