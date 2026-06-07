<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
          <PaintBrushIcon class="w-5 h-5 text-amber-500" />
          Custom Made Orders
        </h2>
        <p class="text-sm text-gray-400 mt-0.5">Manage bespoke jewellery orders — advance bills, completion &amp; customer invoices</p>
      </div>
      <button @click="openCreate" class="btn-primary flex items-center gap-2">
        <PlusIcon class="w-4 h-4" /> New Order
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-4 gap-4">
      <div v-for="c in summaryCards" :key="c.label" class="card text-center py-3">
        <p class="text-xs text-gray-500 uppercase tracking-wider">{{ c.label }}</p>
        <p class="text-2xl font-bold mt-1" :class="c.color">{{ c.value }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="card flex gap-3 flex-wrap">
      <input v-model="filters.search" placeholder="Ref #, description, customer…" class="form-input w-60" @input="load" />
      <select v-model="filters.status" class="form-input w-40" @change="load">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="issued">Issued</option>
      </select>
      <button @click="filters.search=''; filters.status=''; load()" class="btn-secondary text-sm">Clear</button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Ref #</th>
            <th class="table-th">Customer</th>
            <th class="table-th">Description</th>
            <th class="table-th">Drawing</th>
            <th class="table-th">Est. Weight</th>
            <th class="table-th">Est. Total</th>
            <th class="table-th">Advance</th>
            <th class="table-th">Final Total</th>
            <th class="table-th">Balance</th>
            <th class="table-th">Expected</th>
            <th class="table-th">Status</th>
            <th class="table-th">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-if="loading">
            <tr v-for="i in 7" :key="i" class="animate-pulse">
              <td class="table-td"><div class="h-3.5 w-16 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-24 bg-gray-200 rounded mb-1"></div><div class="h-3 w-16 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-28 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-12 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-14 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-20 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-3.5 w-16 bg-gray-200 rounded"></div></td>
              <td class="table-td"><div class="h-5 w-14 bg-gray-200 rounded-full"></div></td>
              <td class="table-td"><div class="flex gap-2"><div class="h-6 w-10 bg-gray-200 rounded-md"></div><div class="h-6 w-10 bg-gray-200 rounded-md"></div><div class="h-6 w-10 bg-gray-200 rounded-md"></div></div></td>
            </tr>
          </template>
          <template v-else>
          <tr v-for="r in orders.data" :key="r.id" class="hover:bg-gray-50">
            <td class="table-td font-mono text-xs text-gray-500">{{ r.reference_number }}</td>
            <td class="table-td">
              <p class="font-medium text-gray-800">{{ r.customer?.name ?? r.customer_name ?? '—' }}</p>
              <p class="text-xs text-gray-400">{{ r.customer?.phone ?? '' }}</p>
            </td>
            <td class="table-td max-w-[160px] truncate">{{ r.description }}</td>
            <td class="table-td">
              <img v-if="r.drawing_image_url" :src="r.drawing_image_url"
                class="w-12 h-12 object-cover rounded-lg border cursor-pointer hover:opacity-80"
                @click="previewImg = r.drawing_image_url" />
              <span v-else class="text-gray-300 text-xs">—</span>
            </td>
            <td class="table-td font-mono text-xs">
              {{ r.estimated_weight ? r.estimated_weight + 'g' : '—' }}
              <span v-if="r.karat" class="text-gold-600 ml-1">{{ r.karat.toUpperCase() }}</span>
            </td>
            <td class="table-td font-semibold text-gray-700">LKR {{ lkr(r.estimated_total) }}</td>
            <td class="table-td text-green-700 font-medium">LKR {{ lkr(r.advance_amount) }}</td>
            <td class="table-td font-semibold text-gold-700">
              <span v-if="r.final_total != null">LKR {{ lkr(r.final_total) }}</span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="table-td font-bold" :class="r.balance_amount > 0 ? 'text-red-600' : 'text-gray-400'">
              <span v-if="r.balance_amount != null">LKR {{ lkr(r.balance_amount) }}</span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="table-td text-xs text-gray-500">{{ fmtDate(r.expected_at) }}</td>
            <td class="table-td">
              <span :class="statusClass(r.status)" class="badge text-xs">{{ statusLabel(r.status) }}</span>
            </td>
            <td class="table-td">
              <div class="flex gap-1 flex-wrap">
                <!-- Edit -->
                <button v-if="r.status !== 'issued'"
                  @click="openEdit(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <PencilSquareIcon class="w-3.5 h-3.5" /> Edit
                </button>
                <!-- Advance Bill -->
                <button @click="printAdvanceBill(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-700 hover:bg-indigo-200 whitespace-nowrap">
                  <DocumentTextIcon class="w-3.5 h-3.5" /> Advance Bill
                </button>
                <!-- Complete -->
                <button v-if="r.status === 'pending' || r.status === 'in_progress'"
                  @click="openComplete(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 hover:bg-green-200 whitespace-nowrap">
                  <CheckCircleIcon class="w-3.5 h-3.5" /> Complete
                </button>
                <!-- Issue Invoice -->
                <button v-if="r.status === 'completed'"
                  @click="openIssue(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 whitespace-nowrap">
                  <ReceiptRefundIcon class="w-3.5 h-3.5" /> Issue
                </button>
                <!-- Print Invoice -->
                <button v-if="r.status === 'issued'"
                  @click="printInvoice(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 whitespace-nowrap">
                  <PrinterIcon class="w-3.5 h-3.5" /> Invoice
                </button>
                <!-- Delete -->
                <button v-if="r.status !== 'issued'"
                  @click="del(r)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                  <TrashIcon class="w-3.5 h-3.5" /> Delete
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!orders.data?.length">
            <td colspan="12" class="table-td text-center text-gray-400 py-10">No custom made orders found</td>
          </tr>
          </template>
        </tbody>
      </table>
      <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
        <span>{{ orders.total ?? 0 }} orders</span>
        <div class="flex gap-2">
          <button @click="page--; load()" :disabled="page<=1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
          <button @click="page++; load()" :disabled="page>=orders.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Image Preview -->
    <teleport to="body">
      <div v-if="previewImg" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4" @click="previewImg=null">
        <img :src="previewImg" class="max-w-2xl max-h-[80vh] rounded-2xl shadow-2xl object-contain" />
      </div>

      <!-- ═══════════════════════════════════════
           CREATE / EDIT MODAL
      ════════════════════════════════════════ -->
      <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[94vh] flex flex-col">
          <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
            <h3 class="font-semibold text-gray-800">{{ editing ? 'Edit Order' : 'New Custom Made Order' }}</h3>
            <button @click="showForm=false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
          </div>
          <div class="flex-1 overflow-y-auto p-6 space-y-5">

            <!-- Customer -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Customer (from list)</label>
                <select v-model="form.customer_id" class="form-input" @change="form.customer_name=''">
                  <option :value="null">— Select customer —</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} {{ c.phone ? '· '+c.phone : '' }}</option>
                </select>
              </div>
              <div>
                <label class="form-label">Or type customer name</label>
                <input v-model="form.customer_name" class="form-input" placeholder="Walk-in / new customer"
                  :disabled="!!form.customer_id" />
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="form-label">Item Description *</label>
              <textarea v-model="form.description" rows="2" class="form-input" placeholder="e.g. 22K gold necklace with floral design, approx 12g…"></textarea>
            </div>

            <!-- Drawing image -->
            <div>
              <label class="form-label">Design / Drawing Image</label>
              <div class="space-y-2">
                <div v-if="localPreviewUrl || form.drawing_image_url" class="flex items-center gap-3">
                  <img :src="localPreviewUrl || form.drawing_image_url" class="w-20 h-20 object-cover rounded-xl border" />
                  <div class="flex flex-col gap-1">
                    <span v-if="localPreviewUrl" class="text-xs text-amber-600">Will upload on save</span>
                    <button type="button" @click="form.drawing_image_url=''; clearPendingFile()"
                      class="text-xs text-red-500 hover:text-red-700">Remove</button>
                  </div>
                </div>
                <div v-else class="flex items-center gap-3">
                  <input type="file" accept="image/*" ref="drawingInput" class="hidden" @change="handleDrawingUpload" />
                  <button type="button" @click="drawingInput.click()"
                    class="btn-secondary text-sm flex items-center gap-1.5">
                    <PhotoIcon class="w-4 h-4" /> Choose Image
                  </button>
                  <input v-model="form.drawing_image_url" class="form-input flex-1" placeholder="Or paste image URL…" />
                </div>
              </div>
            </div>

            <!-- Weight & Karat -->
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="form-label">Est. Weight (g)</label>
                <input v-model.number="form.estimated_weight" type="number" min="0" step="0.001" class="form-input" @input="recalcEst" />
              </div>
              <div>
                <label class="form-label">Karat</label>
                <select v-model="form.karat" class="form-input">
                  <option value="">—</option>
                  <option v-for="k in ['9k','14k','18k','22k','24k']" :key="k" :value="k">{{ k.toUpperCase() }}</option>
                </select>
              </div>
              <div>
                <label class="form-label">Gold Rate / g (LKR)</label>
                <input v-model.number="form.gold_rate_per_gram" type="number" min="0" step="0.01" class="form-input" @input="recalcEst" />
              </div>
            </div>

            <!-- Charges -->
            <div class="border rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Charges (Estimated)</p>
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="form-label">Gold Cost (LKR)</label>
                  <input v-model.number="form.estimated_gold_cost" type="number" min="0" step="0.01" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Making Charge (LKR)</label>
                  <input v-model.number="form.making_charge" type="number" min="0" step="0.01" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Other Charges (LKR)</label>
                  <input v-model.number="form.other_charges" type="number" min="0" step="0.01" class="form-input" />
                </div>
              </div>
              <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5 flex justify-between items-center">
                <span class="text-sm text-gray-600">Estimated Total</span>
                <span class="font-bold text-gold-700 text-lg">LKR {{ lkr((form.estimated_gold_cost||0)+(form.making_charge||0)+(form.other_charges||0)) }}</span>
              </div>
            </div>

            <!-- Advance -->
            <div class="border rounded-xl p-4 space-y-3 bg-green-50/40">
              <p class="text-sm font-semibold text-gray-700">Advance Payment</p>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="form-label">Advance Amount (LKR)</label>
                  <input v-model.number="form.advance_amount" type="number" min="0" step="0.01" class="form-input" />
                </div>
                <div>
                  <label class="form-label">Advance Paid On</label>
                  <input v-model="form.advance_paid_at" type="date" class="form-input" />
                </div>
              </div>
            </div>

            <!-- Dates & Status -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Expected Completion</label>
                <input v-model="form.expected_at" type="date" class="form-input" />
              </div>
              <div v-if="editing">
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-input">
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                </select>
              </div>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Internal notes…"></textarea>
            </div>

            <p v-if="formError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ formError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 shrink-0">
            <button @click="showForm=false" class="btn-secondary">Cancel</button>
            <button @click="save" :disabled="saving" class="btn-primary px-6">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════
           COMPLETE MODAL
      ════════════════════════════════════════ -->
      <div v-if="showCompleteModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showCompleteModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[92vh] flex flex-col">
          <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
            <div>
              <h3 class="font-semibold text-gray-800">Mark Order Complete</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ completing?.reference_number }} — {{ completing?.customer?.name ?? completing?.customer_name }}</p>
            </div>
            <button @click="showCompleteModal=false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
          </div>
          <div class="flex-1 overflow-y-auto p-6 space-y-5">

            <!-- Estimated reference -->
            <div class="bg-gray-50 rounded-xl p-4 space-y-1 text-sm">
              <div class="flex justify-between text-gray-500">
                <span>Est. gold cost</span><span>LKR {{ lkr(completing?.estimated_gold_cost) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>Est. making charge</span><span>LKR {{ lkr(completing?.making_charge) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>Est. other charges</span><span>LKR {{ lkr(completing?.other_charges) }}</span>
              </div>
              <div class="flex justify-between font-semibold text-gray-700 border-t pt-2">
                <span>Estimated Total</span><span>LKR {{ lkr(completing?.estimated_total) }}</span>
              </div>
            </div>

            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Final Actual Charges</p>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Final Weight (g) *</label>
                <input v-model.number="completeForm.final_weight" type="number" min="0.001" step="0.001" class="form-input"
                  :placeholder="completing?.estimated_weight" />
              </div>
              <div>
                <label class="form-label">Final Gold Cost (LKR) *</label>
                <input v-model.number="completeForm.final_gold_cost" type="number" min="0" step="0.01" class="form-input"
                  :placeholder="completing?.estimated_gold_cost" />
              </div>
              <div>
                <label class="form-label">Final Making Charge (LKR) *</label>
                <input v-model.number="completeForm.final_making_charge" type="number" min="0" step="0.01" class="form-input"
                  :placeholder="completing?.making_charge" />
              </div>
              <div>
                <label class="form-label">Final Other Charges (LKR)</label>
                <input v-model.number="completeForm.final_other_charges" type="number" min="0" step="0.01" class="form-input"
                  :placeholder="completing?.other_charges" />
              </div>
            </div>

            <!-- Final total & balance preview -->
            <div class="space-y-1.5">
              <div class="bg-gold-50 border border-gold-200 rounded-xl p-3 flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">Final Total</span>
                <span class="text-lg font-bold text-gold-700">
                  LKR {{ lkr((completeForm.final_gold_cost||0)+(completeForm.final_making_charge||0)+(completeForm.final_other_charges||0)) }}
                </span>
              </div>
              <div class="bg-red-50 border border-red-200 rounded-xl p-3 flex justify-between items-center">
                <span class="text-sm font-medium text-gray-600">Balance (after advance LKR {{ lkr(completing?.advance_amount) }})</span>
                <span class="text-lg font-bold text-red-700">
                  LKR {{ lkr(Math.max(0, (completeForm.final_gold_cost||0)+(completeForm.final_making_charge||0)+(completeForm.final_other_charges||0)-(completing?.advance_amount||0))) }}
                </span>
              </div>
            </div>

            <div>
              <label class="form-label">Notes</label>
              <textarea v-model="completeForm.notes" rows="2" class="form-input" placeholder="e.g. Item ready, customer notified"></textarea>
            </div>

            <p v-if="completeError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ completeError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 shrink-0">
            <button @click="showCompleteModal=false" class="btn-secondary">Cancel</button>
            <button @click="doComplete" :disabled="completeSaving" class="btn-primary px-6 bg-green-600 hover:bg-green-700">
              {{ completeSaving ? 'Saving…' : 'Mark Complete' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════
           ISSUE MODAL
      ════════════════════════════════════════ -->
      <div v-if="showIssueModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showIssueModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col">
          <div class="flex items-center justify-between px-6 py-4 border-b shrink-0">
            <div>
              <h3 class="font-semibold text-gray-800">Issue Item to Customer</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ issuing?.reference_number }}</p>
            </div>
            <button @click="showIssueModal=false" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
          </div>
          <div class="p-6 space-y-4">
            <div class="bg-gray-50 rounded-xl p-4 space-y-1.5 text-sm">
              <div class="flex justify-between text-gray-500">
                <span>Final Total</span><span class="font-semibold">LKR {{ lkr(issuing?.final_total) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>Advance Paid</span><span class="text-green-700">− LKR {{ lkr(issuing?.advance_amount) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold text-red-700 border-t pt-2">
                <span>Balance Due</span><span>LKR {{ lkr(issuing?.balance_amount) }}</span>
              </div>
            </div>
            <div>
              <label class="form-label">Balance Collected (LKR) *</label>
              <input v-model.number="issueForm.balance_collected" type="number" min="0" step="0.01" class="form-input"
                :placeholder="issuing?.balance_amount" />
            </div>
            <p class="text-xs text-gray-400">After confirming, the order status will change to <strong>Issued</strong> and a final invoice will be printed.</p>
            <p v-if="issueError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ issueError }}</p>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 shrink-0">
            <button @click="showIssueModal=false" class="btn-secondary">Cancel</button>
            <button @click="doIssue" :disabled="issueSaving" class="btn-primary px-6 bg-amber-600 hover:bg-amber-700">
              {{ issueSaving ? 'Processing…' : 'Issue & Print Invoice' }}
            </button>
          </div>
        </div>
      </div>

    </teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { uploadToCloudinary } from '@/utils/cloudinary'
import {
  PlusIcon, PencilSquareIcon, TrashIcon,
  CheckCircleIcon, PrinterIcon, DocumentTextIcon,
  PhotoIcon,
} from '@heroicons/vue/24/outline'
import { PaintBrushIcon, ReceiptRefundIcon } from '@heroicons/vue/24/outline'

const orders   = ref({ data: [], total: 0, last_page: 1 })
const customers = ref([])
const page     = ref(1)
const filters  = reactive({ search: '', status: '' })
const loading  = ref(false)

const showForm  = ref(false)
const editing   = ref(null)
const saving    = ref(false)
const formError = ref('')

const showCompleteModal = ref(false)
const completing        = ref(null)
const completeSaving    = ref(false)
const completeError     = ref('')

const showIssueModal = ref(false)
const issuing        = ref(null)
const issueSaving    = ref(false)
const issueError     = ref('')

const previewImg   = ref(null)
const shopSettings = ref({})

const drawingInput      = ref(null)
const uploadingDrawing  = ref(false)
const pendingDrawingFile = ref(null)
const localPreviewUrl    = ref('')

const blankForm = () => ({
  customer_id: null, customer_name: '',
  description: '',
  drawing_image_url: '',
  estimated_weight: 0, karat: '', gold_rate_per_gram: 0,
  estimated_gold_cost: 0, making_charge: 0, other_charges: 0,
  advance_amount: 0, advance_paid_at: new Date().toISOString().split('T')[0],
  expected_at: '', notes: '', status: 'pending',
})
const form = reactive(blankForm())

const blankComplete = () => ({
  final_weight: null, final_gold_cost: null,
  final_making_charge: null, final_other_charges: 0,
  notes: '',
})
const completeForm = reactive(blankComplete())

const issueForm = reactive({ balance_collected: null })

const summaryCards = computed(() => {
  const data = orders.value.data ?? []
  return [
    { label: 'Total',       value: orders.value.total ?? 0, color: 'text-gray-800' },
    { label: 'Pending',     value: data.filter(r => r.status === 'pending').length,     color: 'text-yellow-600' },
    { label: 'In Progress', value: data.filter(r => r.status === 'in_progress').length, color: 'text-blue-600' },
    { label: 'Completed',   value: data.filter(r => r.status === 'completed').length,   color: 'text-green-600' },
  ]
})

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(val) {
  if (!val) return '—'
  return new Date(val).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function statusClass(s) {
  return {
    pending:     'bg-yellow-100 text-yellow-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed:   'bg-green-100 text-green-700',
    issued:      'bg-gray-100 text-gray-600',
  }[s] ?? 'bg-gray-100 text-gray-600'
}

function statusLabel(s) {
  return { pending: 'Pending', in_progress: 'In Progress', completed: 'Completed', issued: 'Issued' }[s] ?? s
}

function recalcEst() {
  if (form.estimated_weight && form.gold_rate_per_gram) {
    form.estimated_gold_cost = parseFloat((form.estimated_weight * form.gold_rate_per_gram).toFixed(2))
  }
}

function handleDrawingUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return
  e.target.value = ''
  if (localPreviewUrl.value) URL.revokeObjectURL(localPreviewUrl.value)
  pendingDrawingFile.value = file
  localPreviewUrl.value = URL.createObjectURL(file)
  form.drawing_image_url = ''
}

function clearPendingFile() {
  if (localPreviewUrl.value) URL.revokeObjectURL(localPreviewUrl.value)
  pendingDrawingFile.value = null
  localPreviewUrl.value = ''
}

function openCreate() {
  editing.value = null
  formError.value = ''
  clearPendingFile()
  Object.assign(form, blankForm())
  showForm.value = true
}

function openEdit(r) {
  editing.value = r
  formError.value = ''
  clearPendingFile()
  Object.assign(form, {
    customer_id: r.customer_id, customer_name: r.customer_name ?? '',
    description: r.description,
    drawing_image_url: r.drawing_image_url ?? '',
    estimated_weight: r.estimated_weight, karat: r.karat ?? '',
    gold_rate_per_gram: r.gold_rate_per_gram,
    estimated_gold_cost: r.estimated_gold_cost, making_charge: r.making_charge,
    other_charges: r.other_charges,
    advance_amount: r.advance_amount,
    advance_paid_at: r.advance_paid_at ? r.advance_paid_at.split('T')[0] : '',
    expected_at: r.expected_at ? r.expected_at.split('T')[0] : '',
    notes: r.notes ?? '', status: r.status,
  })
  showForm.value = true
}

function openComplete(r) {
  completing.value = r
  completeError.value = ''
  Object.assign(completeForm, {
    final_weight: r.estimated_weight,
    final_gold_cost: r.estimated_gold_cost,
    final_making_charge: r.making_charge,
    final_other_charges: r.other_charges ?? 0,
    notes: '',
  })
  showCompleteModal.value = true
}

function openIssue(r) {
  issuing.value = r
  issueError.value = ''
  issueForm.balance_collected = r.balance_amount ?? 0
  showIssueModal.value = true
}

function openPrint(html) {
  const win = window.open('', '_blank', 'width=800,height=900')
  win.document.write(html)
  win.document.close()
  win.addEventListener('load', () => { win.focus(); win.print() })
}

function fmtLkr(val) {
  return 'LKR ' + Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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

function printAdvanceBill(r) {
  const shop = shopSettings.value
  const today = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
  const d = (v) => v ? new Date(v).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
  openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Advance Bill — ${r.reference_number}</title>
  <style>${a5Css()}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${shop.logo_url ? `<img src="${shop.logo_url}" class="logo">` : ''}
      <div>
        <div class="shop-name">${shop.shop_name || ''}</div>
        ${shop.address ? `<div class="shop-sub" style="white-space:pre-line">${shop.address}</div>` : ''}
        ${shop.phone ? `<div class="shop-sub">Tel: ${shop.phone}</div>` : ''}
        ${shop.br_number ? `<div class="shop-sub">BR No: ${shop.br_number}</div>` : ''}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title" style="color:#7c3aed">ADVANCE RECEIPT</div>
      <div style="font-size:10px;color:#888;margin-bottom:4px">Custom Made Order</div>
      <table class="meta-table">
        <tr><td>Ref No</td><td><strong>${r.reference_number}</strong></td></tr>
        <tr><td>Date</td><td>${d(r.advance_paid_at ?? r.created_at)}</td></tr>
        <tr><td>Printed</td><td>${today}</td></tr>
        ${r.expected_at ? `<tr><td>Expected</td><td>${d(r.expected_at)}</td></tr>` : ''}
      </table>
    </div>
  </div>
  <div class="cust">
    <strong>Customer:</strong> ${r.customer?.name ?? r.customer_name ?? 'Walk-in'}
    ${r.customer?.phone ? ` &nbsp;|&nbsp; Tel: ${r.customer.phone}` : ''}
  </div>
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Description</th>
      <th style="text-align:center;width:55px">Karat</th>
      <th style="text-align:right;width:80px">Est. Weight</th>
      <th style="text-align:right;width:100px">Gold Cost</th>
      <th style="text-align:right;width:100px">Making</th>
      <th style="text-align:right;width:95px">Others</th>
    </tr></thead>
    <tbody>
      <tr>
        <td>${r.description}</td>
        <td style="text-align:center;font-weight:700">${r.karat ? r.karat.toUpperCase() : '—'}</td>
        <td style="text-align:right">${r.estimated_weight ? r.estimated_weight + ' g' : '—'}</td>
        <td style="text-align:right">${fmtLkr(r.estimated_gold_cost)}</td>
        <td style="text-align:right">${fmtLkr(r.making_charge)}</td>
        <td style="text-align:right">${fmtLkr(r.other_charges)}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      <div class="tline"><span>Estimated Total</span><span>${fmtLkr(r.estimated_total)}</span></div>
      <div class="grand" style="color:#7c3aed"><span>Advance Received</span><span>${fmtLkr(r.advance_amount)}</span></div>
      <div class="tline" style="color:#dc2626;font-weight:700"><span>Balance (approx.)</span><span>${fmtLkr((r.estimated_total || 0) - (r.advance_amount || 0))}</span></div>
    </div>
  </div>
  ${r.notes ? `<div style="margin-top:10px;font-size:10px;color:#555"><strong>Notes:</strong> ${r.notes}</div>` : ''}
  <div class="sigs">
    <div class="sig">Customer Signature</div>
    <div class="sig">Authorised By</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you for your order!</div>
    <div style="font-size:10px;color:#888;margin-top:2px">Balance payable on collection of the completed item.</div>
    ${shop.shop_name ? `<div style="font-size:10px;color:#888;margin-top:2px">${shop.shop_name}</div>` : ''}
  </div>
  </body></html>`)
}

function printInvoice(r) {
  const shop = shopSettings.value
  const today = new Date().toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
  const d = (v) => v ? new Date(v).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
  openPrint(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice — ${r.reference_number}</title>
  <style>${a5Css()}</style></head><body>
  <div class="hdr">
    <div style="display:flex;align-items:flex-start;gap:10px">
      ${shop.logo_url ? `<img src="${shop.logo_url}" class="logo">` : ''}
      <div>
        <div class="shop-name">${shop.shop_name || ''}</div>
        ${shop.address ? `<div class="shop-sub" style="white-space:pre-line">${shop.address}</div>` : ''}
        ${shop.phone ? `<div class="shop-sub">Tel: ${shop.phone}</div>` : ''}
        ${shop.br_number ? `<div class="shop-sub">BR No: ${shop.br_number}</div>` : ''}
      </div>
    </div>
    <div class="meta-r">
      <div class="inv-title">INVOICE</div>
      <div style="font-size:10px;color:#888;margin-bottom:4px">Custom Made Order</div>
      <table class="meta-table">
        <tr><td>Invoice Ref</td><td><strong>${r.reference_number}</strong></td></tr>
        <tr><td>Issued</td><td>${d(r.issued_at ?? new Date().toISOString())}</td></tr>
        <tr><td>Printed</td><td>${today}</td></tr>
      </table>
    </div>
  </div>
  <div class="cust">
    <strong>Bill To:</strong> ${r.customer?.name ?? r.customer_name ?? 'Walk-in'}
    ${r.customer?.phone ? ` &nbsp;|&nbsp; Tel: ${r.customer.phone}` : ''}
  </div>
  <table class="items">
    <thead><tr>
      <th style="text-align:left">Description</th>
      <th style="text-align:center;width:55px">Karat</th>
      <th style="text-align:right;width:80px">Final Wt</th>
      <th style="text-align:right;width:100px">Gold Cost</th>
      <th style="text-align:right;width:100px">Making</th>
      <th style="text-align:right;width:95px">Others</th>
    </tr></thead>
    <tbody>
      <tr>
        <td>${r.description}</td>
        <td style="text-align:center;font-weight:700">${r.karat ? r.karat.toUpperCase() : '—'}</td>
        <td style="text-align:right">${r.final_weight ? r.final_weight + ' g' : '—'}</td>
        <td style="text-align:right">${fmtLkr(r.final_gold_cost)}</td>
        <td style="text-align:right">${fmtLkr(r.final_making_charge)}</td>
        <td style="text-align:right">${fmtLkr(r.final_other_charges)}</td>
      </tr>
    </tbody>
  </table>
  <div class="totals">
    <div class="totals-box">
      <div class="grand"><span>TOTAL</span><span>${fmtLkr(r.final_total)}</span></div>
      <div class="tline" style="color:#16a34a"><span>Advance Paid</span><span>− ${fmtLkr(r.advance_amount)}</span></div>
      <div class="tline" style="font-weight:700"><span>Balance Paid</span><span>${fmtLkr(r.balance_amount)}</span></div>
    </div>
  </div>
  ${r.notes ? `<div style="margin-top:10px;font-size:10px;color:#555"><strong>Notes:</strong> ${r.notes}</div>` : ''}
  <div class="sigs">
    <div class="sig">Customer Signature</div>
    <div class="sig">Authorised By</div>
  </div>
  <div class="footer">
    <div style="font-weight:600">Thank you for your valued business!</div>
    ${shop.shop_name ? `<div style="font-size:10px;color:#888;margin-top:2px">${shop.shop_name}</div>` : ''}
  </div>
  </body></html>`)
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    if (pendingDrawingFile.value) {
      uploadingDrawing.value = true
      try {
        const result = await uploadToCloudinary(pendingDrawingFile.value, { folder: 'custom_made_orders', tags: ['drawing'] })
        form.drawing_image_url = result.url
        clearPendingFile()
      } catch {
        formError.value = 'Image upload failed. Please try again or paste a URL.'
        return
      } finally {
        uploadingDrawing.value = false
      }
    }
    if (editing.value) {
      await axios.put(`/api/custom-made-orders/${editing.value.id}`, form)
    } else {
      await axios.post('/api/custom-made-orders', form)
    }
    showForm.value = false
    load()
  } catch (e) {
    formError.value = e.response?.data?.message ??
      Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { saving.value = false }
}

async function doComplete() {
  completeSaving.value = true; completeError.value = ''
  try {
    await axios.post(`/api/custom-made-orders/${completing.value.id}/complete`, completeForm)
    showCompleteModal.value = false
    load()
  } catch (e) {
    completeError.value = e.response?.data?.message ??
      Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error completing'
  } finally { completeSaving.value = false }
}

async function doIssue() {
  if (issueForm.balance_collected === null || issueForm.balance_collected === '') {
    issueError.value = 'Enter the balance collected.'; return
  }
  issueSaving.value = true; issueError.value = ''
  try {
    const { data } = await axios.post(`/api/custom-made-orders/${issuing.value.id}/issue`, issueForm)
    showIssueModal.value = false
    printInvoice(data)
    load()
  } catch (e) {
    issueError.value = e.response?.data?.message ??
      Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error issuing'
  } finally { issueSaving.value = false }
}

async function del(r) {
  if (!confirm(`Delete order ${r.reference_number}?`)) return
  try {
    await axios.delete(`/api/custom-made-orders/${r.id}`)
    load()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error deleting')
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/custom-made-orders', { params: { ...filters, page: page.value } })
    orders.value = data
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  const [c, branding] = await Promise.all([
    axios.get('/api/custom-made-orders/options/customers'),
    axios.get('/api/shop-branding').catch(() => ({ data: {} })),
  ])
  customers.value = c.data
  shopSettings.value = branding.data ?? {}
  load()
})
</script>
