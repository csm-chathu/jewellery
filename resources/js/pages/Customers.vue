<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <input v-model="search" type="search" placeholder="Search customers…" class="form-input w-64" @input="debouncedFetch" />
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Customer
      </button>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b"><tr>
          <th class="table-th">Name</th><th class="table-th">Email</th>
          <th class="table-th">Phone</th><th class="table-th">City</th>
          <th class="table-th">KYC</th><th class="table-th">Sales</th><th class="table-th">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
          <!-- Skeleton -->
          <template v-if="loading">
            <tr v-for="n in 7" :key="n" class="animate-pulse">
              <td class="table-td"><div class="h-3.5 w-28 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-32 bg-gray-100 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-24 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-20 bg-gray-100 rounded"></div></td>
              <td class="table-td"><div class="h-5 w-16 bg-gray-200 rounded-full"></div></td>
              <td class="table-td"><div class="h-3.5 w-6 bg-gray-100 rounded"></div></td>
              <td class="table-td">
                <div class="flex gap-2">
                  <div class="h-6 w-10 bg-gray-200 rounded-md"></div>
                  <div class="h-6 w-14 bg-gray-200 rounded-md"></div>
                </div>
              </td>
            </tr>
          </template>
          <template v-else>
          <tr v-for="c in customers.data" :key="c.id" class="hover:bg-gray-50">
            <td class="table-td font-medium">{{ c.name }}</td>
            <td class="table-td text-gray-500">{{ c.email }}</td>
            <td class="table-td">{{ c.phone }}</td>
            <td class="table-td">{{ c.city }}</td>
            <td class="table-td">
              <span v-if="c.kyc_verified" class="badge bg-green-100 text-green-700 text-xs">✓ Verified</span>
              <span v-else-if="c.id_number" class="badge bg-yellow-100 text-yellow-700 text-xs">ID on file</span>
              <span v-else class="text-gray-300 text-xs">—</span>
            </td>
            <td class="table-td">{{ c.sales_count }}</td>
            <td class="table-td"><div class="flex gap-2">
            <button @click="openEdit(c)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
              <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
            </button>
            <button @click="del(c)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
              <TrashIcon class="w-3.5 h-3.5" /> Delete
            </button>
            </div></td>
          </tr>
          <tr v-if="!customers.data?.length"><td colspan="6" class="table-td text-center text-gray-400 py-8">No customers</td></tr>
          </template>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ customers.total ?? 0 }} customers</span>
        <div class="flex gap-2">
          <button @click="page--; fetch()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetch()" :disabled="page>=customers.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
            <h3 class="text-lg font-semibold text-gray-800">{{ editing ? 'Edit' : 'Add' }} Customer</h3>
            <button @click="showModal=false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
          </div>

          <!-- Body: two columns -->
          <div class="flex-1 overflow-y-auto">
            <div class="grid grid-cols-2 divide-x divide-gray-100">

              <!-- LEFT — Main Details -->
              <div class="p-6 space-y-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Customer Details</p>

                <div>
                  <label class="form-label">Full Name *</label>
                  <input v-model="form.name" required class="form-input" placeholder="e.g. Amara Perera" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="form-label">Email</label>
                    <input v-model="form.email" type="email" class="form-input" placeholder="email@example.com" />
                  </div>
                  <div>
                    <label class="form-label">Phone</label>
                    <input v-model="form.phone" class="form-input" placeholder="+94 77 000 0000" />
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="form-label">City</label>
                    <input v-model="form.city" class="form-input" placeholder="Colombo" />
                  </div>
                  <div>
                    <label class="form-label">Country</label>
                    <input v-model="form.country" class="form-input" placeholder="Sri Lanka" />
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="form-label">Date of Birth</label>
                    <input v-model="form.date_of_birth" type="date" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Gender</label>
                    <select v-model="form.gender" class="form-input">
                      <option value="">— Select —</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="form-label">Address</label>
                  <textarea v-model="form.address" rows="2" class="form-input" placeholder="Street, city, postal code…"></textarea>
                </div>

                <div>
                  <label class="form-label">Notes</label>
                  <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Internal notes about this customer…"></textarea>
                </div>
              </div>

              <!-- RIGHT — KYC / ID Verification -->
              <div class="p-6 space-y-4 bg-gray-50/60">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">KYC &amp; ID Verification</p>

                <!-- KYC status badge -->
                <div class="flex items-center gap-3 p-3 rounded-xl border"
                  :class="form.kyc_verified ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                    :class="form.kyc_verified ? 'bg-green-500 text-white' : 'bg-yellow-400 text-white'">
                    {{ form.kyc_verified ? '✓' : '!' }}
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-semibold" :class="form.kyc_verified ? 'text-green-700' : 'text-yellow-700'">
                      {{ form.kyc_verified ? 'KYC Verified' : 'Not Yet Verified' }}
                    </p>
                    <p class="text-xs text-gray-400">Toggle below once ID has been physically sighted</p>
                  </div>
                  <label class="flex items-center cursor-pointer">
                    <div class="relative">
                      <input type="checkbox" v-model="form.kyc_verified" class="sr-only" />
                      <div class="w-10 h-5 rounded-full transition-colors"
                        :class="form.kyc_verified ? 'bg-green-500' : 'bg-gray-300'"></div>
                      <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
                        :class="form.kyc_verified ? 'translate-x-5' : 'translate-x-0'"></div>
                    </div>
                  </label>
                </div>

                <div>
                  <label class="form-label">ID Type</label>
                  <select v-model="form.id_type" class="form-input">
                    <option value="">None / Not provided</option>
                    <option value="nic">NIC (National ID)</option>
                    <option value="passport">Passport</option>
                    <option value="driving_license">Driving License</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label class="form-label">ID Number</label>
                  <input v-model="form.id_number" class="form-input" placeholder="e.g. 199012345678" />
                </div>

                <div>
                  <label class="form-label">ID Expiry Date</label>
                  <input v-model="form.id_expiry" type="date" class="form-input" />
                </div>

                <div>
                  <label class="form-label">KYC Notes</label>
                  <textarea v-model="form.kyc_notes" rows="4" class="form-input"
                    placeholder="e.g. ID sighted and photocopied on 2026-05-14, copy filed in cabinet 3…"></textarea>
                </div>
              </div>

            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between px-6 py-4 border-t bg-gray-50 rounded-b-2xl shrink-0">
            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
            <span v-else></span>
            <div class="flex gap-3">
              <button @click="showModal=false" class="btn-secondary">Cancel</button>
              <button @click="save" :disabled="saving" class="btn-primary px-6">
                {{ saving ? 'Saving…' : (editing ? 'Update Customer' : 'Add Customer') }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'

const customers = ref({ data: [] })
const loading   = ref(false)
const search    = ref(''); const page = ref(1)
const showModal = ref(false); const editing = ref(null)
const saving    = ref(false); const error   = ref('')
const form      = reactive({ name:'',email:'',phone:'',address:'',city:'',country:'',date_of_birth:'',gender:'',notes:'', id_type:'',id_number:'',id_expiry:'',kyc_verified:false,kyc_notes:'' })

let debounceTimer = null
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { page.value=1; fetch() }, 400) }

async function fetch() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/customers', { params: { page: page.value, search: search.value } })
    customers.value = data
  } finally { loading.value = false }
}

function openCreate() { editing.value=null; Object.assign(form,{name:'',email:'',phone:'',address:'',city:'',country:'',date_of_birth:'',gender:'',notes:'',id_type:'',id_number:'',id_expiry:'',kyc_verified:false,kyc_notes:''}); showModal.value=true }
function openEdit(c)  { editing.value=c; Object.assign(form,c); showModal.value=true }

async function save() {
  saving.value=true; error.value=''
  try {
    if (editing.value) await axios.put(`/api/customers/${editing.value.id}`, form)
    else               await axios.post('/api/customers', form)
    showModal.value=false; fetch()
  } catch(e) { error.value = Object.values(e.response?.data?.errors??{}).flat().join(', ')||'Error' }
  finally { saving.value=false }
}

async function del(c) {
  if (!confirm(`Delete "${c.name}"?`)) return
  await axios.delete(`/api/customers/${c.id}`); fetch()
}

onMounted(fetch)
</script>
