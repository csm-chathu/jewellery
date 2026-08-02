<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Repair Article List</h1>
        <p class="text-sm text-gray-500 mt-1">Track articles received for repair</p>
      </div>
      <button @click="openModal()" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> Add Repair
      </button>
    </div>

    <!-- Search -->
    <div class="card p-4">
      <input v-model="search" @input="fetchDebounced" type="text" placeholder="Search by article, customer, bill no, phone…"
        class="form-input w-full max-w-md" />
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-900 text-white text-xs uppercase">
            <th class="px-3 py-3 text-left">Date</th>
            <th class="px-3 py-3 text-left">Bill No</th>
            <th class="px-3 py-3 text-left">Give Date</th>
            <th class="px-3 py-3 text-left">Article</th>
            <th class="px-3 py-3 text-left">Damage</th>
            <th class="px-3 py-3 text-left">Customer Name</th>
            <th class="px-3 py-3 text-left">Telephone No</th>
            <th class="px-3 py-3 text-right">Weight</th>
            <th class="px-3 py-3 text-right">Add Weight</th>
            <th class="px-3 py-3 text-right">Advance</th>
            <th class="px-3 py-3 text-center">Done</th>
            <th class="px-3 py-3 text-center">Give</th>
            <th class="px-3 py-3 text-right">Price</th>
            <th class="px-3 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="14" class="text-center py-8 text-gray-400">Loading…</td>
          </tr>
          <tr v-else-if="!rows.length">
            <td colspan="14" class="text-center py-8 text-gray-400">No repair articles found.</td>
          </tr>
          <tr v-for="row in rows" :key="row.id"
            class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
            :class="{ 'bg-green-50': row.given }">
            <td class="px-3 py-2 whitespace-nowrap">{{ fmtDate(row.received_date) }}</td>
            <td class="px-3 py-2">{{ row.bill_number ?? '—' }}</td>
            <td class="px-3 py-2 whitespace-nowrap" :class="isOverdue(row) ? 'text-red-600 font-semibold' : ''">
              {{ fmtDate(row.give_date) }}
            </td>
            <td class="px-3 py-2 font-medium">{{ row.article }}</td>
            <td class="px-3 py-2 text-gray-600 max-w-[200px] truncate" :title="row.damage">{{ row.damage ?? '—' }}</td>
            <td class="px-3 py-2">{{ row.customer_name ?? '—' }}</td>
            <td class="px-3 py-2">{{ row.telephone ?? '—' }}</td>
            <td class="px-3 py-2 text-right">{{ row.weight ?? '—' }}</td>
            <td class="px-3 py-2 text-right">{{ row.add_weight ?? '—' }}</td>
            <td class="px-3 py-2 text-right">{{ row.advance ? Number(row.advance).toLocaleString() : '—' }}</td>
            <td class="px-3 py-2 text-center">
              <span v-if="row.done" class="text-green-600 font-bold text-xs bg-green-100 px-2 py-0.5 rounded-full">YES</span>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>
            <td class="px-3 py-2 text-center">
              <span v-if="row.given" class="text-blue-600 font-bold text-xs bg-blue-100 px-2 py-0.5 rounded-full">YES</span>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>
            <td class="px-3 py-2 text-right font-semibold">{{ Number(row.price).toLocaleString() }}</td>
            <td class="px-3 py-2 text-center">
              <div class="flex items-center justify-center gap-1">
                <button @click="openModal(row)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit">
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button @click="printAdvanceInvoice(row)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded" title="Print Advance Invoice">
                  <PrinterIcon class="w-4 h-4" />
                </button>
                <button v-if="row.given" @click="printCompletionInvoice(row)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Print Completion Invoice">
                  <PrinterIcon class="w-4 h-4" />
                </button>
                <button @click="confirmDelete(row)" class="p-1.5 text-red-500 hover:bg-red-50 rounded" title="Delete">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
        <p class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
        <div class="flex gap-2">
          <button @click="goPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
            class="px-3 py-1 text-sm border rounded disabled:opacity-40">Prev</button>
          <button @click="goPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 text-sm border rounded disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Add / Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-bold">{{ editing ? 'Edit Repair Article' : 'Add Repair Article' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="save" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Received Date *</label>
                <input v-model="form.received_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Bill No</label>
                <input v-model="form.bill_number" type="text" class="form-input" placeholder="e.g. 1409" />
              </div>
              <div>
                <label class="form-label">Give Date</label>
                <input v-model="form.give_date" type="date" class="form-input" />
              </div>
              <div>
                <label class="form-label">Article *</label>
                <input v-model="form.article" type="text" required class="form-input" placeholder="e.g. Chain, Ring…" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Damage / Work Description</label>
                <textarea v-model="form.damage" class="form-input" rows="2" placeholder="Describe the damage or work needed…" />
              </div>
              <div>
                <label class="form-label">Customer Name</label>
                <input v-model="form.customer_name" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Telephone No</label>
                <input v-model="form.telephone" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Weight (g)</label>
                <input v-model="form.weight" type="number" step="0.001" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Add Weight (g)</label>
                <input v-model="form.add_weight" type="number" step="0.001" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Advance (LKR)</label>
                <input v-model="form.advance" type="number" step="0.01" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Price (LKR)</label>
                <input v-model="form.price" type="number" step="0.01" min="0" class="form-input" />
              </div>
              <div class="flex items-center gap-6 col-span-2 pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input v-model="form.done" type="checkbox" class="w-4 h-4 accent-green-600" />
                  <span class="text-sm font-medium">Done</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input v-model="form.given" type="checkbox" class="w-4 h-4 accent-blue-600" />
                  <span class="text-sm font-medium">Given back to customer</span>
                </label>
              </div>
              <div class="col-span-2">
                <label class="form-label">Notes</label>
                <textarea v-model="form.notes" class="form-input" rows="2" />
              </div>
            </div>

            <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Saving…' : (editing ? 'Update' : 'Add') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirm -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 space-y-4">
          <h3 class="text-lg font-bold text-gray-900">Delete Repair Article?</h3>
          <p class="text-sm text-gray-600">
            Are you sure you want to delete <strong>{{ deleteTarget.article }}</strong>? This cannot be undone.
          </p>
          <div class="flex justify-end gap-3">
            <button @click="deleteTarget = null" class="btn-secondary">Cancel</button>
            <button @click="doDelete" :disabled="deleting" class="btn-danger">
              {{ deleting ? 'Deleting…' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

function fmtDate(val) {
  if (!val) return '—'
  return val.slice(0, 10).replace(/-/g, '.')
}
import axios from 'axios'
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon, PrinterIcon } from '@heroicons/vue/24/outline'

const shopSettings = ref({})

const rows       = ref([])
const loading    = ref(false)
const search     = ref('')
const pagination = ref({ current_page: 1, last_page: 1 })

const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const formError  = ref('')

const deleteTarget = ref(null)
const deleting     = ref(false)

const emptyForm = () => ({
  bill_number: '', received_date: new Date().toISOString().slice(0, 10),
  give_date: '', article: '', damage: '', customer_name: '', telephone: '',
  weight: '', add_weight: '', advance: '', price: '', done: false, given: false, notes: '',
})

const form = reactive(emptyForm())

let debounceTimer = null
function fetchDebounced() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetch(1), 300)
}

async function fetch(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/repair-articles', { params: { search: search.value, page } })
    rows.value       = data.data
    pagination.value = { current_page: data.current_page, last_page: data.last_page }
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  if (p >= 1 && p <= pagination.value.last_page) fetch(p)
}

function isOverdue(row) {
  if (!row.give_date || row.given) return false
  return new Date(row.give_date) < new Date()
}

function openModal(row = null) {
  formError.value = ''
  if (row) {
    editing.value = row.id
    Object.assign(form, {
      bill_number: row.bill_number ?? '', received_date: row.received_date?.slice(0, 10) ?? '',
      give_date: row.give_date?.slice(0, 10) ?? '', article: row.article, damage: row.damage ?? '',
      customer_name: row.customer_name ?? '', telephone: row.telephone ?? '',
      weight: row.weight ?? '', add_weight: row.add_weight ?? '',
      advance: row.advance ?? '', price: row.price ?? '',
      done: row.done, given: row.given, notes: row.notes ?? '',
    })
  } else {
    editing.value = null
    Object.assign(form, emptyForm())
  }
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function save() {
  saving.value = true
  formError.value = ''
  try {
    const payload = { ...form }
    if (editing.value) {
      await axios.put(`/api/repair-articles/${editing.value}`, payload)
    } else {
      await axios.post('/api/repair-articles', payload)
    }
    closeModal()
    fetch(pagination.value.current_page)
  } catch (e) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0] ?? 'Failed to save.'
  } finally {
    saving.value = false
  }
}

function printCompletionInvoice(row) {
  const shop    = shopSettings.value
  const today   = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
  const d       = (v) => v ? new Date(v).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
  const advance = row.advance || 0
  const balance = (row.price || 0) - advance

  openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Repair Invoice — ${row.bill_number ?? row.id}</title>
  <style>${repairInvoiceCss()}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${shop.logo_url ? `<img src="${shop.logo_url}" class="logo">` : ''}
      <div>
        <div class="shop-name">${shop.shop_name || ''}</div>
        ${shop.address   ? `<div class="shop-sub" style="white-space:pre-line">${shop.address}</div>` : ''}
        ${shop.phone     ? `<div class="shop-sub">Tel: ${shop.phone}</div>` : ''}
        ${shop.br_number ? `<div class="shop-sub">BR No: ${shop.br_number}</div>` : ''}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title" style="color:#1d4ed8">REPAIR INVOICE</div>
      <div style="font-size:10px;color:#888;margin-bottom:4px">Completion Receipt</div>
      <table class="meta-table">
        ${row.bill_number ? `<tr><td>Bill No</td><td><strong>${row.bill_number}</strong></td></tr>` : ''}
        <tr><td>Received</td><td>${d(row.received_date)}</td></tr>
        <tr><td>Collected</td><td>${today}</td></tr>
      </table>
    </div>
  </div>
  <div class="cust">
    <strong>Customer:</strong> ${row.customer_name || 'Walk-in'}
    ${row.telephone ? ` &nbsp;|&nbsp; Tel: ${row.telephone}` : ''}
  </div>
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Article</th>
      <th style="text-align:left">Damage / Work Done</th>
      <th style="text-align:right;width:70px">Weight (g)</th>
      <th style="text-align:right;width:80px">Add Wt (g)</th>
    </tr></thead>
    <tbody>
      <tr>
        <td style="font-weight:700">${row.article}</td>
        <td>${row.damage || '—'}</td>
        <td style="text-align:right">${row.weight || '—'}</td>
        <td style="text-align:right">${row.add_weight || '—'}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      <div class="tline"><span>Total Repair Charge</span><span>${fmtLkr(row.price)}</span></div>
      <div class="tline"><span>Advance Paid</span><span>(${fmtLkr(advance)})</span></div>
      <div class="grand" style="color:#1d4ed8"><span>Balance Collected</span><span>${fmtLkr(balance)}</span></div>
    </div>
  </div>
  ${row.notes ? `<div style="margin-top:10px;font-size:10px;color:#555"><strong>Notes:</strong> ${row.notes}</div>` : ''}
  <div class="sigs">
    <div class="sig">Customer Signature</div>
    <div class="sig">Authorised By</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you for choosing us!</div>
    ${shop.shop_name ? `<div style="font-size:10px;color:#888;margin-top:2px">${shop.shop_name}</div>` : ''}
  </div>
  </body></html>`)
}

function confirmDelete(row) { deleteTarget.value = row }

async function doDelete() {
  deleting.value = true
  try {
    await axios.delete(`/api/repair-articles/${deleteTarget.value.id}`)
    deleteTarget.value = null
    fetch(pagination.value.current_page)
  } finally {
    deleting.value = false
  }
}

onMounted(async () => {
  fetch()
  const { data } = await axios.get('/api/shop-branding').catch(() => ({ data: {} }))
  shopSettings.value = data ?? {}
})

function fmtLkr(val) {
  return 'LKR ' + Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function openPrint(html) {
  const win = window.open('', '_blank', 'width=800,height=900')
  win.document.write(html)
  win.document.close()
  win.addEventListener('load', () => { win.focus(); win.print() })
}

function repairInvoiceCss() {
  return `
    @media print { @page { size: A5; margin: 10mm 12mm; } }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 12px 16px; }
    .hdr { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; padding-bottom:10px; border-bottom:2px solid #1a1a1a; }
    .logo { max-height:52px; max-width:80px; object-fit:contain; }
    .shop-name { font-size:15px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:2px; }
    .shop-sub { font-size:10px; color:#555; line-height:1.5; }
    .meta-r { text-align:right; min-width:140px; }
    .inv-title { font-size:17px; font-weight:900; letter-spacing:2px; margin-bottom:6px; }
    .meta-table { font-size:10px; border-collapse:collapse; margin-left:auto; }
    .meta-table td { padding:1px 4px; }
    .meta-table td:first-child { color:#888; text-align:right; }
    .meta-table td:last-child { font-size:11px; text-align:left; }
    .cust { font-size:11px; background:#f9f9f9; border:1px solid #e5e7eb; padding:6px 10px; border-radius:4px; margin-bottom:10px; }
    table.items { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:10px; }
    table.items thead tr { background:#1a1a1a; color:#fff; }
    table.items th { padding:5px 6px; font-size:10px; font-weight:700; letter-spacing:0.3px; }
    table.items tbody tr { border-bottom:1px solid #e5e7eb; }
    table.items td { padding:5px 6px; vertical-align:top; }
    .totals { display:flex; justify-content:flex-end; margin-top:8px; }
    .totals-box { min-width:220px; }
    .tline { display:flex; justify-content:space-between; font-size:11px; padding:3px 0; border-bottom:1px dashed #e5e7eb; }
    .grand { display:flex; justify-content:space-between; font-size:14px; font-weight:800; border-top:2px solid #1a1a1a; border-bottom:2px solid #1a1a1a; padding:4px 0; margin:2px 0; }
    .footer { text-align:center; margin-top:16px; padding-top:10px; border-top:1px dashed #ccc; font-size:11px; }
    .sigs { display:flex; justify-content:space-between; margin-top:32px; }
    .sig { border-top:1px solid #374151; width:160px; text-align:center; padding-top:4px; font-size:10px; color:#6b7280; }
  `
}

function printAdvanceInvoice(row) {
  const shop  = shopSettings.value
  const today = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
  const d     = (v) => v ? new Date(v).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
  const balance = (row.price || 0) - (row.advance || 0)

  openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Advance Invoice — ${row.bill_number ?? row.id}</title>
  <style>${repairInvoiceCss()}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${shop.logo_url ? `<img src="${shop.logo_url}" class="logo">` : ''}
      <div>
        <div class="shop-name">${shop.shop_name || ''}</div>
        ${shop.address ? `<div class="shop-sub" style="white-space:pre-line">${shop.address}</div>` : ''}
        ${shop.phone   ? `<div class="shop-sub">Tel: ${shop.phone}</div>` : ''}
        ${shop.br_number ? `<div class="shop-sub">BR No: ${shop.br_number}</div>` : ''}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title" style="color:#059669">ADVANCE RECEIPT</div>
      <div style="font-size:10px;color:#888;margin-bottom:4px">Repair Article</div>
      <table class="meta-table">
        ${row.bill_number ? `<tr><td>Bill No</td><td><strong>${row.bill_number}</strong></td></tr>` : ''}
        <tr><td>Received</td><td>${d(row.received_date)}</td></tr>
        ${row.give_date  ? `<tr><td>Expected</td><td>${d(row.give_date)}</td></tr>` : ''}
        <tr><td>Printed</td><td>${today}</td></tr>
      </table>
    </div>
  </div>
  <div class="cust">
    <strong>Customer:</strong> ${row.customer_name || 'Walk-in'}
    ${row.telephone ? ` &nbsp;|&nbsp; Tel: ${row.telephone}` : ''}
  </div>
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Article</th>
      <th style="text-align:left">Damage / Work</th>
      <th style="text-align:right;width:70px">Weight (g)</th>
      <th style="text-align:right;width:80px">Add Wt (g)</th>
    </tr></thead>
    <tbody>
      <tr>
        <td style="font-weight:700">${row.article}</td>
        <td>${row.damage || '—'}</td>
        <td style="text-align:right">${row.weight || '—'}</td>
        <td style="text-align:right">${row.add_weight || '—'}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      ${row.price ? `<div class="tline"><span>Estimated Price</span><span>${fmtLkr(row.price)}</span></div>` : ''}
      <div class="grand" style="color:#059669"><span>Advance Received</span><span>${fmtLkr(row.advance)}</span></div>
      ${row.price ? `<div class="tline" style="color:#dc2626;font-weight:700"><span>Balance Due</span><span>${fmtLkr(balance)}</span></div>` : ''}
    </div>
  </div>
  ${row.notes ? `<div style="margin-top:10px;font-size:10px;color:#555"><strong>Notes:</strong> ${row.notes}</div>` : ''}
  <div class="sigs">
    <div class="sig">Customer Signature</div>
    <div class="sig">Authorised By</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you! We'll notify you when your item is ready.</div>
    <div style="font-size:10px;color:#888;margin-top:2px">Balance payable on collection of the completed item.</div>
    ${shop.shop_name ? `<div style="font-size:10px;color:#888;margin-top:2px">${shop.shop_name}</div>` : ''}
  </div>
  </body></html>`)
}
</script>
