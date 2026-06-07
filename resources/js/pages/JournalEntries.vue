<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Journal Entries</h2>
        <p class="text-sm text-gray-500 mt-0.5">Double-entry bookkeeping — every debit has a matching credit</p>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Entry
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <input v-model="search"   type="search" placeholder="Search entry # or description…" class="form-input w-60" @input="debouncedFetch" />
      <input v-model="dateFrom" type="date"   class="form-input w-36" @change="fetchEntries" title="From" />
      <span class="text-gray-400 text-xs">to</span>
      <input v-model="dateTo"   type="date"   class="form-input w-36" @change="fetchEntries" title="To" />
      <select v-model="statusFilter" class="form-input w-28" @change="fetchEntries">
        <option value="">All</option>
        <option value="posted">Posted</option>
        <option value="draft">Draft</option>
      </select>
    </div>

    <!-- Entries list -->
    <div class="card p-0 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="table-th w-36">Entry #</th>
              <th class="table-th w-28">Date</th>
              <th class="table-th">Description</th>
              <th class="table-th w-16 text-center">Lines</th>
              <th class="table-th w-36 text-right">Total (Dr)</th>
              <th class="table-th w-24 text-center">Status</th>
              <th class="table-th w-24 text-center">By</th>
              <th class="table-th w-20">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-if="loading">
              <tr v-for="n in 7" :key="n" class="animate-pulse">
                <td class="table-td bg-gray-50"><div class="h-4 w-24 bg-gray-200 rounded font-mono"></div></td>
                <td class="table-td"><div class="h-4 w-20 bg-gray-200 rounded"></div></td>
                <td class="table-td"><div class="h-4 w-48 bg-gray-200 rounded"></div></td>
                <td class="table-td text-center"><div class="h-5 w-8 bg-gray-200 rounded-full mx-auto"></div></td>
                <td class="table-td text-right"><div class="h-4 w-28 bg-gray-200 rounded ml-auto"></div></td>
                <td class="table-td text-center"><div class="h-5 w-14 bg-gray-200 rounded-full mx-auto"></div></td>
                <td class="table-td text-center"><div class="h-4 w-16 bg-gray-200 rounded mx-auto"></div></td>
                <td class="table-td"><div class="h-6 w-8 bg-gray-200 rounded-md"></div></td>
              </tr>
            </template>
            <template v-else>
              <tr v-for="entry in entries.data" :key="entry.id"
                class="hover:bg-gray-50 cursor-pointer"
                @click="viewEntry(entry)">
                <td class="table-td font-mono text-xs font-semibold text-gray-700 bg-gray-50">{{ entry.entry_number }}</td>
                <td class="table-td text-sm text-gray-600">{{ fmtDate(entry.entry_date) }}</td>
                <td class="table-td font-medium text-gray-800 max-w-xs truncate">{{ entry.description }}</td>
                <td class="table-td text-center">
                  <span class="badge bg-gray-100 text-gray-600">{{ entry.lines_count }}</span>
                </td>
                <td class="table-td text-right font-semibold text-gray-800">
                  LKR {{ lkr(entry.lines_sum_debit) }}
                </td>
                <td class="table-td text-center">
                  <span :class="entry.status === 'posted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="badge text-xs">
                    {{ entry.status }}
                  </span>
                </td>
                <td class="table-td text-center text-xs text-gray-500">{{ entry.created_by?.name ?? '—' }}</td>
                <td class="table-td" @click.stop>
                  <button v-if="entry.status === 'draft' || !entry.reference_type"
                    @click="deleteEntry(entry)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!entries.data?.length">
                <td colspan="8" class="table-td text-center text-gray-400 py-10">No journal entries found</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ entries.total ?? 0 }} entries</span>
        <div class="flex gap-2">
          <button @click="page--; fetchEntries()" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; fetchEntries()" :disabled="page >= entries.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- ── View Entry Detail Modal ── -->
    <div v-if="viewModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">
        <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
          <div>
            <p class="font-mono font-semibold text-gray-700">{{ viewDetail?.entry_number }}</p>
            <p class="text-sm text-gray-500">{{ fmtDate(viewDetail?.entry_date) }} · {{ viewDetail?.description }}</p>
          </div>
          <button @click="viewModal = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
        </div>
        <div class="overflow-y-auto flex-1 p-6">
          <table class="w-full text-sm">
            <thead><tr class="bg-gray-50 border-b">
              <th class="table-th">Account</th>
              <th class="table-th w-32 text-right">Debit (LKR)</th>
              <th class="table-th w-32 text-right">Credit (LKR)</th>
              <th class="table-th">Note</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="line in viewDetail?.lines" :key="line.id">
                <td class="table-td">
                  <span class="font-mono text-xs text-gray-500">{{ line.account?.code }}</span>
                  <span class="ml-2 font-medium">{{ line.account?.name }}</span>
                </td>
                <td class="table-td text-right font-mono" :class="line.debit > 0 ? 'text-gray-800' : 'text-gray-300'">
                  {{ line.debit > 0 ? lkr(line.debit) : '—' }}
                </td>
                <td class="table-td text-right font-mono" :class="line.credit > 0 ? 'text-gray-800' : 'text-gray-300'">
                  {{ line.credit > 0 ? lkr(line.credit) : '—' }}
                </td>
                <td class="table-td text-xs text-gray-500">{{ line.description ?? '' }}</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-300 font-semibold">
              <tr class="bg-gray-50">
                <td class="table-td">Totals</td>
                <td class="table-td text-right font-mono">{{ lkr(viewDetail?.lines?.reduce((s,l) => s+l.debit, 0)) }}</td>
                <td class="table-td text-right font-mono">{{ lkr(viewDetail?.lines?.reduce((s,l) => s+l.credit, 0)) }}</td>
                <td class="table-td"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Create Entry Panel ── -->
    <div v-if="createModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
          <h3 class="font-semibold text-gray-800">New Journal Entry</h3>
          <button @click="createModal = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
        </div>
        <div class="overflow-y-auto flex-1 p-6 space-y-5">
          <!-- Header fields -->
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="form-label">Date *</label>
              <input v-model="newEntry.entry_date" type="date" class="form-input" required />
            </div>
            <div class="col-span-2">
              <label class="form-label">Description *</label>
              <input v-model="newEntry.description" type="text" class="form-input" placeholder="e.g. Sale invoice INV-001" required />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Reference Type</label>
              <select v-model="newEntry.reference_type" class="form-input">
                <option value="">— None —</option>
                <option value="Manual">Manual</option>
                <option value="Sale">Sale</option>
                <option value="Purchase">Purchase</option>
                <option value="GoldBuyback">Gold Buy-Back</option>
                <option value="Expense">Expense</option>
              </select>
            </div>
            <div>
              <label class="form-label">Status</label>
              <select v-model="newEntry.status" class="form-input">
                <option value="posted">Posted</option>
                <option value="draft">Draft</option>
              </select>
            </div>
          </div>

          <!-- Lines -->
          <div class="border rounded-xl overflow-hidden">
            <div class="bg-gray-50 px-4 py-2.5 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Entry Lines</span>
              <button @click="addLine" class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-700 hover:bg-amber-200 rounded-md text-xs font-medium">
                <PlusIcon class="w-3.5 h-3.5" /> Add Line
              </button>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b text-xs">
                <tr>
                  <th class="table-th">Account</th>
                  <th class="table-th w-32">Debit (LKR)</th>
                  <th class="table-th w-32">Credit (LKR)</th>
                  <th class="table-th">Note</th>
                  <th class="table-th w-10"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(line, i) in newEntry.lines" :key="i">
                  <td class="table-td">
                    <select v-model="line.account_id" class="form-input text-sm">
                      <option value="">— Select —</option>
                      <optgroup v-for="grp in accountGroups" :key="grp.type" :label="grp.label">
                        <option v-for="a in grp.items" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
                      </optgroup>
                    </select>
                  </td>
                  <td class="table-td">
                    <input v-model.number="line.debit" type="number" min="0" step="0.01" class="form-input text-right font-mono"
                      @input="line.credit = line.debit > 0 ? 0 : line.credit" />
                  </td>
                  <td class="table-td">
                    <input v-model.number="line.credit" type="number" min="0" step="0.01" class="form-input text-right font-mono"
                      @input="line.debit = line.credit > 0 ? 0 : line.debit" />
                  </td>
                  <td class="table-td">
                    <input v-model="line.description" type="text" class="form-input text-sm" placeholder="Optional" />
                  </td>
                  <td class="table-td text-center">
                    <button @click="removeLine(i)" class="text-red-400 hover:text-red-600 p-1">
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot class="border-t-2">
                <tr :class="isBalanced ? 'bg-green-50' : 'bg-red-50'">
                  <td class="table-td font-semibold text-xs uppercase tracking-wide">
                    <span v-if="isBalanced" class="text-green-700">✓ Balanced</span>
                    <span v-else class="text-red-600">⚠ Not balanced</span>
                  </td>
                  <td class="table-td text-right font-semibold font-mono">{{ lkr(totalDebit) }}</td>
                  <td class="table-td text-right font-semibold font-mono">{{ lkr(totalCredit) }}</td>
                  <td colspan="2" class="table-td text-xs text-gray-500">
                    Difference: {{ lkr(Math.abs(totalDebit - totalCredit)) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <p v-if="createError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ createError }}</p>
        </div>
        <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
          <button @click="createModal = false" class="btn-secondary">Cancel</button>
          <button @click="submitEntry" :disabled="!isBalanced || saving" class="btn-primary disabled:opacity-50">
            {{ saving ? 'Posting…' : 'Post Entry' }}
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
import { PlusIcon, TrashIcon, XMarkIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const entries      = ref({ data: [] })
const loading      = ref(false)
const search       = ref('')
const dateFrom     = ref('')
const dateTo       = ref('')
const statusFilter = ref('')
const page         = ref(1)
const viewModal    = ref(false)
const viewDetail   = ref(null)
const createModal  = ref(false)
const saving       = ref(false)
const createError  = ref('')
const allAccounts  = ref([])

const defaultEntry = () => ({
  entry_date:     new Date().toISOString().slice(0, 10),
  description:    '',
  reference_type: '',
  status:         'posted',
  lines: [
    { account_id: '', debit: 0, credit: 0, description: '' },
    { account_id: '', debit: 0, credit: 0, description: '' },
  ],
})
const newEntry = ref(defaultEntry())

const typeOrder = ['asset', 'liability', 'equity', 'revenue', 'expense']
const typeLabel = { asset: 'Assets', liability: 'Liabilities', equity: 'Equity', revenue: 'Revenue', expense: 'Expenses' }

const accountGroups = computed(() =>
  typeOrder.map(type => ({
    type,
    label: typeLabel[type],
    items: allAccounts.value.filter(a => a.type === type),
  })).filter(g => g.items.length)
)

const totalDebit  = computed(() => newEntry.value.lines.reduce((s, l) => s + (Number(l.debit) || 0), 0))
const totalCredit = computed(() => newEntry.value.lines.reduce((s, l) => s + (Number(l.credit) || 0), 0))
const isBalanced  = computed(() => newEntry.value.lines.length >= 2 && Math.abs(totalDebit.value - totalCredit.value) < 0.01)

let timer = null
function debouncedFetch() { clearTimeout(timer); timer = setTimeout(() => { page.value = 1; fetchEntries() }, 350) }

async function fetchEntries() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/journal-entries', {
      params: { page: page.value, search: search.value, from: dateFrom.value, to: dateTo.value, status: statusFilter.value },
    })
    entries.value = data
  } finally {
    loading.value = false
  }
}

async function viewEntry(entry) {
  const { data } = await axios.get(`/api/journal-entries/${entry.id}`)
  viewDetail.value = data
  viewModal.value  = true
}

function openCreate() {
  newEntry.value = defaultEntry()
  createError.value = ''
  createModal.value = true
}

function addLine() {
  newEntry.value.lines.push({ account_id: '', debit: 0, credit: 0, description: '' })
}

function removeLine(i) {
  if (newEntry.value.lines.length <= 2) return
  newEntry.value.lines.splice(i, 1)
}

async function submitEntry() {
  saving.value      = true
  createError.value = ''
  try {
    await axios.post('/api/journal-entries', newEntry.value)
    createModal.value = false
    fetchEntries()
  } catch (e) {
    createError.value = e.response?.data?.message ?? 'Failed to post entry.'
  } finally {
    saving.value = false
  }
}

async function deleteEntry(entry) {
  if (!confirm(`Delete entry ${entry.entry_number}?`)) return
  try {
    await axios.delete(`/api/journal-entries/${entry.id}`)
    fetchEntries()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Cannot delete this entry.')
  }
}

function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDate(d) { return _fmtDate(d) }

onMounted(async () => {
  const { data } = await axios.get('/api/accounts/all')
  allAccounts.value = data
  fetchEntries()
})
</script>
