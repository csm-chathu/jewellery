<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Employees</h2>
        <p class="text-sm text-gray-500 mt-0.5">Manage staff records, designations and employment details</p>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Employee
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <input v-model="search" type="search" placeholder="Search name, ID, role…" class="form-input w-56" @input="debouncedFetch" />
      <select v-model="deptFilter" class="form-input w-44" @change="fetchEmployees">
        <option value="">All departments</option>
        <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
        <input type="checkbox" v-model="inactiveFilter" @change="fetchEmployees" class="rounded text-amber-500" />
        Show inactive
      </label>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
          <UserGroupIcon class="w-5 h-5 text-blue-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Total Employees</p>
          <p class="text-2xl font-bold text-gray-800">{{ employees.total ?? 0 }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
          <CheckCircleIcon class="w-5 h-5 text-green-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Active</p>
          <p class="text-2xl font-bold text-green-700">{{ activeCount }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
          <BanknotesIcon class="w-5 h-5 text-amber-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Monthly Payroll</p>
          <p class="text-lg font-bold text-amber-700">LKR {{ lkr(monthlyPayroll) }}</p>
        </div>
      </div>
      <div class="card flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
          <BuildingOfficeIcon class="w-5 h-5 text-purple-600" />
        </div>
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide">Departments</p>
          <p class="text-2xl font-bold text-purple-700">{{ departments.length }}</p>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th w-28">Emp No.</th>
              <th class="table-th">Name</th>
              <th class="table-th">Designation</th>
              <th class="table-th">Department</th>
              <th class="table-th w-28 text-center">Type</th>
              <th class="table-th w-36 text-right">Basic Salary</th>
              <th class="table-th w-28">Joined</th>
              <th class="table-th w-20 text-center">Status</th>
              <th class="table-th w-36">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-if="loading">
              <tr v-for="n in 7" :key="n" class="animate-pulse">
                <td class="table-td"><div class="h-4 w-20 bg-gray-200 rounded font-mono"></div></td>
                <td class="table-td">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gray-200 shrink-0"></div>
                    <div class="h-4 w-28 bg-gray-200 rounded"></div>
                  </div>
                </td>
                <td class="table-td"><div class="h-4 w-24 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-4 w-20 bg-gray-200 rounded"></div></td>
                <td class="table-td text-center"><div class="h-5 w-16 bg-gray-200 rounded-full mx-auto"></div></td>
                <td class="table-td text-right"><div class="h-4 w-28 bg-gray-200 rounded ml-auto"></div></td>
                <td class="table-td"><div class="h-4 w-16 bg-gray-200 rounded"></div></td>
                <td class="table-td text-center"><div class="h-5 w-12 bg-gray-200 rounded-full mx-auto"></div></td>
                <td class="table-td">
                  <div class="flex gap-2">
                    <div class="h-6 w-14 bg-gray-200 rounded-md"></div>
                    <div class="h-6 w-14 bg-gray-200 rounded-md"></div>
                  </div>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr v-for="emp in employees.data" :key="emp.id"
                class="hover:bg-gray-50 transition-colors"
                :class="!emp.is_active ? 'opacity-60' : ''">
                <td class="table-td font-mono text-xs font-semibold text-gray-600">{{ emp.employee_number }}</td>
                <td class="table-td">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 font-bold text-sm flex items-center justify-center shrink-0">
                      {{ emp.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="font-semibold text-gray-800 text-sm">{{ emp.name }}</p>
                      <p v-if="emp.contact_email" class="text-xs text-gray-400">{{ emp.contact_email }}</p>
                    </div>
                  </div>
                </td>
                <td class="table-td text-gray-600 text-sm">{{ emp.designation ?? '—' }}</td>
                <td class="table-td text-gray-500 text-sm">{{ emp.department ?? '—' }}</td>
                <td class="table-td text-center">
                  <span :class="typeClass(emp.employment_type)" class="badge text-xs">
                    {{ emp.employment_type?.replace('_', ' ') }}
                  </span>
                </td>
                <td class="table-td text-right font-semibold text-gray-800">LKR {{ lkr(emp.basic_salary) }}</td>
                <td class="table-td text-xs text-gray-500">{{ fmtDate(emp.joined_date) }}</td>
                <td class="table-td text-center">
                  <span :class="emp.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="badge text-xs">
                    {{ emp.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="table-td">
                  <div class="flex items-center gap-1.5">
                    <button @click="openEdit(emp)"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                      <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                    </button>
                    <button @click="deleteEmployee(emp)"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                      <TrashIcon class="w-3.5 h-3.5" /> Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!employees.data?.length">
                <td colspan="9" class="table-td text-center text-gray-400 py-10">No employees found</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ employees.from ?? 0 }}–{{ employees.to ?? 0 }} of {{ employees.total ?? 0 }}</span>
        <div class="flex gap-2">
          <button @click="page--; fetchEmployees()" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetchEmployees()" :disabled="page >= employees.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Add / Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
          <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit Employee' : 'Add Employee' }}</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
        </div>
        <form @submit.prevent="saveEmployee" class="overflow-y-auto flex-1 p-6 space-y-4">
          <!-- Personal -->
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b pb-1">Personal Details</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Full Name *</label>
              <input v-model="form.name" type="text" class="form-input" required />
            </div>
            <div>
              <label class="form-label">NIC / National ID</label>
              <input v-model="form.nic" type="text" class="form-input" placeholder="e.g. 991234567V" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Phone</label>
              <input v-model="form.contact_phone" type="text" class="form-input" />
            </div>
            <div>
              <label class="form-label">Email</label>
              <input v-model="form.contact_email" type="email" class="form-input" />
            </div>
          </div>
          <div>
            <label class="form-label">Address</label>
            <textarea v-model="form.address" rows="2" class="form-input"></textarea>
          </div>

          <!-- Employment -->
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b pb-1 mt-2">Employment Details</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Designation</label>
              <input v-model="form.designation" type="text" class="form-input" placeholder="e.g. Sales Associate" />
            </div>
            <div>
              <label class="form-label">Department</label>
              <input v-model="form.department" type="text" class="form-input" placeholder="e.g. Sales" list="dept-list" />
              <datalist id="dept-list">
                <option v-for="d in departments" :key="d" :value="d" />
              </datalist>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="form-label">Type *</label>
              <select v-model="form.employment_type" class="form-input" required>
                <option value="full_time">Full Time</option>
                <option value="part_time">Part Time</option>
                <option value="contract">Contract</option>
              </select>
            </div>
            <div>
              <label class="form-label">Joined Date *</label>
              <input v-model="form.joined_date" type="date" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Terminated Date</label>
              <input v-model="form.terminated_date" type="date" class="form-input" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Basic Salary (LKR) *</label>
              <input v-model.number="form.basic_salary" type="number" min="0" step="0.01" class="form-input" required />
            </div>
            <div class="flex items-end pb-0.5">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_active" type="checkbox" class="rounded text-amber-500" />
                <span class="text-sm text-gray-700">Active Employee</span>
              </label>
            </div>
          </div>

          <!-- Bank -->
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b pb-1 mt-2">Banking Details</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Bank Name</label>
              <input v-model="form.bank_name" type="text" class="form-input" placeholder="e.g. Bank of Ceylon" />
            </div>
            <div>
              <label class="form-label">Account Number</label>
              <input v-model="form.bank_account" type="text" class="form-input font-mono" />
            </div>
          </div>

          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="form.notes" rows="2" class="form-input"></textarea>
          </div>

          <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
        </form>
        <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
          <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
          <button type="button" @click="saveEmployee" :disabled="saving" class="btn-primary">
            {{ saving ? 'Saving…' : (editing ? 'Save Changes' : 'Add Employee') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { fmtDate as _fmtDate } from '../utils/date.js'
import {
  PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon, ArrowPathIcon,
  UserGroupIcon, BanknotesIcon, BuildingOfficeIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const employees    = ref({ data: [] })
const loading      = ref(false)
const search       = ref('')
const deptFilter   = ref('')
const inactiveFilter = ref(false)
const page         = ref(1)
const showModal    = ref(false)
const editing      = ref(null)
const saving       = ref(false)
const formError    = ref('')

const defaultForm = () => ({
  name: '', nic: '', designation: '', department: '',
  employment_type: 'full_time', basic_salary: 0,
  joined_date: new Date().toISOString().slice(0,10),
  terminated_date: '', contact_phone: '', contact_email: '',
  address: '', bank_name: '', bank_account: '',
  is_active: true, notes: '',
})
const form = ref(defaultForm())

const departments = computed(() => {
  const depts = employees.value.data?.map(e => e.department).filter(Boolean)
  return [...new Set(depts)].sort()
})

const activeCount = computed(() => employees.value.data?.filter(e => e.is_active).length ?? 0)
const monthlyPayroll = computed(() => employees.value.data?.filter(e => e.is_active).reduce((s, e) => s + (e.basic_salary || 0), 0) ?? 0)

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value = 1; fetchEmployees() }, 350) }

async function fetchEmployees() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/employees', {
      params: {
        page: page.value,
        search: search.value,
        department: deptFilter.value,
        ...(inactiveFilter.value ? {} : { is_active: 1 }),
      },
    })
    employees.value = data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value   = null
  form.value      = defaultForm()
  formError.value = ''
  showModal.value = true
}

function openEdit(emp) {
  editing.value   = emp
  form.value      = {
    name: emp.name, nic: emp.nic ?? '', designation: emp.designation ?? '',
    department: emp.department ?? '', employment_type: emp.employment_type,
    basic_salary: emp.basic_salary, joined_date: emp.joined_date?.slice(0,10) ?? '',
    terminated_date: emp.terminated_date?.slice(0,10) ?? '',
    contact_phone: emp.contact_phone ?? '', contact_email: emp.contact_email ?? '',
    address: emp.address ?? '', bank_name: emp.bank_name ?? '',
    bank_account: emp.bank_account ?? '', is_active: emp.is_active, notes: emp.notes ?? '',
  }
  formError.value = ''
  showModal.value = true
}

async function saveEmployee() {
  saving.value    = true
  formError.value = ''
  try {
    if (editing.value) {
      await axios.put(`/api/employees/${editing.value.id}`, form.value)
    } else {
      await axios.post('/api/employees', form.value)
    }
    showModal.value = false
    fetchEmployees()
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0] ?? 'An error occurred.'
  } finally {
    saving.value = false
  }
}

async function deleteEmployee(emp) {
  if (!confirm(`Delete employee "${emp.name}"?`)) return
  try {
    await axios.delete(`/api/employees/${emp.id}`)
    fetchEmployees()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Cannot delete this employee.')
  }
}

function typeClass(t) {
  return { full_time: 'bg-blue-100 text-blue-700', part_time: 'bg-yellow-100 text-yellow-700', contract: 'bg-purple-100 text-purple-700' }[t] ?? 'bg-gray-100 text-gray-600'
}
function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDate(d) { return _fmtDate(d) }

onMounted(fetchEmployees)
</script>
