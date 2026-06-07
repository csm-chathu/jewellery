<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Layaway / Installments</h1>
        <p class="text-sm text-gray-500 mt-1">Track customer installment bookings and payments</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
        <PlusIcon class="h-5 w-5" />
        New Layaway
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Layaways</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ summary.total }}</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Active</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ summary.active }}</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Completed</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ summary.completed }}</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Balance Outstanding</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ fmtCurrency(summary.balance) }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
        <select v-model="filters.status" @change="loadLayaways()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
        <input v-model="filters.from" @change="loadLayaways()" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
        <input v-model="filters.to" @change="loadLayaways()" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
      </div>
      <button @click="resetFilters" class="px-3 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Reset</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <table class="min-w-full text-sm divide-y divide-gray-200">
        <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left">Reference</th>
            <th class="px-4 py-3 text-left">Customer</th>
            <th class="px-4 py-3 text-left">Item</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-right">Paid</th>
            <th class="px-4 py-3 text-right">Balance</th>
            <th class="px-4 py-3 text-center">Progress</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th class="px-4 py-3 text-left">Booked</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-if="loading">
            <tr v-for="n in 7" :key="n" class="animate-pulse">
              <td class="px-4 py-3"><div class="h-4 w-24 bg-gray-200 rounded font-mono"></div></td>
              <td class="px-4 py-3">
                <div class="h-4 w-24 bg-gray-200 rounded mb-1"></div>
                <div class="h-3 w-16 bg-gray-200 rounded"></div>
              </td>
              <td class="px-4 py-3"><div class="h-4 w-40 bg-gray-200 rounded"></div></td>
              <td class="px-4 py-3 text-right"><div class="h-4 w-24 bg-gray-200 rounded ml-auto"></div></td>
              <td class="px-4 py-3 text-right"><div class="h-4 w-24 bg-gray-200 rounded ml-auto"></div></td>
              <td class="px-4 py-3 text-right"><div class="h-4 w-24 bg-gray-200 rounded ml-auto"></div></td>
              <td class="px-4 py-3"><div class="w-24 bg-gray-200 rounded-full h-2 mx-auto"></div></td>
              <td class="px-4 py-3 text-center"><div class="h-5 w-16 bg-gray-200 rounded-full mx-auto"></div></td>
              <td class="px-4 py-3"><div class="h-4 w-16 bg-gray-200 rounded"></div></td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <div class="h-7 w-7 bg-gray-200 rounded"></div>
                  <div class="h-7 w-7 bg-gray-200 rounded"></div>
                  <div class="h-7 w-7 bg-gray-200 rounded"></div>
                </div>
              </td>
            </tr>
          </template>
          <template v-else>
          <tr v-for="lay in layaways.data" :key="lay.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="font-mono text-xs font-semibold text-amber-700">{{ lay.reference_number }}</div>
              <div v-if="lay.sale" class="text-xs text-green-600 mt-0.5">Invoice: {{ lay.sale.invoice_number }}</div>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ lay.customer?.name }}</div>
              <div class="text-xs text-gray-500">{{ lay.customer?.phone }}</div>
            </td>
            <td class="px-4 py-3 max-w-[200px] truncate text-gray-700">{{ lay.item_description }}</td>
            <td class="px-4 py-3 text-right text-gray-900 font-medium">{{ fmtCurrency(lay.total_amount) }}</td>
            <td class="px-4 py-3 text-right text-green-700 font-medium">{{ fmtCurrency(lay.paid_amount) }}</td>
            <td class="px-4 py-3 text-right font-bold" :class="lay.balance_amount > 0 ? 'text-red-600' : 'text-green-600'">{{ fmtCurrency(lay.balance_amount) }}</td>
            <td class="px-4 py-3">
              <div class="w-24 mx-auto bg-gray-200 rounded-full h-2">
                <div class="bg-amber-500 h-2 rounded-full" :style="{ width: progressPct(lay) + '%' }"></div>
              </div>
              <div class="text-center text-xs text-gray-500 mt-1">{{ progressPct(lay) }}%</div>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="statusClass(lay.status)" class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full">
                {{ lay.status.charAt(0).toUpperCase() + lay.status.slice(1) }}
              </span>
              <div v-if="lay.sale_id" class="text-xs text-green-600 mt-1 font-medium">Collected</div>
            </td>
            <td class="px-4 py-3 text-xs text-gray-500">{{ fmtDate(lay.booking_date) }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <button @click="openDetail(lay)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="View / Payments">
                  <EyeIcon class="h-4 w-4" />
                </button>
                <button v-if="lay.status === 'active'" @click="openPayment(lay)" class="p-1.5 text-green-600 hover:bg-green-50 rounded" title="Record Payment">
                  <BanknotesIcon class="h-4 w-4" />
                </button>
                <!-- Issue Invoice: completed and not yet converted -->
                <button v-if="lay.status === 'completed' && !lay.sale_id" @click="openConvert(lay)" class="p-1.5 text-purple-600 hover:bg-purple-50 rounded" title="Issue Invoice / Hand Over">
                  <DocumentCheckIcon class="h-4 w-4" />
                </button>
                <!-- Print invoice if already converted -->
                <button v-if="lay.sale_id" @click="printLayawayInvoice(lay)" class="p-1.5 text-gray-600 hover:bg-gray-50 rounded" title="Print Invoice">
                  <PrinterIcon class="h-4 w-4" />
                </button>
                <button v-if="lay.status === 'active'" @click="openCancel(lay)" class="p-1.5 text-red-500 hover:bg-red-50 rounded" title="Cancel Layaway">
                  <XMarkIcon class="h-4 w-4" />
                </button>
                <button v-if="lay.status === 'cancelled' && lay.paid_amount > 0" @click="printCancellationSlip(lay)" class="p-1.5 text-gray-500 hover:bg-gray-50 rounded" title="Print Cancellation Slip">
                  <PrinterIcon class="h-4 w-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!layaways.data?.length">
            <td colspan="10" class="px-4 py-10 text-center text-gray-400 text-sm">No layaways found.</td>
          </tr>
          </template>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="layaways.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 text-sm text-gray-600">
        <span>Page {{ layaways.current_page }} of {{ layaways.last_page }}</span>
        <div class="flex gap-2">
          <button @click="loadLayaways(layaways.current_page - 1)" :disabled="layaways.current_page === 1" class="px-3 py-1 border rounded disabled:opacity-40">Prev</button>
          <button @click="loadLayaways(layaways.current_page + 1)" :disabled="layaways.current_page === layaways.last_page" class="px-3 py-1 border rounded disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- ─── Create Layaway Modal ─── -->
    <teleport to="body">
      <div v-if="showCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">New Layaway</h2>
            <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-5 w-5" /></button>
          </div>
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
              <select v-model="createForm.customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                <option value="">Select customer…</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} — {{ c.phone }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Item Description <span class="text-red-500">*</span></label>
              <input v-model="createForm.item_description" type="text" placeholder="e.g. 22K Gold Necklace 15g" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount (LKR) <span class="text-red-500">*</span></label>
                <input v-model.number="createForm.total_amount" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Initial Payment (LKR)</label>
                <input v-model.number="createForm.initial_payment" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Booking Date <span class="text-red-500">*</span></label>
                <input v-model="createForm.booking_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expected Completion</label>
                <input v-model="createForm.expected_by" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
              <select v-model="createForm.payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <option value="card">Card</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="createForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500"></textarea>
            </div>
            <div v-if="createForm.initial_payment > 0" class="flex items-center gap-2">
              <input id="create-sms" v-model="createForm.send_sms" type="checkbox" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
              <label for="create-sms" class="text-sm text-gray-700">Send SMS confirmation to customer</label>
            </div>
            <div v-if="createError" class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">{{ createError }}</div>
          </div>
          <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
            <button @click="showCreate = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button @click="saveLayaway" :disabled="creating" class="px-4 py-2 text-sm bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50">
              {{ creating ? 'Saving…' : 'Create Layaway' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- ─── Detail / Payments Modal ─── -->
    <teleport to="body">
      <div v-if="showDetail && selectedLayaway" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <div>
              <h2 class="text-lg font-bold text-gray-900">{{ selectedLayaway.reference_number }}</h2>
              <p class="text-sm text-gray-500">{{ selectedLayaway.item_description }}</p>
            </div>
            <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-5 w-5" /></button>
          </div>

          <!-- Converted banner -->
          <div v-if="selectedLayaway.sale_id" class="mx-5 mt-4 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between">
            <div class="flex items-center gap-2 text-green-800">
              <CheckCircleIcon class="h-5 w-5 text-green-600" />
              <span class="text-sm font-medium">Item collected — Invoice {{ selectedLayaway.sale?.invoice_number }}</span>
            </div>
            <button @click="printLayawayInvoice(selectedLayaway)" class="flex items-center gap-1 text-xs px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700">
              <PrinterIcon class="h-3.5 w-3.5" />
              Print Invoice
            </button>
          </div>

          <!-- Summary -->
          <div class="p-5 grid grid-cols-3 gap-4 border-b border-gray-100">
            <div class="text-center">
              <p class="text-xs text-gray-500">Total</p>
              <p class="text-lg font-bold text-gray-900">{{ fmtCurrency(selectedLayaway.total_amount) }}</p>
            </div>
            <div class="text-center">
              <p class="text-xs text-gray-500">Paid</p>
              <p class="text-lg font-bold text-green-600">{{ fmtCurrency(selectedLayaway.paid_amount) }}</p>
            </div>
            <div class="text-center">
              <p class="text-xs text-gray-500">Balance</p>
              <p class="text-lg font-bold" :class="selectedLayaway.balance_amount > 0 ? 'text-red-600' : 'text-green-600'">{{ fmtCurrency(selectedLayaway.balance_amount) }}</p>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="px-5 py-3 border-b border-gray-100">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
              <span>Progress</span>
              <span>{{ progressPct(selectedLayaway) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div class="bg-amber-500 h-3 rounded-full transition-all" :style="{ width: progressPct(selectedLayaway) + '%' }"></div>
            </div>
          </div>

          <!-- Customer & Dates -->
          <div class="px-5 py-4 grid grid-cols-2 gap-4 border-b border-gray-100 text-sm">
            <div>
              <span class="text-xs text-gray-500 block">Customer</span>
              <span class="font-medium">{{ selectedLayaway.customer?.name }}</span>
              <span class="text-gray-500 block text-xs">{{ selectedLayaway.customer?.phone }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block">Booked / Expected</span>
              <span class="font-medium">{{ fmtDate(selectedLayaway.booking_date) }}</span>
              <span class="text-gray-500 block text-xs">{{ selectedLayaway.expected_by ? fmtDate(selectedLayaway.expected_by) : 'No deadline' }}</span>
            </div>
          </div>

          <!-- Payment History -->
          <div class="p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-gray-900">Payment History</h3>
              <div class="flex gap-2">
                <button v-if="selectedLayaway.status === 'completed' && !selectedLayaway.sale_id" @click="openConvert(selectedLayaway)" class="flex items-center gap-1 text-sm px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                  <DocumentCheckIcon class="h-4 w-4" />
                  Issue Invoice
                </button>
                <button v-if="selectedLayaway.status === 'active'" @click="openPayment(selectedLayaway)" class="flex items-center gap-1 text-sm px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700">
                  <PlusIcon class="h-4 w-4" />
                  Record Payment
                </button>
              </div>
            </div>
            <div v-if="!selectedLayaway.payments?.length" class="text-center text-gray-400 py-6">No payments recorded yet.</div>
            <table v-else class="min-w-full text-sm divide-y divide-gray-200">
              <thead class="text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left">Receipt</th>
                  <th class="px-3 py-2 text-right">Amount</th>
                  <th class="px-3 py-2 text-center">Method</th>
                  <th class="px-3 py-2 text-left">Date</th>
                  <th class="px-3 py-2 text-center">SMS</th>
                  <th class="px-3 py-2 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="pmt in selectedLayaway.payments" :key="pmt.id">
                  <td class="px-3 py-2 font-mono text-xs text-amber-700">{{ pmt.receipt_number }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-green-700">{{ fmtCurrency(pmt.amount) }}</td>
                  <td class="px-3 py-2 text-center capitalize text-gray-600">{{ pmt.payment_method.replace('_', ' ') }}</td>
                  <td class="px-3 py-2 text-xs text-gray-600">{{ fmtDate(pmt.payment_date) }}</td>
                  <td class="px-3 py-2 text-center">
                    <span v-if="pmt.sms_sent" class="text-green-500 text-xs">✓ Sent</span>
                    <span v-else class="text-gray-400 text-xs">—</span>
                  </td>
                  <td class="px-3 py-2 text-right">
                    <button @click="printReceipt(pmt, selectedLayaway)" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Print Receipt">
                      <PrinterIcon class="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </teleport>

    <!-- ─── Record Payment Modal ─── -->
    <teleport to="body">
      <div v-if="showPayment && paymentTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Record Payment</h2>
            <button @click="showPayment = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-5 w-5" /></button>
          </div>
          <div class="p-5 space-y-4">
            <div class="bg-amber-50 rounded-lg p-3 text-sm">
              <p class="font-medium text-amber-900">{{ paymentTarget.reference_number }}</p>
              <p class="text-amber-700">{{ paymentTarget.item_description }}</p>
              <p class="text-amber-700 mt-1">Balance: <strong>{{ fmtCurrency(paymentTarget.balance_amount) }}</strong></p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount (LKR) <span class="text-red-500">*</span></label>
              <input v-model.number="payForm.amount" type="number" min="0.01" :max="paymentTarget.balance_amount" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Method <span class="text-red-500">*</span></label>
                <select v-model="payForm.payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="cheque">Cheque</option>
                  <option value="card">Card</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                <input v-model="payForm.payment_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <input v-model="payForm.notes" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" />
            </div>
            <div class="flex items-center gap-2">
              <input id="pay-sms" v-model="payForm.send_sms" type="checkbox" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
              <label for="pay-sms" class="text-sm text-gray-700">Send SMS to customer</label>
            </div>
            <div v-if="payError" class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">{{ payError }}</div>
          </div>
          <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
            <button @click="showPayment = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button @click="submitPayment" :disabled="paying" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
              {{ paying ? 'Processing…' : 'Record Payment' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- ─── Cancel Layaway Modal ─── -->
    <teleport to="body">
      <div v-if="showCancel && cancelTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
          <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <div>
              <h2 class="text-lg font-bold text-red-700">Cancel Layaway</h2>
              <p class="text-sm text-gray-500 mt-0.5">{{ cancelTarget.reference_number }} · {{ cancelTarget.customer?.name }}</p>
            </div>
            <button @click="showCancel = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-5 w-5" /></button>
          </div>

          <div class="p-5 space-y-4">
            <!-- Paid amount summary -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 space-y-1.5 text-sm">
              <div class="flex justify-between">
                <span class="text-red-700">Item</span>
                <span class="font-medium text-red-900">{{ cancelTarget.item_description }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-red-700">Total Agreed</span>
                <span class="font-medium text-red-900">{{ fmtCurrency(cancelTarget.total_amount) }}</span>
              </div>
              <div class="flex justify-between border-t border-red-200 pt-1.5 mt-1.5">
                <span class="text-red-700 font-semibold">Total Paid by Customer</span>
                <span class="text-lg font-bold text-red-900">{{ fmtCurrency(cancelTarget.paid_amount) }}</span>
              </div>
            </div>

            <!-- Refund type -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Refund Policy <span class="text-red-500">*</span></label>
              <div class="space-y-2">
                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="cancelForm.refund_type === 'full' ? 'border-green-400 bg-green-50' : 'border-gray-200 hover:bg-gray-50'">
                  <input type="radio" v-model="cancelForm.refund_type" value="full" class="mt-0.5 text-green-600" />
                  <div>
                    <p class="text-sm font-medium text-gray-800">Full Refund</p>
                    <p class="text-xs text-gray-500">Refund 100% of payments collected ({{ fmtCurrency(cancelTarget.paid_amount) }})</p>
                  </div>
                </label>
                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="cancelForm.refund_type === 'partial' ? 'border-amber-400 bg-amber-50' : 'border-gray-200 hover:bg-gray-50'">
                  <input type="radio" v-model="cancelForm.refund_type" value="partial" class="mt-0.5 text-amber-600" />
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">Partial Refund (Cancellation Fee)</p>
                    <p class="text-xs text-gray-500">Keep a cancellation fee, refund the rest</p>
                    <div v-if="cancelForm.refund_type === 'partial'" class="mt-2 flex items-center gap-2">
                      <span class="text-xs text-gray-600">Fee (LKR)</span>
                      <input v-model.number="cancelForm.cancellation_fee" type="number" min="0" :max="cancelTarget.paid_amount" step="0.01"
                        class="flex-1 border border-gray-300 rounded-lg px-2 py-1 text-sm focus:ring-amber-500 focus:border-amber-500" placeholder="0.00" />
                    </div>
                  </div>
                </label>
                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer" :class="cancelForm.refund_type === 'forfeit' ? 'border-red-400 bg-red-50' : 'border-gray-200 hover:bg-gray-50'">
                  <input type="radio" v-model="cancelForm.refund_type" value="forfeit" class="mt-0.5 text-red-600" />
                  <div>
                    <p class="text-sm font-medium text-gray-800">Forfeit All</p>
                    <p class="text-xs text-gray-500">Shop keeps all payments as income — no refund to customer</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- Refund method (only if refund > 0) -->
            <div v-if="cancelRefundAmount > 0" class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Refund Method <span class="text-red-500">*</span></label>
                <select v-model="cancelForm.refund_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cancellation Date</label>
                <input v-model="cancelForm.cancelled_at" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500" />
              </div>
            </div>

            <!-- Reason -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Cancellation</label>
              <textarea v-model="cancelForm.cancellation_reason" rows="2" placeholder="Optional note…"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500"></textarea>
            </div>

            <!-- Refund summary -->
            <div v-if="cancelForm.refund_type" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm space-y-1">
              <div class="flex justify-between text-gray-600">
                <span>Total Paid</span>
                <span>{{ fmtCurrency(cancelTarget.paid_amount) }}</span>
              </div>
              <div class="flex justify-between text-red-600">
                <span>Cancellation Fee (kept by shop)</span>
                <span>{{ fmtCurrency(cancelFeeAmount) }}</span>
              </div>
              <div class="flex justify-between font-bold text-gray-900 border-t border-gray-200 pt-1 mt-1">
                <span>Refund to Customer</span>
                <span :class="cancelRefundAmount > 0 ? 'text-green-700' : 'text-gray-500'">{{ fmtCurrency(cancelRefundAmount) }}</span>
              </div>
              <div v-if="cancelTarget.paid_amount > 0" class="text-xs text-blue-700 bg-blue-50 rounded p-2 mt-2">
                <strong>GL:</strong> Dr Customer Deposit (2200) {{ fmtCurrency(cancelTarget.paid_amount) }}
                <span v-if="cancelRefundAmount > 0"> → Cr Cash/Bank {{ fmtCurrency(cancelRefundAmount) }}</span>
                <span v-if="cancelFeeAmount > 0"> → Cr Cancellation Fee Income (4050) {{ fmtCurrency(cancelFeeAmount) }}</span>
              </div>
            </div>

            <div v-if="cancelError" class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">{{ cancelError }}</div>
          </div>

          <div class="flex items-center justify-between p-5 border-t border-gray-200">
            <button @click="showCancel = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Keep Layaway</button>
            <button @click="submitCancel" :disabled="!cancelForm.refund_type || cancelling"
              class="flex items-center gap-2 px-5 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 font-medium">
              <XMarkIcon class="h-4 w-4" />
              {{ cancelling ? 'Cancelling…' : 'Confirm Cancellation' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

    <!-- ─── Convert to Sale / Issue Invoice Modal ─── -->
    <teleport to="body">
      <div v-if="showConvert && convertTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
          <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <div>
              <h2 class="text-lg font-bold text-gray-900">Issue Invoice &amp; Hand Over</h2>
              <p class="text-sm text-gray-500 mt-0.5">Convert completed layaway to a sale record</p>
            </div>
            <button @click="showConvert = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="h-5 w-5" /></button>
          </div>

          <div class="p-5 space-y-4">
            <!-- Summary -->
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-purple-700 font-medium">Layaway</span>
                <span class="font-mono font-semibold text-purple-900">{{ convertTarget.reference_number }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-purple-700">Customer</span>
                <span class="font-medium text-purple-900">{{ convertTarget.customer?.name }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-purple-700">Item</span>
                <span class="font-medium text-purple-900">{{ convertTarget.item_description }}</span>
              </div>
              <div class="border-t border-purple-200 pt-2 mt-2 flex justify-between">
                <span class="text-purple-700 font-semibold">Total Amount</span>
                <span class="text-lg font-bold text-purple-900">{{ fmtCurrency(convertTarget.total_amount) }}</span>
              </div>
            </div>

            <!-- GL note -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
              <strong>GL effect:</strong> Customer Deposit (2200) Dr. → Sales Revenue (4000) Cr. for {{ fmtCurrency(convertTarget.total_amount) }}
              <br>A new invoice number will be auto-generated and the item stock will be decremented.
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                <select v-model="convertForm.payment_method" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500">
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="cheque">Cheque</option>
                  <option value="card">Card</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Collection Date <span class="text-red-500">*</span></label>
                <input v-model="convertForm.collected_at" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <input v-model="convertForm.notes" type="text" placeholder="Optional handover notes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500" />
            </div>

            <div v-if="convertError" class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">{{ convertError }}</div>
          </div>

          <div class="flex items-center justify-between p-5 border-t border-gray-200">
            <button @click="showConvert = false" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button @click="submitConvert" :disabled="converting" class="flex items-center gap-2 px-5 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 font-medium">
              <DocumentCheckIcon class="h-4 w-4" />
              {{ converting ? 'Processing…' : 'Issue Invoice & Hand Over' }}
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
import {
  PlusIcon, XMarkIcon, EyeIcon, BanknotesIcon, PrinterIcon,
  DocumentCheckIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'

// ── State ──────────────────────────────────────────────────────────────────
const layaways  = ref({ data: [], current_page: 1, last_page: 1 })
const customers = ref([])
const loading   = ref(false)

const filters = reactive({ status: '', from: '', to: '' })

const summary = computed(() => {
  const rows = layaways.value.data ?? []
  return {
    total:     rows.length,
    active:    rows.filter(r => r.status === 'active').length,
    completed: rows.filter(r => r.status === 'completed').length,
    balance:   rows.reduce((s, r) => s + (r.balance_amount ?? 0), 0),
  }
})

// Create modal
const showCreate  = ref(false)
const creating    = ref(false)
const createError = ref(null)
const createForm  = reactive({
  customer_id: '', item_description: '', total_amount: '', initial_payment: 0,
  booking_date: today(), expected_by: '', payment_method: 'cash', notes: '', send_sms: false,
})

// Detail modal
const showDetail      = ref(false)
const selectedLayaway = ref(null)

// Payment modal
const showPayment   = ref(false)
const paymentTarget = ref(null)
const paying        = ref(false)
const payError      = ref(null)
const payForm       = reactive({ amount: '', payment_method: 'cash', payment_date: today(), notes: '', send_sms: false })

// Convert modal
const showConvert   = ref(false)
const convertTarget = ref(null)
const converting    = ref(false)
const convertError  = ref(null)
const convertForm   = reactive({ payment_method: 'cash', collected_at: today(), notes: '' })

// Cancel modal
const showCancel    = ref(false)
const cancelTarget  = ref(null)
const cancelling    = ref(false)
const cancelError   = ref(null)
const cancelForm    = reactive({
  refund_type: '', cancellation_fee: 0, cancellation_reason: '',
  refund_method: 'cash', cancelled_at: today(),
})
const cancelFeeAmount = computed(() => {
  if (!cancelTarget.value) return 0
  if (cancelForm.refund_type === 'full')    return 0
  if (cancelForm.refund_type === 'forfeit') return cancelTarget.value.paid_amount
  if (cancelForm.refund_type === 'partial') return Math.min(Number(cancelForm.cancellation_fee) || 0, cancelTarget.value.paid_amount)
  return 0
})
const cancelRefundAmount = computed(() => {
  if (!cancelTarget.value) return 0
  return Math.max(0, cancelTarget.value.paid_amount - cancelFeeAmount.value)
})

// Branding
const branding = ref({ shop_name: '', logo_url: '' })

// ── Lifecycle ──────────────────────────────────────────────────────────────
onMounted(async () => {
  loadLayaways()
  loadCustomers()
  try {
    const { data } = await axios.get('/api/shop-branding')
    branding.value = data
  } catch (_) {}
})

// ── Data loading ───────────────────────────────────────────────────────────
async function loadLayaways(page = 1) {
  loading.value = true
  try {
    const params = { page, ...filters }
    const { data } = await axios.get('/api/layaways', { params })
    layaways.value = data
  } finally {
    loading.value = false
  }
}

async function loadCustomers() {
  const { data } = await axios.get('/api/customers/all')
  customers.value = data
}

function resetFilters() {
  Object.assign(filters, { status: '', from: '', to: '' })
  loadLayaways()
}

// ── Create ─────────────────────────────────────────────────────────────────
function openCreate() {
  Object.assign(createForm, {
    customer_id: '', item_description: '', total_amount: '', initial_payment: 0,
    booking_date: today(), expected_by: '', payment_method: 'cash', notes: '', send_sms: false,
  })
  createError.value = null
  showCreate.value  = true
}

async function saveLayaway() {
  createError.value = null
  if (!createForm.customer_id || !createForm.item_description || !createForm.total_amount || !createForm.booking_date) {
    createError.value = 'Please fill all required fields.'
    return
  }
  creating.value = true
  try {
    await axios.post('/api/layaways', { ...createForm })
    showCreate.value = false
    loadLayaways()
  } catch (e) {
    createError.value = e.response?.data?.message ?? 'Failed to create layaway.'
  } finally {
    creating.value = false
  }
}

// ── Detail ─────────────────────────────────────────────────────────────────
async function openDetail(lay) {
  const { data } = await axios.get(`/api/layaways/${lay.id}`)
  selectedLayaway.value = data
  showDetail.value      = true
}

// ── Payment ────────────────────────────────────────────────────────────────
function openPayment(lay) {
  paymentTarget.value = lay
  payError.value      = null
  Object.assign(payForm, { amount: '', payment_method: 'cash', payment_date: today(), notes: '', send_sms: false })
  showPayment.value   = true
}

async function submitPayment() {
  payError.value = null
  if (!payForm.amount || !payForm.payment_date) {
    payError.value = 'Amount and date are required.'
    return
  }
  paying.value = true
  try {
    const { data } = await axios.post(`/api/layaways/${paymentTarget.value.id}/pay`, { ...payForm })
    showPayment.value = false
    // Update detail modal if open
    if (showDetail.value && selectedLayaway.value?.id === paymentTarget.value.id) {
      selectedLayaway.value = data.layaway
    }
    printReceipt(data.payment, data.layaway)
    loadLayaways()
  } catch (e) {
    payError.value = e.response?.data?.message ?? 'Failed to record payment.'
  } finally {
    paying.value = false
  }
}

// ── Convert to Sale ────────────────────────────────────────────────────────
function openConvert(lay) {
  convertTarget.value = lay
  convertError.value  = null
  Object.assign(convertForm, { payment_method: 'cash', collected_at: today(), notes: '' })
  showConvert.value   = true
}

async function submitConvert() {
  convertError.value = null
  converting.value   = true
  try {
    const { data } = await axios.post(`/api/layaways/${convertTarget.value.id}/convert-to-sale`, { ...convertForm })
    showConvert.value = false

    // Update detail modal if open
    if (showDetail.value && selectedLayaway.value?.id === convertTarget.value.id) {
      selectedLayaway.value = data.layaway
    }

    loadLayaways()

    // Auto-print the full layaway invoice
    printLayawayInvoiceFromData(data.layaway, data.sale)
  } catch (e) {
    convertError.value = e.response?.data?.message ?? 'Failed to issue invoice.'
  } finally {
    converting.value = false
  }
}

// ── Cancel ─────────────────────────────────────────────────────────────────
function openCancel(lay) {
  cancelTarget.value = lay
  cancelError.value  = null
  Object.assign(cancelForm, {
    refund_type: lay.paid_amount > 0 ? '' : 'forfeit',
    cancellation_fee: 0, cancellation_reason: '',
    refund_method: 'cash', cancelled_at: today(),
  })
  showCancel.value = true
}

async function submitCancel() {
  cancelError.value = null
  if (!cancelForm.refund_type) {
    cancelError.value = 'Please select a refund policy.'
    return
  }
  cancelling.value = true
  try {
    const payload = {
      refund_type:         cancelForm.refund_type,
      cancellation_fee:    cancelFeeAmount.value,
      cancellation_reason: cancelForm.cancellation_reason,
      refund_method:       cancelForm.refund_method,
      cancelled_at:        cancelForm.cancelled_at,
    }
    const { data } = await axios.post(`/api/layaways/${cancelTarget.value.id}/cancel`, payload)
    showCancel.value = false
    loadLayaways()
    if (showDetail.value && selectedLayaway.value?.id === cancelTarget.value.id) {
      selectedLayaway.value = data
    }
    printCancellationSlip(data)
  } catch (e) {
    cancelError.value = e.response?.data?.message ?? 'Failed to cancel layaway.'
  } finally {
    cancelling.value = false
  }
}

function printCancellationSlip(lay) {
  if (!lay.payments) {
    axios.get(`/api/layaways/${lay.id}`).then(({ data }) => _doCancelPrint(data))
  } else {
    _doCancelPrint(lay)
  }
}

function _doCancelPrint(layaway) {
  const shop     = branding.value.shop_name || 'Jewellery Shop'
  const logoHtml = branding.value.logo_url
    ? `<img src="${branding.value.logo_url}" alt="logo" style="height:70px;max-width:220px;object-fit:contain">`
    : ''
  const customer = layaway.customer ?? {}
  const payments = layaway.payments ?? []

  const refundTypeLabel = { full: 'Full Refund', partial: 'Partial Refund', forfeit: 'Forfeit All' }[layaway.refund_type] ?? '—'
  const refundMethodLabel = (layaway.refund_method ?? '').replace('_', ' ')

  const pmtRows = payments.map(p => `
    <tr>
      <td>${p.receipt_number}</td>
      <td>${fmtDate(p.payment_date)}</td>
      <td style="text-transform:capitalize">${(p.payment_method ?? '').replace('_', ' ')}</td>
      <td style="text-align:right;font-weight:bold">LKR ${Number(p.amount).toFixed(2)}</td>
    </tr>`).join('')

  const html = `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Cancellation Slip ${layaway.reference_number}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Arial,sans-serif;font-size:12px;color:#1a1a1a;padding:20px}
  @page{size:A5 landscape;margin:12mm}
  .cancelled-badge{display:inline-block;background:#fee2e2;color:#991b1b;border:2px solid #fca5a5;border-radius:6px;padding:4px 14px;font-size:15px;font-weight:bold;letter-spacing:1px;margin-bottom:4px}
  table{width:100%;border-collapse:collapse;margin-top:8px}
  th{background:#f3f4f6;color:#374151;padding:6px 8px;text-align:left;font-size:11px;border:1px solid #e5e7eb}
  td{padding:5px 8px;border:1px solid #e5e7eb;font-size:11px}
  .label{color:#6b7280;font-size:10px}
  .divider{border-top:1px solid #e5e7eb;margin:10px 0}
  .summary-box{background:#fef2f2;border:2px solid #fca5a5;border-radius:8px;padding:10px 16px}
  .refund-box{background:#f0fdf4;border:2px solid #86efac;border-radius:8px;padding:10px 16px}
</style></head>
<body>
<div style="display:flex;justify-content:space-between;align-items:flex-start">
  <div>${logoHtml}<div style="font-size:18px;font-weight:bold;margin-top:6px">${shop}</div></div>
  <div style="text-align:right">
    <div class="cancelled-badge">✗ CANCELLED</div>
    <div class="label" style="margin-top:6px">Layaway Reference</div>
    <div style="font-size:15px;font-weight:bold;font-family:monospace;color:#991b1b">${layaway.reference_number}</div>
    <div class="label" style="margin-top:4px">Cancelled On</div>
    <div style="font-weight:bold">${layaway.cancelled_at ? fmtDate(layaway.cancelled_at) : fmtDate(new Date())}</div>
  </div>
</div>

<div class="divider"></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div>
    <div class="label">Customer</div>
    <div style="font-weight:bold;font-size:13px">${customer.name ?? ''}</div>
    <div style="color:#6b7280">${customer.phone ?? ''}</div>
    <div style="color:#6b7280">${customer.email ?? ''}</div>
  </div>
  <div>
    <div class="label">Item Description</div>
    <div style="font-weight:bold">${layaway.item_description}</div>
    <div class="label" style="margin-top:4px">Booking Date</div>
    <div>${fmtDate(layaway.booking_date)}</div>
    ${layaway.cancellation_reason ? `<div class="label" style="margin-top:4px">Reason</div><div style="color:#6b7280">${layaway.cancellation_reason}</div>` : ''}
  </div>
</div>

<div class="divider"></div>

<div style="font-weight:bold;margin-bottom:6px;color:#374151">Payment History</div>
<table>
  <tr>
    <th>Receipt No</th><th>Date</th><th>Method</th><th style="text-align:right">Amount</th>
  </tr>
  ${pmtRows || '<tr><td colspan="4" style="text-align:center;color:#9ca3af">No payments recorded</td></tr>'}
</table>

<div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div class="summary-box">
    <div style="font-size:10px;color:#991b1b;font-weight:bold;margin-bottom:6px">CANCELLATION SUMMARY</div>
    <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:11px">
      <span>Total Agreed</span>
      <span>LKR ${Number(layaway.total_amount).toFixed(2)}</span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:11px">
      <span>Total Paid</span>
      <span>LKR ${Number(layaway.paid_amount).toFixed(2)}</span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:11px;color:#991b1b">
      <span>Cancellation Fee</span>
      <span>LKR ${Number(layaway.cancellation_fee ?? 0).toFixed(2)}</span>
    </div>
    <div style="font-size:10px;color:#6b7280;margin-top:4px">Policy: ${refundTypeLabel}</div>
  </div>
  <div class="refund-box">
    <div style="font-size:10px;color:#166534;font-weight:bold;margin-bottom:6px">REFUND TO CUSTOMER</div>
    <div style="font-size:22px;font-weight:bold;color:#166534">LKR ${Number(layaway.refund_amount ?? 0).toFixed(2)}</div>
    ${(layaway.refund_amount ?? 0) > 0 ? `<div style="font-size:10px;color:#374151;margin-top:4px">Via: ${refundMethodLabel}</div>` : '<div style="font-size:10px;color:#6b7280;margin-top:4px">No refund applicable</div>'}
  </div>
</div>

<div style="margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:40px">
  <div style="border-top:1px solid #374151;padding-top:4px;text-align:center;font-size:10px;color:#6b7280">Customer Signature</div>
  <div style="border-top:1px solid #374151;padding-top:4px;text-align:center;font-size:10px;color:#6b7280">Authorised By</div>
</div>
</body></html>`

  popup(html, 900, 620)
}

// ── Print: Payment Receipt (80mm thermal) ─────────────────────────────────
function printReceipt(payment, layaway) {
  const shop     = branding.value.shop_name || 'Jewellery Shop'
  const logoHtml = branding.value.logo_url
    ? `<img src="${branding.value.logo_url}" alt="logo" style="height:60px;max-width:200px;object-fit:contain;display:block;margin:0 auto 8px">`
    : ''

  const customer  = layaway.customer ?? {}
  const paidSoFar = Number(layaway.paid_amount ?? 0).toFixed(2)
  const balance   = Number(layaway.balance_amount ?? 0).toFixed(2)

  const html = `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Receipt ${payment.receipt_number}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Arial,sans-serif;font-size:12px;color:#1a1a1a}
  @page{size:80mm auto;margin:4mm}
  .center{text-align:center}.bold{font-weight:bold}
  .sep{border-top:1px dashed #888;margin:6px 0}
  .row{display:flex;justify-content:space-between;padding:2px 0}
  .total-row{display:flex;justify-content:space-between;padding:4px 0;font-size:14px;font-weight:bold;border-top:2px solid #000;margin-top:4px}
  .highlight{background:#fef9c3;padding:4px 6px;border-radius:4px}
</style></head>
<body>
${logoHtml}
<div class="center bold" style="font-size:15px">${shop}</div>
<div class="center" style="font-size:11px;color:#555">Layaway Payment Receipt</div>
<div class="sep"></div>
<div class="row"><span>Receipt No:</span><span class="bold">${payment.receipt_number}</span></div>
<div class="row"><span>Layaway Ref:</span><span class="bold">${layaway.reference_number}</span></div>
<div class="row"><span>Customer:</span><span>${customer.name ?? ''}</span></div>
<div class="row"><span>Phone:</span><span>${customer.phone ?? ''}</span></div>
<div class="row"><span>Date:</span><span>${fmtDate(payment.payment_date)}</span></div>
<div class="sep"></div>
<div class="row"><span>Item:</span><span>${layaway.item_description}</span></div>
<div class="sep"></div>
<div class="row"><span>Total Amount:</span><span>LKR ${Number(layaway.total_amount).toFixed(2)}</span></div>
<div class="row"><span>Payment Amount:</span><span class="bold">LKR ${Number(payment.amount).toFixed(2)}</span></div>
<div class="row"><span>Method:</span><span>${(payment.payment_method ?? '').replace('_', ' ')}</span></div>
<div class="sep"></div>
<div class="row"><span>Total Paid:</span><span>LKR ${paidSoFar}</span></div>
<div class="total-row highlight"><span>Balance Due:</span><span>LKR ${balance}</span></div>
${payment.notes ? `<div class="sep"></div><div class="row"><span>Note:</span><span>${payment.notes}</span></div>` : ''}
<div class="sep"></div>
<div class="center" style="font-size:10px;color:#666;margin-top:8px">Thank you for your payment!<br>Please keep this receipt for your records.</div>
</body></html>`

  popup(html, 380, 600)
}

// ── Print: Full Layaway Invoice (A5) ──────────────────────────────────────
function printLayawayInvoice(lay) {
  if (!lay.payments) {
    axios.get(`/api/layaways/${lay.id}`).then(({ data }) => printLayawayInvoiceFromData(data, data.sale))
  } else {
    printLayawayInvoiceFromData(lay, lay.sale)
  }
}

function printLayawayInvoiceFromData(layaway, sale) {
  const shop     = branding.value.shop_name || 'Jewellery Shop'
  const logoHtml = branding.value.logo_url
    ? `<img src="${branding.value.logo_url}" alt="logo" style="height:70px;max-width:220px;object-fit:contain">`
    : ''

  const customer  = layaway.customer ?? {}
  const payments  = layaway.payments ?? []
  const invoiceNo = sale?.invoice_number ?? '—'

  const pmtRows = payments.map(p => `
    <tr>
      <td>${p.receipt_number}</td>
      <td>${fmtDate(p.payment_date)}</td>
      <td style="text-transform:capitalize">${(p.payment_method ?? '').replace('_', ' ')}</td>
      <td style="text-align:right;font-weight:bold">LKR ${Number(p.amount).toFixed(2)}</td>
    </tr>`).join('')

  const html = `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Invoice ${invoiceNo}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:Arial,sans-serif;font-size:12px;color:#1a1a1a;padding:20px}
  @page{size:A5 landscape;margin:12mm}
  h1{font-size:20px;color:#92400e}
  table{width:100%;border-collapse:collapse;margin-top:8px}
  th{background:#fef3c7;color:#92400e;padding:6px 8px;text-align:left;font-size:11px;border:1px solid #fde68a}
  td{padding:5px 8px;border:1px solid #e5e7eb;font-size:11px}
  .label{color:#6b7280;font-size:10px}
  .divider{border-top:1px solid #e5e7eb;margin:10px 0}
  .total-box{background:#fef9c3;border:2px solid #fbbf24;border-radius:8px;padding:10px 16px;display:inline-block}
  .collected-badge{background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:bold;display:inline-block}
</style></head>
<body>
<div style="display:flex;justify-content:space-between;align-items:flex-start">
  <div>${logoHtml}<div style="font-size:18px;font-weight:bold;margin-top:6px">${shop}</div></div>
  <div style="text-align:right">
    <h1>LAYAWAY INVOICE</h1>
    <div class="label">Invoice No</div>
    <div style="font-size:15px;font-weight:bold;color:#1f2937">${invoiceNo}</div>
    <div class="label" style="margin-top:4px">Layaway Ref</div>
    <div style="font-weight:bold;font-family:monospace;color:#92400e">${layaway.reference_number}</div>
    ${sale ? `<div class="collected-badge" style="margin-top:8px">✓ Collected</div>` : ''}
  </div>
</div>

<div class="divider"></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div>
    <div class="label">Customer</div>
    <div style="font-weight:bold;font-size:13px">${customer.name ?? ''}</div>
    <div style="color:#6b7280">${customer.phone ?? ''}</div>
    <div style="color:#6b7280">${customer.email ?? ''}</div>
  </div>
  <div>
    <div class="label">Booking Date</div>
    <div style="font-weight:bold">${fmtDate(layaway.booking_date)}</div>
    ${layaway.expected_by ? `<div class="label" style="margin-top:4px">Expected By</div><div>${fmtDate(layaway.expected_by)}</div>` : ''}
    ${layaway.collected_at ? `<div class="label" style="margin-top:4px">Collected On</div><div style="color:#059669;font-weight:bold">${fmtDate(layaway.collected_at)}</div>` : ''}
  </div>
</div>

<div class="divider"></div>

<table>
  <tr>
    <th>Item Description</th>
    <th style="text-align:right">Total Amount</th>
  </tr>
  <tr>
    <td>${layaway.item_description}</td>
    <td style="text-align:right;font-weight:bold">LKR ${Number(layaway.total_amount).toFixed(2)}</td>
  </tr>
</table>

<div style="margin-top:16px">
  <div style="font-weight:bold;margin-bottom:6px;color:#374151">Payment History</div>
  <table>
    <tr>
      <th>Receipt No</th>
      <th>Date</th>
      <th>Method</th>
      <th style="text-align:right">Amount</th>
    </tr>
    ${pmtRows || '<tr><td colspan="4" style="text-align:center;color:#9ca3af">No payments</td></tr>'}
  </table>
</div>

<div style="margin-top:16px;display:flex;justify-content:space-between;align-items:flex-end">
  <div style="font-size:10px;color:#9ca3af;max-width:200px">Thank you for shopping with us!<br>Items collected cannot be returned.</div>
  <div>
    <div class="total-box">
      <div style="font-size:10px;color:#92400e">TOTAL PAID</div>
      <div style="font-size:20px;font-weight:bold;color:#1f2937">LKR ${Number(layaway.total_amount).toFixed(2)}</div>
    </div>
  </div>
</div>

<div style="margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:40px">
  <div style="border-top:1px solid #374151;padding-top:4px;text-align:center;font-size:10px;color:#6b7280">Customer Signature</div>
  <div style="border-top:1px solid #374151;padding-top:4px;text-align:center;font-size:10px;color:#6b7280">Authorised By</div>
</div>
</body></html>`

  popup(html, 900, 620)
}

// ── Helpers ────────────────────────────────────────────────────────────────
function popup(html, w, h) {
  const win = window.open('', '_blank', `width=${w},height=${h}`)
  win.document.write(html)
  win.document.close()
  win.focus()
  setTimeout(() => { win.print(); win.close() }, 700)
}

function fmtCurrency(v) {
  return 'LKR ' + Number(v ?? 0).toLocaleString('en-LK', { minimumFractionDigits: 2 })
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function today() {
  return new Date().toISOString().slice(0, 10)
}

function progressPct(lay) {
  if (!lay.total_amount) return 0
  return Math.min(100, Math.round((lay.paid_amount / lay.total_amount) * 100))
}

function statusClass(status) {
  return {
    active:    'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    pending:   'bg-yellow-100 text-yellow-700',
  }[status] ?? 'bg-gray-100 text-gray-700'
}
</script>
