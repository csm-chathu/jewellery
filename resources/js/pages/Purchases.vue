<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex gap-3">
        <input v-model="search" type="search" placeholder="PO number…" class="form-input w-44" @input="debouncedFetch" />
        <select v-model="supplierFilter" class="form-input w-44" @change="fetch">
          <option value="">All suppliers</option>
          <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <router-link to="/purchases/new" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Purchase
      </router-link>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr>
          <th class="table-th">PO Number</th><th class="table-th">Supplier</th>
          <th class="table-th">Date</th><th class="table-th">Total</th>
          <th class="table-th">Payment</th><th class="table-th">Status</th><th class="table-th">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="p in purchases.data" :key="p.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs font-medium">{{ p.purchase_number }}</td>
            <td class="table-td">{{ p.supplier?.name }}</td>
            <td class="table-td text-gray-500 text-xs">{{ new Date(p.purchased_at).toLocaleDateString() }}</td>
            <td class="table-td font-semibold">LKR {{ Number(p.total).toLocaleString() }}</td>
            <td class="table-td text-xs text-gray-600 uppercase">{{ p.payment_method || 'cash' }}</td>
            <td class="table-td">
              <span :class="statusClass(p.status)" class="badge">{{ p.status }}</span>
            </td>
            <td class="table-td">
              <button @click="del(p)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                <TrashIcon class="w-3.5 h-3.5" /> Delete
              </button>
            </td>
          </tr>
          <tr v-if="!purchases.data?.length"><td colspan="7" class="table-td text-center text-gray-400 py-8">No purchases</td></tr>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ purchases.total ?? 0 }} records</span>
        <div class="flex gap-2">
          <button @click="page--; fetch()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetch()" :disabled="page>=purchases.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const purchases      = ref({ data: [] })
const suppliers      = ref([])
const search         = ref(''); const page = ref(1)
const supplierFilter = ref('')

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value=1; fetch() }, 400) }

async function fetch() {
  const { data } = await axios.get('/api/purchases', { params: { page: page.value, search: search.value, supplier_id: supplierFilter.value } })
  purchases.value = data
}

function statusClass(s) {
  return { received:'bg-green-100 text-green-700', pending:'bg-yellow-100 text-yellow-700', partial:'bg-blue-100 text-blue-700', cancelled:'bg-red-100 text-red-700' }[s] ?? 'bg-gray-100 text-gray-700'
}

async function del(p) {
  if (!confirm(`Delete purchase order ${p.purchase_number}?`)) return
  await axios.delete(`/api/purchases/${p.id}`); fetch()
}

onMounted(async () => {
  const { data } = await axios.get('/api/suppliers/all')
  suppliers.value = data
  fetch()
})
</script>
