<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Gold Loans</h1>
        <p class="text-sm text-gray-500 mt-1">Private gold loan records</p>
      </div>
      <button @click="openGlModal"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium text-sm shadow-sm">
        <PlusIcon class="w-4 h-4" /> New Loan
      </button>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap items-end">
      <input v-model="search" type="text" placeholder="Search borrower / loan #" class="form-input w-56" @input="load" />
      <select v-model="statusFilter" class="form-input w-36" @change="load">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="closed">Closed</option>
        <option value="overdue">Overdue</option>
      </select>
      <button @click="search=''; statusFilter=''; load()" class="btn-secondary text-sm">Clear</button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div v-if="loading" class="text-center py-10 text-gray-400">Loading…</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-800 text-white text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left w-6"></th>
            <th class="px-4 py-3 text-left">Loan #</th>
            <th class="px-4 py-3 text-left">Borrower</th>
            <th class="px-4 py-3 text-left">Pledge</th>
            <th class="px-4 py-3 text-right">Loan Amt</th>
            <th class="px-4 py-3 text-right">Outstanding</th>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="!loans.length">
            <td colspan="9" class="text-center py-10 text-gray-400">No loans found</td>
          </tr>
          <template v-for="loan in loans" :key="loan.id">
            <tr class="hover:bg-gray-50 cursor-pointer" @click="toggle(loan.id)">
              <td class="px-4 py-2.5 text-gray-400">
                <ChevronDownIcon v-if="expanded.has(loan.id)" class="w-4 h-4" />
                <ChevronRightIcon v-else class="w-4 h-4" />
              </td>
              <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ loan.loan_number }}</td>
              <td class="px-4 py-2.5">
                <div class="font-medium text-gray-800">{{ loan.borrower_name }}</div>
                <div class="text-xs text-gray-400">{{ loan.borrower_phone }}</div>
              </td>
              <td class="px-4 py-2.5 text-xs text-gray-600">
                {{ loan.pledge_description || '—' }}
                <span v-if="loan.declared_karat" class="ml-1 bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full text-xs">{{ loan.declared_karat }}</span>
              </td>
              <td class="px-4 py-2.5 text-right font-medium">LKR {{ lkr(loan.loan_amount) }}</td>
              <td class="px-4 py-2.5 text-right font-bold" :class="loan.outstanding_principal > 0 ? 'text-red-600' : 'text-green-600'">
                LKR {{ lkr(loan.outstanding_principal) }}
              </td>
              <td class="px-4 py-2.5 text-xs text-gray-500">{{ fmtDate(loan.disbursed_date) }}</td>
              <td class="px-4 py-2.5 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                  :class="{
                    'bg-green-100 text-green-700': loan.status === 'active',
                    'bg-gray-100 text-gray-600':   loan.status === 'closed',
                    'bg-red-100 text-red-700':     loan.status === 'overdue',
                  }">{{ loan.status }}</span>
              </td>
              <td class="px-4 py-2.5 text-center">
                <div class="flex items-center justify-center gap-1">
                  <button v-if="loan.status === 'active'" @click.stop="openRepayModal(loan)"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-green-50 hover:bg-green-100 text-green-600 text-xs font-medium border border-green-200">
                    Repay
                  </button>
                  <button @click.stop="deleteLoan(loan)"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-red-50 hover:bg-red-100 text-red-500 text-xs font-medium border border-red-200">
                    <TrashIcon class="w-3 h-3" /> Del
                  </button>
                  <button @click.stop="printLoan(loan)" :disabled="printing === loan.id"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs font-medium border border-gray-200">
                    {{ printing === loan.id ? '…' : '🖨' }}
                  </button>
                </div>
              </td>
            </tr>
            <!-- Repayments -->
            <tr v-if="expanded.has(loan.id)" class="bg-amber-50/40">
              <td colspan="9" class="px-10 py-3">
                <p class="text-xs font-semibold text-gray-600 mb-2">Repayments</p>
                <div v-if="!loan.repayments?.length" class="text-xs text-gray-400">No repayments yet</div>
                <table v-else class="w-full text-xs">
                  <thead><tr class="text-gray-500">
                    <th class="text-left py-1">Date</th>
                    <th class="text-right py-1">Principal</th>
                    <th class="text-right py-1">Interest</th>
                    <th class="text-right py-1">Total</th>
                    <th class="text-right py-1">Notes</th>
                    <th class="text-center py-1">Del</th>
                  </tr></thead>
                  <tbody>
                    <tr v-for="r in loan.repayments" :key="r.id" class="border-t border-amber-100">
                      <td class="py-1">{{ fmtDate(r.payment_date) }}</td>
                      <td class="text-right py-1">LKR {{ lkr(r.principal_paid) }}</td>
                      <td class="text-right py-1">LKR {{ lkr(r.interest_paid) }}</td>
                      <td class="text-right py-1 font-semibold">LKR {{ lkr(r.total_paid) }}</td>
                      <td class="text-right py-1 text-gray-500">{{ r.notes || '—' }}</td>
                      <td class="text-center py-1">
                        <button @click="deleteRepayment(loan, r)" class="text-red-400 hover:text-red-600">
                          <TrashIcon class="w-3 h-3 inline" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- New Loan Modal -->
    <teleport to="body">
      <div v-if="glModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">New Gold Loan</h3>
            <button @click="glModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
          <form @submit.prevent="saveGl" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="form-label">Borrower Name *</label>
                <input v-model="glForm.borrower_name" type="text" required class="form-input" />
              </div>
              <div>
                <label class="form-label">NIC</label>
                <input v-model="glForm.borrower_nic" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Phone</label>
                <input v-model="glForm.borrower_phone" type="text" class="form-input" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Pledge Description</label>
                <input v-model="glForm.pledge_description" type="text" class="form-input" placeholder="e.g. 22K gold chain 10g" />
              </div>
              <div>
                <label class="form-label">Karat</label>
                <select v-model="glForm.declared_karat" class="form-input">
                  <option value="">—</option>
                  <option>24K</option><option>22K</option><option>21K</option><option>18K</option>
                </select>
              </div>
              <div>
                <label class="form-label">Net Weight (g)</label>
                <input v-model="glForm.net_weight" type="number" step="0.001" min="0" class="form-input" />
              </div>
              <div>
                <label class="form-label">Loan Amount (LKR) *</label>
                <input v-model="glForm.loan_amount" type="number" min="0.01" step="0.01" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Interest (% / month)</label>
                <input v-model="glForm.interest_rate_monthly" type="number" min="0" step="0.1" class="form-input" />
              </div>
              <div>
                <label class="form-label">Disbursed Date *</label>
                <input v-model="glForm.disbursed_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Maturity Date</label>
                <input v-model="glForm.maturity_date" type="date" class="form-input" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Notes</label>
                <textarea v-model="glForm.notes" rows="2" class="form-input"></textarea>
              </div>
            </div>
            <p v-if="glError" class="text-red-500 text-sm">{{ glError }}</p>
            <div class="flex justify-end gap-3">
              <button type="button" @click="glModal = false" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="glSaving" class="btn-primary">{{ glSaving ? 'Saving…' : 'Save Loan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- Repayment Modal -->
    <teleport to="body">
      <div v-if="repayModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Record Repayment — {{ repayLoan?.loan_number }}</h3>
            <button @click="repayModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
          <form @submit.prevent="saveRepay" class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Outstanding: <span class="font-bold text-red-600">LKR {{ lkr(repayLoan?.outstanding_principal) }}</span></p>
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="form-label">Payment Date *</label>
                <input v-model="repayForm.payment_date" type="date" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Principal Paid (LKR) *</label>
                <input v-model="repayForm.principal_paid" type="number" min="0" step="0.01" required class="form-input" />
              </div>
              <div>
                <label class="form-label">Interest Paid (LKR)</label>
                <input v-model="repayForm.interest_paid" type="number" min="0" step="0.01" class="form-input" />
              </div>
              <div class="col-span-2">
                <label class="form-label">Notes</label>
                <input v-model="repayForm.notes" type="text" class="form-input" />
              </div>
            </div>
            <p v-if="repayError" class="text-red-500 text-sm">{{ repayError }}</p>
            <div class="flex justify-end gap-3">
              <button type="button" @click="repayModal = false" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="repaySaving" class="btn-primary">{{ repaySaving ? 'Saving…' : 'Save Repayment' }}</button>
            </div>
          </form>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { PlusIcon, TrashIcon, ChevronDownIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { fmtDate } from '../utils/date.js'

const loans       = ref([])
const loading     = ref(false)
const search      = ref('')
const statusFilter = ref('')
const expanded    = ref(new Set())

const glModal  = ref(false)
const glSaving = ref(false)
const glError  = ref('')
const glForm   = reactive({
  borrower_name: '', borrower_nic: '', borrower_phone: '',
  pledge_description: '', declared_karat: '', net_weight: 0,
  loan_amount: 0, interest_rate_monthly: 0,
  disbursed_date: '', maturity_date: '', notes: '',
})

const repayModal  = ref(false)
const repaySaving = ref(false)
const repayError  = ref('')
const repayLoan   = ref(null)
const repayForm   = reactive({ payment_date: '', principal_paid: 0, interest_paid: 0, notes: '' })

const lkr = (v) => Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/private-gold-loans', { params: { search: search.value, status: statusFilter.value } })
    loans.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error('Gold loans load error:', e)
    loans.value = []
  } finally { loading.value = false }
}

function toggle(id) {
  if (expanded.value.has(id)) expanded.value.delete(id)
  else expanded.value.add(id)
  expanded.value = new Set(expanded.value)
}

function openGlModal() {
  glError.value = ''
  Object.assign(glForm, {
    borrower_name: '', borrower_nic: '', borrower_phone: '',
    pledge_description: '', declared_karat: '', net_weight: 0,
    loan_amount: 0, interest_rate_monthly: 0,
    disbursed_date: new Date().toISOString().slice(0, 10),
    maturity_date: '', notes: '',
  })
  glModal.value = true
}

async function saveGl() {
  glSaving.value = true; glError.value = ''
  try {
    await axios.post('/api/private-gold-loans', glForm)
    glModal.value = false
    await load()
  } catch (e) {
    glError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { glSaving.value = false }
}

async function deleteLoan(loan) {
  if (!confirm(`Delete loan ${loan.loan_number}? This cannot be undone.`)) return
  try {
    await axios.delete(`/api/private-gold-loans/${loan.id}`)
    await load()
  } catch (e) { alert(e.response?.data?.message ?? 'Delete failed') }
}

function openRepayModal(loan) {
  repayError.value = ''
  repayLoan.value  = loan
  Object.assign(repayForm, { payment_date: new Date().toISOString().slice(0, 10), principal_paid: 0, interest_paid: 0, notes: '' })
  repayModal.value = true
}

async function saveRepay() {
  repaySaving.value = true; repayError.value = ''
  try {
    const { data } = await axios.post(`/api/private-gold-loans/${repayLoan.value.id}/repayments`, repayForm)
    const idx = loans.value.findIndex(l => l.id === data.id)
    if (idx !== -1) loans.value[idx] = data
    repayModal.value = false
  } catch (e) {
    repayError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { repaySaving.value = false }
}

async function deleteRepayment(loan, repayment) {
  if (!confirm('Delete this repayment?')) return
  try {
    const { data } = await axios.delete(`/api/private-gold-loans/${loan.id}/repayments/${repayment.id}`)
    const idx = loans.value.findIndex(l => l.id === data.id)
    if (idx !== -1) loans.value[idx] = data
  } catch (e) { alert(e.response?.data?.message ?? 'Delete failed') }
}

const printing     = ref(null)
const shopSettings = ref({})

async function loadShopSettings() {
  try { const { data } = await axios.get('/api/shop-branding'); shopSettings.value = data } catch { /* non-critical */ }
}

function openPrint(html) {
  const win = window.open('', '_blank', 'width=800,height=900')
  win.document.write(html)
  win.document.close()
  win.addEventListener('load', () => { win.focus(); win.print() })
}

function a5Css() {
  return `
    @media print { @page { size: A5; margin: 10mm 12mm; } }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 12px 16px; }
    .hdr { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; padding-bottom:10px; border-bottom:2px solid #1a1a1a; }
    .logo { max-height:52px; max-width:80px; object-fit:contain; }
    .shop-name { font-size:15px; font-weight:800; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:2px; }
    .shop-sub { font-size:10px; color:#555; line-height:1.5; }
    .meta-r { text-align:right; min-width:140px; }
    .inv-title { font-size:17px; font-weight:900; letter-spacing:2px; margin-bottom:6px; color:#b45309; }
    .meta-table { font-size:10px; border-collapse:collapse; margin-left:auto; }
    .meta-table td { padding:1px 4px; }
    .meta-table td:first-child { color:#888; text-align:right; }
    .meta-table td:last-child { font-size:11px; text-align:left; }
    .cust { font-size:11px; background:#f9f9f9; border:1px solid #e5e7eb; padding:6px 10px; border-radius:4px; margin-bottom:10px; }
    .section { display:grid; grid-template-columns:1fr 1fr; gap:4px 20px; margin-bottom:10px; }
    .row { display:flex; flex-direction:column; padding:3px 0; border-bottom:1px dashed #f0f0f0; }
    .lbl { font-size:9px; text-transform:uppercase; color:#9ca3af; letter-spacing:.4px; }
    .val { font-size:11px; font-weight:600; }
    table.items { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:10px; }
    table.items thead tr { background:#1a1a1a; color:#fff; }
    table.items th { padding:5px 6px; font-size:10px; font-weight:700; letter-spacing:0.3px; }
    table.items tbody tr { border-bottom:1px solid #e5e7eb; }
    table.items tbody tr:nth-child(even) { background:#fafafa; }
    table.items td { padding:5px 6px; vertical-align:top; }
    .totals { display:flex; justify-content:flex-end; margin-top:8px; }
    .totals-box { min-width:220px; }
    .tline { display:flex; justify-content:space-between; font-size:11px; padding:3px 0; border-bottom:1px dashed #e5e7eb; }
    .grand { display:flex; justify-content:space-between; font-size:11px; font-weight:800; border-top:2px solid #1a1a1a; border-bottom:2px solid #1a1a1a; padding:4px 0; margin:2px 0; }
    .footer { text-align:center; margin-top:16px; padding-top:10px; border-top:1px dashed #ccc; font-size:11px; }
    .sigs { display:flex; justify-content:space-between; margin-top:32px; }
    .sig { border-top:1px solid #374151; width:160px; text-align:center; padding-top:4px; font-size:10px; color:#6b7280; }
  `
}

function fmtLkr(val) {
  return 'LKR ' + Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function printLoan(loan) {
  printing.value = loan.id
  try {
    if (!shopSettings.value.shop_name) {
      try { const { data } = await axios.get('/api/shop-branding'); shopSettings.value = data } catch { /* non-critical */ }
    }
    const shop = shopSettings.value
    const today = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
    const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
    const repayments = loan.repayments ?? []

    const repayRows = repayments.length
      ? repayments.map(r => `<tr>
          <td>${fmtDate(r.payment_date)}</td>
          <td style="text-align:right">${fmtLkr(r.principal_paid)}</td>
          <td style="text-align:right">${fmtLkr(r.interest_paid)}</td>
          <td style="text-align:right;font-weight:700">${fmtLkr((r.principal_paid || 0) + (r.interest_paid || 0))}</td>
          <td>${r.notes ?? '—'}</td>
        </tr>`).join('')
      : '<tr><td colspan="5" style="text-align:center;color:#888;padding:8px">No repayments yet</td></tr>'

    const outstandingColor = Number(loan.outstanding_principal) > 0 ? '#dc2626' : '#16a34a'

    openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Loan — ${loan.loan_number}</title>
    <style>${a5Css()}</style></head><body>
    <div class="hdr">
      <div style="display:flex;align-items:flex-start;gap:10px">
        ${shop.logo_url ? `<img src="${shop.logo_url}" class="logo">` : ''}
        <div>
          <div class="shop-name">${shop.shop_name || 'රන් අත්වැල් GOLD HOUSE'}</div>
          ${shop.address ? `<div class="shop-sub" style="white-space:pre-line">${shop.address}</div>` : ''}
          ${shop.phone ? `<div class="shop-sub">Tel: ${shop.phone}</div>` : ''}
        </div>
      </div>
      <div class="meta-r">
        <div class="inv-title">LOAN RECEIPT</div>
        <table class="meta-table">
          <tr><td>Loan No</td><td><strong>${loan.loan_number}</strong></td></tr>
          <tr><td>Issued</td><td>${fmtDate(loan.disbursed_date)}</td></tr>
          <tr><td>Maturity</td><td>${fmtDate(loan.maturity_date)}</td></tr>
          <tr><td>Status</td><td><strong style="text-transform:uppercase">${loan.status ?? ''}</strong></td></tr>
          <tr><td>Printed</td><td>${today}</td></tr>
        </table>
      </div>
    </div>
    <div class="cust">
      <strong>${loan.borrower_name ?? '—'}</strong>
      ${loan.borrower_phone ? `<span style="margin-left:8px;color:#555">${loan.borrower_phone}</span>` : ''}
      ${loan.borrower_nic ? `<span style="margin-left:8px;color:#888;font-size:10px">NIC: ${loan.borrower_nic}</span>` : ''}
    </div>
    <div class="section">
      <div class="row"><span class="lbl">Pledge Item</span><span class="val">${loan.pledge_description ?? '—'}</span></div>
      <div class="row"><span class="lbl">Declared Karat</span><span class="val">${loan.declared_karat ?? '—'}</span></div>
      <div class="row"><span class="lbl">Net Weight</span><span class="val">${Number(loan.net_weight || 0).toFixed(3)} g</span></div>
      <div class="row"><span class="lbl">Interest Rate</span><span class="val">${loan.interest_rate_monthly ?? 0}% / month</span></div>
    </div>
    <div class="totals">
      <div class="totals-box">
        <div class="tline"><span>Loan Amount</span><span style="color:#1d4ed8;font-weight:700">${fmtLkr(loan.loan_amount)}</span></div>
        <div class="grand"><span>OUTSTANDING</span><span style="color:${outstandingColor}">${fmtLkr(loan.outstanding_principal)}</span></div>
        ${loan.notes ? `<div class="tline"><span>Notes</span><span style="max-width:160px;text-align:right">${loan.notes}</span></div>` : ''}
      </div>
    </div>
    <div style="font-weight:700;font-size:11px;margin:10px 0 4px;color:#374151">Repayment History</div>
    <table class="items">
      <thead><tr>
        <th style="text-align:left">Date</th>
        <th style="text-align:right">Principal</th>
        <th style="text-align:right">Interest</th>
        <th style="text-align:right">Total</th>
        <th style="text-align:left">Notes</th>
      </tr></thead>
      <tbody>${repayRows}</tbody>
    </table>
    <div class="sigs">
      <div class="sig">Lender / Recorder</div>
      <div class="sig">Borrower Signature</div>
    </div>
    <div class="footer">
      <div style="font-weight:600">Private Gold Book Record</div>
      ${shop.shop_name ? `<div style="font-size:10px;color:#888;margin-top:2px">${shop.shop_name}</div>` : ''}
    </div>
    </body></html>`)
  } catch (e) {
    alert('Could not generate print.')
  } finally {
    printing.value = null
  }
}

onMounted(() => { load(); loadShopSettings() })
</script>
