<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Salary Payments</h2>
        <p class="text-sm text-gray-500 mt-0.5">Process payroll with EPF/ETF deductions — auto-posts GL journal entries</p>
      </div>
      <button @click="openPay" class="btn-primary flex items-center gap-2">
        <BanknotesIcon class="w-4 h-4" /> Pay Salary
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex flex-wrap items-center gap-3">
      <select v-model="empFilter" class="form-input w-52" @change="fetchPayments">
        <option value="">All employees</option>
        <option v-for="e in allEmployees" :key="e.id" :value="e.id">{{ e.employee_number }} – {{ e.name }}</option>
      </select>
      <input v-model="dateFrom" type="date" class="form-input w-36" @change="fetchPayments" title="From" />
      <span class="text-gray-400 text-xs">to</span>
      <input v-model="dateTo" type="date" class="form-input w-36" @change="fetchPayments" title="To" />
      <div class="flex gap-2 ml-auto">
        <button @click="activeTab = 'payments'" :class="tabCls('payments')">Payments</button>
        <button @click="activeTab = 'advances'; fetchAdvances()" :class="tabCls('advances')">Advances</button>
        <button @click="activeTab = 'summary'; fetchSummary()" :class="tabCls('summary')">Payroll Summary</button>
        <button @click="activeTab = 'settings'; loadSettings()" :class="tabCls('settings')">EPF/ETF Rates</button>
      </div>
    </div>

    <!-- ── Payments tab ── -->
    <template v-if="activeTab === 'payments'">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
            <DocumentTextIcon class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Payments</p>
            <p class="text-2xl font-bold text-gray-800">{{ payments.total ?? 0 }}</p>
          </div>
        </div>
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
            <BanknotesIcon class="w-5 h-5 text-amber-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Net Paid</p>
            <p class="text-lg font-bold text-amber-700">LKR {{ lkr(totalPaid) }}</p>
          </div>
        </div>
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <BuildingLibraryIcon class="w-5 h-5 text-red-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">EPF Employee</p>
            <p class="text-lg font-bold text-red-700">LKR {{ lkr(totalEpfEmployee) }}</p>
          </div>
        </div>
        <div class="card flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
            <BuildingLibraryIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">EPF+ETF Employer</p>
            <p class="text-lg font-bold text-purple-700">LKR {{ lkr(totalEmployerContrib) }}</p>
          </div>
        </div>
      </div>

      <!-- Payments table -->
      <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[1000px]">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th w-36">Payment #</th>
                <th class="table-th">Employee</th>
                <th class="table-th w-44">Period</th>
                <th class="table-th w-24">Date</th>
                <th class="table-th w-28 text-right">Gross</th>
                <th class="table-th w-24 text-right">EPF Emp.</th>
                <th class="table-th w-24 text-right">Other Ded.</th>
                <th class="table-th w-28 text-right">Net Pay</th>
                <th class="table-th w-28 text-right">EPF Emr.</th>
                <th class="table-th w-24 text-right">ETF</th>
                <th class="table-th w-20 text-center">GL</th>
                <th class="table-th w-28">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="12" class="table-td text-center py-10 text-gray-400">
                  <div class="flex items-center justify-center gap-2"><ArrowPathIcon class="w-4 h-4 animate-spin" /> Loading…</div>
                </td>
              </tr>
              <template v-else>
                <tr v-for="p in payments.data" :key="p.id" class="hover:bg-gray-50">
                  <td class="table-td font-mono text-xs font-semibold text-gray-700 bg-gray-50">{{ p.payment_number }}</td>
                  <td class="table-td">
                    <p class="font-medium text-gray-800 text-sm">{{ p.employee?.name }}</p>
                    <p class="text-xs text-gray-400">{{ p.employee?.designation }}</p>
                  </td>
                  <td class="table-td text-xs text-gray-500">{{ fmtDate(p.period_from) }} – {{ fmtDate(p.period_to) }}</td>
                  <td class="table-td text-xs text-gray-600">{{ fmtDate(p.payment_date) }}</td>
                  <td class="table-td text-right font-mono text-sm">{{ lkr(p.gross_salary || p.basic_salary) }}</td>
                  <td class="table-td text-right font-mono text-sm text-red-600">
                    {{ p.epf_employee > 0 ? lkr(p.epf_employee) : '—' }}
                  </td>
                  <td class="table-td text-right font-mono text-sm text-orange-600">
                    {{ p.deductions > 0 ? lkr(p.deductions) : '—' }}
                  </td>
                  <td class="table-td text-right font-bold text-gray-800">LKR {{ lkr(p.net_salary) }}</td>
                  <td class="table-td text-right font-mono text-sm text-purple-700">
                    {{ p.epf_employer > 0 ? lkr(p.epf_employer) : '—' }}
                  </td>
                  <td class="table-td text-right font-mono text-sm text-indigo-700">
                    {{ p.etf_employer > 0 ? lkr(p.etf_employer) : '—' }}
                  </td>
                  <td class="table-td text-center">
                    <span v-if="p.journal_entry_id" class="badge bg-green-100 text-green-700 text-xs cursor-pointer hover:bg-green-200" @click="goToGL(p)" title="View in General Ledger">✓ Posted</span>
                    <span v-else class="badge bg-yellow-100 text-yellow-700 text-xs">Draft</span>
                  </td>
                  <td class="table-td">
                    <div class="flex gap-1.5">
                      <button @click="printPayslip(p)" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200 whitespace-nowrap">
                        <PrinterIcon class="w-3.5 h-3.5" /> Payslip
                      </button>
                      <button v-if="p.status !== 'paid'" @click="deletePayment(p)"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                        <TrashIcon class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!payments.data?.length">
                  <td colspan="12" class="table-td text-center text-gray-400 py-10">No salary payments found</td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
          <span>{{ payments.total ?? 0 }} records</span>
          <div class="flex gap-2">
            <button @click="page--; fetchPayments()" :disabled="page <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
            <button @click="page++; fetchPayments()" :disabled="page >= payments.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
          </div>
        </div>
      </div>
    </template>

    <!-- ── Advances tab ── -->
    <template v-else-if="activeTab === 'advances'">
      <div class="flex items-center justify-between mb-2">
        <p class="text-sm text-gray-500">Salary advances given to employees — pending advances are recovered on the next salary payment.</p>
        <button @click="openAdvanceModal" class="btn-primary flex items-center gap-2">
          <BanknotesIcon class="w-4 h-4" /> Give Advance
        </button>
      </div>
      <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[800px]">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Advance #</th>
                <th class="table-th">Employee</th>
                <th class="table-th">Date</th>
                <th class="table-th text-right">Amount</th>
                <th class="table-th">Reason</th>
                <th class="table-th text-center">Status</th>
                <th class="table-th">Recovered In</th>
                <th class="table-th">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="a in advancesData.data" :key="a.id" class="hover:bg-gray-50">
                <td class="table-td font-mono text-xs font-semibold text-gray-700 bg-gray-50">{{ a.advance_number }}</td>
                <td class="table-td">
                  <p class="font-medium text-sm">{{ a.employee?.name }}</p>
                  <p class="text-xs text-gray-400">{{ a.employee?.employee_number }}</p>
                </td>
                <td class="table-td text-xs text-gray-600">{{ fmtDate(a.given_date) }}</td>
                <td class="table-td text-right font-bold text-gray-800">LKR {{ lkr(a.amount) }}</td>
                <td class="table-td text-xs text-gray-500">{{ a.reason || '—' }}</td>
                <td class="table-td text-center">
                  <span :class="{
                    'bg-yellow-100 text-yellow-700': a.status === 'pending',
                    'bg-green-100 text-green-700': a.status === 'deducted',
                    'bg-gray-100 text-gray-400': a.status === 'cancelled',
                  }" class="badge text-xs capitalize">{{ a.status }}</span>
                </td>
                <td class="table-td text-xs text-gray-500 font-mono">{{ a.salary_payment?.payment_number || '—' }}</td>
                <td class="table-td">
                  <button v-if="a.status === 'pending'" @click="cancelAdvance(a)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                    <TrashIcon class="w-3.5 h-3.5" /> Cancel
                  </button>
                </td>
              </tr>
              <tr v-if="!advancesData.data?.length">
                <td colspan="8" class="table-td text-center text-gray-400 py-10">No advances found</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-4 py-3 border-t flex justify-between text-sm text-gray-600">
          <span>{{ advancesData.total ?? 0 }} records</span>
          <div class="flex gap-2">
            <button @click="advancePage--; fetchAdvances()" :disabled="advancePage <= 1" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Prev</button>
            <button @click="advancePage++; fetchAdvances()" :disabled="advancePage >= advancesData.last_page" class="btn-secondary py-1 px-3 text-xs disabled:opacity-40">Next</button>
          </div>
        </div>
      </div>
    </template>

    <!-- ── Payroll Summary tab ── -->
    <template v-else-if="activeTab === 'summary'">
      <div v-if="summaryData" class="space-y-4">
        <div class="grid grid-cols-4 gap-4">
          <div class="card text-center bg-amber-50">
            <p class="text-xs text-amber-600 uppercase tracking-wide">Net Payroll</p>
            <p class="text-2xl font-bold text-amber-800">LKR {{ lkr(summaryData.grand_total) }}</p>
          </div>
          <div class="card text-center bg-red-50">
            <p class="text-xs text-red-600 uppercase tracking-wide">EPF Employee Total</p>
            <p class="text-xl font-bold text-red-700">LKR {{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_epf_employee||0),0)) }}</p>
          </div>
          <div class="card text-center bg-purple-50">
            <p class="text-xs text-purple-600 uppercase tracking-wide">EPF Employer Total</p>
            <p class="text-xl font-bold text-purple-700">LKR {{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_epf_employer||0),0)) }}</p>
          </div>
          <div class="card text-center bg-indigo-50">
            <p class="text-xs text-indigo-600 uppercase tracking-wide">ETF Employer Total</p>
            <p class="text-xl font-bold text-indigo-700">LKR {{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_etf_employer||0),0)) }}</p>
          </div>
        </div>
        <div class="card p-0 overflow-hidden">
          <div class="px-5 py-3 border-b bg-gray-50">
            <span class="font-semibold text-gray-700 text-sm">Payroll Summary by Employee</span>
          </div>
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Employee</th>
                <th class="table-th">Designation</th>
                <th class="table-th w-20 text-center">Payments</th>
                <th class="table-th w-32 text-right">Gross</th>
                <th class="table-th w-28 text-right">EPF Emp.</th>
                <th class="table-th w-28 text-right">EPF Emr.</th>
                <th class="table-th w-24 text-right">ETF</th>
                <th class="table-th w-28 text-right">Other Ded.</th>
                <th class="table-th w-32 text-right">Net Paid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in summaryData.rows" :key="row.id" class="hover:bg-gray-50">
                <td class="table-td">
                  <p class="font-semibold text-gray-800">{{ row.name }}</p>
                  <p class="text-xs font-mono text-gray-400">{{ row.employee_number }}</p>
                </td>
                <td class="table-td text-gray-500 text-xs">{{ row.designation ?? '—' }}</td>
                <td class="table-td text-center"><span class="badge bg-gray-100 text-gray-600">{{ row.payment_count }}</span></td>
                <td class="table-td text-right font-mono">{{ lkr(row.total_basic) }}</td>
                <td class="table-td text-right font-mono text-red-600">{{ lkr(row.total_epf_employee) }}</td>
                <td class="table-td text-right font-mono text-purple-700">{{ lkr(row.total_epf_employer) }}</td>
                <td class="table-td text-right font-mono text-indigo-700">{{ lkr(row.total_etf_employer) }}</td>
                <td class="table-td text-right font-mono text-orange-600">{{ lkr(row.total_deductions) }}</td>
                <td class="table-td text-right font-bold text-gray-800">LKR {{ lkr(row.total_net) }}</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-bold">
              <tr>
                <td class="table-td" colspan="3">Grand Total</td>
                <td class="table-td text-right font-mono">{{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_basic),0)) }}</td>
                <td class="table-td text-right font-mono text-red-600">{{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_epf_employee||0),0)) }}</td>
                <td class="table-td text-right font-mono text-purple-700">{{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_epf_employer||0),0)) }}</td>
                <td class="table-td text-right font-mono text-indigo-700">{{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_etf_employer||0),0)) }}</td>
                <td class="table-td text-right font-mono text-orange-600">{{ lkr(summaryData.rows?.reduce((s,r)=>s+Number(r.total_deductions),0)) }}</td>
                <td class="table-td text-right text-amber-700">LKR {{ lkr(summaryData.grand_total) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div v-else class="card text-center py-16 text-gray-400">
        <ArrowPathIcon class="w-6 h-6 animate-spin mx-auto mb-2" /> Loading summary…
      </div>
    </template>

    <!-- ── EPF/ETF Settings tab ── -->
    <template v-else-if="activeTab === 'settings'">
      <div class="grid grid-cols-2 gap-6">

        <!-- Set new rates form -->
        <div class="card space-y-4">
          <h3 class="font-semibold text-gray-800">Set New EPF / ETF Rates</h3>
          <p class="text-xs text-gray-400">Sri Lanka defaults: EPF Employee 8%, EPF Employer 12%, ETF Employer 3%. Setting a new rate deactivates the previous one.</p>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="form-label">EPF Employee % *</label>
              <input v-model.number="rateForm.epf_employee_rate" type="number" min="0" max="100" step="0.01" class="form-input" />
              <p class="text-xs text-gray-400 mt-1">Deducted from employee</p>
            </div>
            <div>
              <label class="form-label">EPF Employer % *</label>
              <input v-model.number="rateForm.epf_employer_rate" type="number" min="0" max="100" step="0.01" class="form-input" />
              <p class="text-xs text-gray-400 mt-1">Employer pays to fund</p>
            </div>
            <div>
              <label class="form-label">ETF Employer % *</label>
              <input v-model.number="rateForm.etf_employer_rate" type="number" min="0" max="100" step="0.01" class="form-input" />
              <p class="text-xs text-gray-400 mt-1">Employer pays to fund</p>
            </div>
          </div>
          <div>
            <label class="form-label">Effective From *</label>
            <input v-model="rateForm.effective_from" type="date" class="form-input" />
          </div>
          <div>
            <label class="form-label">Notes</label>
            <input v-model="rateForm.notes" class="form-input" placeholder="e.g. Updated per gazette notification..." />
          </div>
          <p v-if="rateError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ rateError }}</p>
          <button @click="saveRates" :disabled="rateSaving" class="btn-primary">{{ rateSaving ? 'Saving…' : 'Save Rates' }}</button>
        </div>

        <!-- Current active rates -->
        <div class="space-y-4">
          <div v-if="currentRates" class="card border-2 border-gold-300 bg-gold-50/30 space-y-3">
            <div class="flex items-center gap-2">
              <span class="badge bg-green-100 text-green-700 text-xs">Active</span>
              <h3 class="font-semibold text-gray-800">Current Rates</h3>
              <span class="text-xs text-gray-400">Effective {{ fmtDate(currentRates.effective_from) }}</span>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center">
              <div class="bg-red-50 rounded-xl p-3">
                <p class="text-xs text-red-500 uppercase tracking-wider">EPF Employee</p>
                <p class="text-2xl font-bold text-red-700">{{ currentRates.epf_employee_rate }}%</p>
              </div>
              <div class="bg-purple-50 rounded-xl p-3">
                <p class="text-xs text-purple-500 uppercase tracking-wider">EPF Employer</p>
                <p class="text-2xl font-bold text-purple-700">{{ currentRates.epf_employer_rate }}%</p>
              </div>
              <div class="bg-indigo-50 rounded-xl p-3">
                <p class="text-xs text-indigo-500 uppercase tracking-wider">ETF Employer</p>
                <p class="text-2xl font-bold text-indigo-700">{{ currentRates.etf_employer_rate }}%</p>
              </div>
            </div>
          </div>

          <!-- Rate history -->
          <div class="card p-0 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-700">Rate History</div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="table-th">Effective From</th>
                  <th class="table-th text-right">EPF Emp.</th>
                  <th class="table-th text-right">EPF Emr.</th>
                  <th class="table-th text-right">ETF</th>
                  <th class="table-th text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="r in settingsHistory" :key="r.id" class="hover:bg-gray-50">
                  <td class="table-td text-sm">{{ fmtDate(r.effective_from) }}</td>
                  <td class="table-td text-right font-mono text-red-600">{{ r.epf_employee_rate }}%</td>
                  <td class="table-td text-right font-mono text-purple-700">{{ r.epf_employer_rate }}%</td>
                  <td class="table-td text-right font-mono text-indigo-700">{{ r.etf_employer_rate }}%</td>
                  <td class="table-td text-center">
                    <span :class="r.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'" class="badge text-xs">
                      {{ r.is_active ? 'Active' : 'Superseded' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!settingsHistory.length">
                  <td colspan="5" class="table-td text-center text-gray-400 py-6">No rate history yet</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>

    <!-- ── Give Advance Modal ── -->
    <div v-if="advanceModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col">
        <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
          <h3 class="font-semibold text-gray-800">Give Salary Advance</h3>
          <button @click="advanceModal = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
        </div>
        <form @submit.prevent="submitAdvance" class="p-6 space-y-4">
          <div>
            <label class="form-label">Employee *</label>
            <select v-model="advanceForm.employee_id" class="form-input" required>
              <option value="">— Select Employee —</option>
              <option v-for="e in allEmployees" :key="e.id" :value="e.id">{{ e.employee_number }} – {{ e.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Amount (LKR) *</label>
              <input v-model.number="advanceForm.amount" type="number" min="1" step="0.01" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Date *</label>
              <input v-model="advanceForm.given_date" type="date" class="form-input" required />
            </div>
          </div>
          <div>
            <label class="form-label">Pay From Account *</label>
            <select v-model="advanceForm.paid_from_account_id" class="form-input" required>
              <option value="">— Select —</option>
              <option v-for="a in cashAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
            </select>
          </div>
          <div>
            <label class="form-label">Reason</label>
            <input v-model="advanceForm.reason" class="form-input" placeholder="e.g. Medical emergency" />
          </div>
          <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-800">
            GL: DR Salary Advance (1300) · CR Cash/Bank
          </div>
          <p v-if="advanceError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ advanceError }}</p>
        </form>
        <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
          <button type="button" @click="advanceModal = false" class="btn-secondary">Cancel</button>
          <button type="button" @click="submitAdvance" :disabled="advanceSaving" class="btn-primary">
            {{ advanceSaving ? 'Saving…' : 'Record Advance' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Pay Salary Modal ── -->
    <div v-if="payModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[92vh] flex flex-col">
        <div class="px-6 py-4 border-b flex items-center justify-between shrink-0">
          <h3 class="font-semibold text-gray-800">Process Salary Payment</h3>
          <button @click="payModal = false" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
        </div>
        <form @submit.prevent="submitPayment" class="overflow-y-auto flex-1 p-6 space-y-4">
          <div>
            <label class="form-label">Employee *</label>
            <select v-model="payForm.employee_id" class="form-input" required @change="prefillSalary">
              <option value="">— Select Employee —</option>
              <option v-for="e in allEmployees" :key="e.id" :value="e.id">
                {{ e.employee_number }} – {{ e.name }} (LKR {{ lkr(e.basic_salary) }})
              </option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Period From *</label>
              <input v-model="payForm.period_from" type="date" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Period To *</label>
              <input v-model="payForm.period_to" type="date" class="form-input" required />
            </div>
          </div>
          <div>
            <label class="form-label">Payment Date *</label>
            <input v-model="payForm.payment_date" type="date" class="form-input" required />
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="form-label">Basic Salary (LKR) *</label>
              <input v-model.number="payForm.basic_salary" type="number" min="0" step="0.01" class="form-input" required @input="recalc" />
            </div>
            <div>
              <label class="form-label">Allowances</label>
              <input v-model.number="payForm.allowances" type="number" min="0" step="0.01" class="form-input" @input="recalc" />
            </div>
            <div>
              <label class="form-label">Other Deductions</label>
              <input v-model.number="payForm.deductions" type="number" min="0" step="0.01" class="form-input" @input="recalc" />
            </div>
          </div>

          <!-- EPF/ETF toggle -->
          <div class="border rounded-xl p-4 space-y-3" :class="payForm.apply_epf_etf ? 'border-red-200 bg-red-50/30' : 'border-gray-200'">
            <label class="flex items-center gap-3 cursor-pointer">
              <div class="relative">
                <input type="checkbox" v-model="payForm.apply_epf_etf" class="sr-only" @change="recalc" />
                <div class="w-10 h-5 rounded-full transition-colors" :class="payForm.apply_epf_etf ? 'bg-red-500' : 'bg-gray-300'"></div>
                <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" :class="payForm.apply_epf_etf ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
              <span class="text-sm font-medium text-gray-700">Apply EPF / ETF Deductions</span>
              <span v-if="currentRates" class="text-xs text-gray-400">({{ currentRates.epf_employee_rate }}% / {{ currentRates.epf_employer_rate }}% / {{ currentRates.etf_employer_rate }}%)</span>
            </label>

            <div v-if="payForm.apply_epf_etf && currentRates" class="grid grid-cols-3 gap-3 text-xs">
              <div class="bg-white rounded-lg p-2 border border-red-100 text-center">
                <p class="text-gray-500">EPF Employee ({{ currentRates.epf_employee_rate }}%)</p>
                <p class="font-bold text-red-600 text-sm">- LKR {{ lkr(calcEpfEmployee) }}</p>
              </div>
              <div class="bg-white rounded-lg p-2 border border-purple-100 text-center">
                <p class="text-gray-500">EPF Employer ({{ currentRates.epf_employer_rate }}%)</p>
                <p class="font-bold text-purple-700 text-sm">LKR {{ lkr(calcEpfEmployer) }}</p>
              </div>
              <div class="bg-white rounded-lg p-2 border border-indigo-100 text-center">
                <p class="text-gray-500">ETF Employer ({{ currentRates.etf_employer_rate }}%)</p>
                <p class="font-bold text-indigo-700 text-sm">LKR {{ lkr(calcEtfEmployer) }}</p>
              </div>
            </div>
          </div>

          <!-- Pending advances to recover -->
          <div v-if="employeePendingAdvances.length" class="border border-blue-200 rounded-xl p-4 space-y-2 bg-blue-50/40">
            <p class="text-sm font-medium text-blue-800">Pending Advances — select to recover in this payment</p>
            <label v-for="adv in employeePendingAdvances" :key="adv.id"
              class="flex items-center gap-3 cursor-pointer bg-white rounded-lg px-3 py-2 border border-blue-100">
              <input type="checkbox" :value="adv.id" v-model="selectedAdvanceIds" class="rounded text-blue-600" />
              <span class="flex-1 text-sm">{{ adv.advance_number }} — {{ fmtDate(adv.given_date) }}</span>
              <span class="font-mono text-sm font-bold text-blue-700">LKR {{ lkr(adv.amount) }}</span>
            </label>
            <p v-if="totalAdvanceDeduction > 0" class="text-xs text-blue-700 font-medium text-right">
              Total to recover: LKR {{ lkr(totalAdvanceDeduction) }}
            </p>
          </div>

          <!-- Net salary preview -->
          <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Gross Salary</span>
              <span class="font-mono text-sm">LKR {{ lkr(grossSalary) }}</span>
            </div>
            <div v-if="payForm.apply_epf_etf" class="flex items-center justify-between text-red-600">
              <span class="text-sm">EPF Employee deduction</span>
              <span class="font-mono text-sm">- LKR {{ lkr(calcEpfEmployee) }}</span>
            </div>
            <div v-if="payForm.deductions > 0" class="flex items-center justify-between text-orange-600">
              <span class="text-sm">Other deductions</span>
              <span class="font-mono text-sm">- LKR {{ lkr(payForm.deductions || 0) }}</span>
            </div>
            <div class="flex items-center justify-between border-t pt-2 mt-1">
              <span class="text-sm font-semibold text-amber-700">Net Pay to Employee</span>
              <span class="text-xl font-bold text-amber-800">LKR {{ lkr(netSalary) }}</span>
            </div>
            <div v-if="totalAdvanceDeduction > 0" class="flex items-center justify-between text-blue-700">
              <span class="text-sm">Advance recovery</span>
              <span class="font-mono text-sm">- LKR {{ lkr(totalAdvanceDeduction) }}</span>
            </div>
            <div v-if="totalAdvanceDeduction > 0" class="flex items-center justify-between border-t pt-2 mt-1 text-blue-800">
              <span class="text-sm font-semibold">Cash Actually Paid Out</span>
              <span class="text-xl font-bold">LKR {{ lkr(netCashOut) }}</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Payment Method *</label>
              <select v-model="payForm.payment_method" class="form-input" required>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>
            <div>
              <label class="form-label">Pay From Account *</label>
              <select v-model="payForm.paid_from_account_id" class="form-input" required>
                <option value="">— Select —</option>
                <option v-for="a in cashAccounts" :key="a.id" :value="a.id">{{ a.code }} – {{ a.name }}</option>
              </select>
            </div>
          </div>
          <div>
            <label class="form-label">Notes</label>
            <textarea v-model="payForm.notes" rows="2" class="form-input" placeholder="Optional notes…"></textarea>
          </div>

          <p v-if="payError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ payError }}</p>
        </form>
        <div class="px-6 py-4 border-t flex justify-end gap-3 shrink-0">
          <button type="button" @click="payModal = false" class="btn-secondary">Cancel</button>
          <button type="button" @click="submitPayment" :disabled="paying || netSalary <= 0" class="btn-primary disabled:opacity-50">
            {{ paying ? 'Processing…' : 'Post & Pay' }}
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
import { useRouter } from 'vue-router'
import {
  BanknotesIcon, TrashIcon, XMarkIcon, ArrowPathIcon,
  DocumentTextIcon, PrinterIcon, BuildingLibraryIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()

const payments       = ref({ data: [] })
const allEmployees   = ref([])
const cashAccounts   = ref([])
const summaryData    = ref(null)
const currentRates   = ref(null)
const settingsHistory = ref([])
const branding       = ref({ shop_name: 'Jewellery Store', logo_url: '' })
const loading        = ref(false)
const activeTab      = ref('payments')
const empFilter      = ref('')
const dateFrom       = ref('')
const dateTo         = ref('')
const page           = ref(1)
const payModal       = ref(false)
const paying         = ref(false)
const payError       = ref('')
const rateSaving     = ref(false)
const rateError      = ref('')

// Advances
const advancesData             = ref({ data: [] })
const advancePage              = ref(1)
const advanceModal             = ref(false)
const advanceSaving            = ref(false)
const advanceError             = ref('')
const employeePendingAdvances  = ref([])
const selectedAdvanceIds       = ref([])

const defaultAdvanceForm = () => ({
  employee_id: '', amount: 0, given_date: today(),
  paid_from_account_id: '', reason: '',
})
const advanceForm = ref(defaultAdvanceForm())

const rateForm = ref({
  epf_employee_rate: 8.00,
  epf_employer_rate: 12.00,
  etf_employer_rate: 3.00,
  effective_from: new Date().toISOString().slice(0, 10),
  notes: '',
})

const defaultPayForm = () => ({
  employee_id: '', period_from: firstOfMonth(), period_to: lastOfMonth(),
  payment_date: today(), basic_salary: 0, allowances: 0, deductions: 0,
  apply_epf_etf: false,
  payment_method: 'bank_transfer', paid_from_account_id: '', notes: '',
})
const payForm = ref(defaultPayForm())

// Computed EPF/ETF amounts in the modal
const grossSalary    = computed(() => (payForm.value.basic_salary || 0) + (payForm.value.allowances || 0))
const calcEpfEmployee = computed(() => payForm.value.apply_epf_etf && currentRates.value
  ? Math.round(grossSalary.value * currentRates.value.epf_employee_rate / 100 * 100) / 100 : 0)
const calcEpfEmployer = computed(() => payForm.value.apply_epf_etf && currentRates.value
  ? Math.round(grossSalary.value * currentRates.value.epf_employer_rate / 100 * 100) / 100 : 0)
const calcEtfEmployer = computed(() => payForm.value.apply_epf_etf && currentRates.value
  ? Math.round(grossSalary.value * currentRates.value.etf_employer_rate / 100 * 100) / 100 : 0)
const netSalary            = computed(() => Math.max(0, grossSalary.value - calcEpfEmployee.value - (payForm.value.deductions || 0)))
const totalAdvanceDeduction = computed(() => selectedAdvanceIds.value.reduce((sum, id) => {
  const adv = employeePendingAdvances.value.find(a => a.id === id)
  return sum + (adv ? Number(adv.amount) : 0)
}, 0))
const netCashOut = computed(() => Math.max(0, netSalary.value - totalAdvanceDeduction.value))

// Summary totals
const totalPaid          = computed(() => payments.value.data?.reduce((s, p) => s + Number(p.net_salary), 0) ?? 0)
const totalEpfEmployee   = computed(() => payments.value.data?.reduce((s, p) => s + Number(p.epf_employee || 0), 0) ?? 0)
const totalEmployerContrib = computed(() => payments.value.data?.reduce((s, p) => s + Number(p.epf_employer || 0) + Number(p.etf_employer || 0), 0) ?? 0)

function tabCls(t) {
  return ['px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border',
    activeTab.value === t ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'].join(' ')
}

function recalc() {} // reactivity handles it via computed

async function fetchAdvances() {
  const { data } = await axios.get('/api/salary-advances', {
    params: { page: advancePage.value, employee_id: empFilter.value },
  })
  advancesData.value = data
}

function openAdvanceModal() {
  advanceForm.value  = defaultAdvanceForm()
  advanceError.value = ''
  advanceModal.value = true
}

async function submitAdvance() {
  advanceSaving.value = true; advanceError.value = ''
  try {
    await axios.post('/api/salary-advances', advanceForm.value)
    advanceModal.value = false
    fetchAdvances()
  } catch (e) {
    advanceError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0] ?? 'Failed.'
  } finally { advanceSaving.value = false }
}

async function cancelAdvance(a) {
  if (!confirm(`Cancel advance ${a.advance_number} (LKR ${lkr(a.amount)})?`)) return
  try {
    await axios.delete(`/api/salary-advances/${a.id}`)
    fetchAdvances()
  } catch (e) { alert(e.response?.data?.message ?? 'Cannot cancel.') }
}

async function loadPendingAdvances(employeeId) {
  employeePendingAdvances.value = []
  selectedAdvanceIds.value = []
  if (!employeeId) return
  const { data } = await axios.get(`/api/salary-advances/pending/${employeeId}`)
  employeePendingAdvances.value = data
}

async function fetchPayments() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/salary-payments', {
      params: { page: page.value, employee_id: empFilter.value, from: dateFrom.value, to: dateTo.value },
    })
    payments.value = data
  } finally { loading.value = false }
}

async function fetchSummary() {
  summaryData.value = null
  const { data } = await axios.get('/api/salary-payments/summary', {
    params: { from: dateFrom.value || firstOfYear(), to: dateTo.value || today() },
  })
  summaryData.value = data
}

async function loadSettings() {
  const [hist, curr] = await Promise.all([
    axios.get('/api/epf-etf-settings'),
    axios.get('/api/epf-etf-settings/current').catch(() => ({ data: null })),
  ])
  settingsHistory.value = hist.data
  currentRates.value    = curr.data
  if (curr.data) {
    rateForm.value.epf_employee_rate = curr.data.epf_employee_rate
    rateForm.value.epf_employer_rate = curr.data.epf_employer_rate
    rateForm.value.etf_employer_rate = curr.data.etf_employer_rate
  }
}

async function saveRates() {
  rateSaving.value = true; rateError.value = ''
  try {
    await axios.post('/api/epf-etf-settings', rateForm.value)
    await loadSettings()
    currentRates.value = (await axios.get('/api/epf-etf-settings/current')).data
  } catch (e) {
    rateError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ') ?? 'Error saving'
  } finally { rateSaving.value = false }
}

function openPay() {
  payForm.value              = defaultPayForm()
  payError.value             = ''
  employeePendingAdvances.value = []
  selectedAdvanceIds.value   = []
  payModal.value             = true
}

function prefillSalary() {
  const emp = allEmployees.value.find(e => e.id == payForm.value.employee_id)
  if (emp) payForm.value.basic_salary = emp.basic_salary
  loadPendingAdvances(payForm.value.employee_id)
}

async function submitPayment() {
  paying.value = true; payError.value = ''
  try {
    await axios.post('/api/salary-payments', {
      ...payForm.value,
      advance_ids: selectedAdvanceIds.value,
    })
    payModal.value = false
    fetchPayments()
  } catch (e) {
    payError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0] ?? 'Failed.'
  } finally { paying.value = false }
}

async function deletePayment(p) {
  if (!confirm(`Delete payment ${p.payment_number}?`)) return
  try {
    await axios.delete(`/api/salary-payments/${p.id}`)
    fetchPayments()
  } catch (e) { alert(e.response?.data?.message ?? 'Cannot delete.') }
}

function goToGL(p) { router.push('/general-ledger') }

function printPayslip(p) {
  const emp       = p.employee ?? {}
  const gross     = Number(p.gross_salary || p.basic_salary || 0)
  const epfEmp    = Number(p.epf_employee || 0)
  const epfEmr    = Number(p.epf_employer || 0)
  const etf       = Number(p.etf_employer || 0)
  const otherDed  = Number(p.deductions || 0)
  const advDed    = Number(p.advance_deduction || 0)
  const net       = Number(p.net_salary || 0)
  const cashOut   = net - advDed

  const popup = window.open('', '_blank', 'width=680,height=820')
  if (!popup) { alert('Popup blocked. Please allow popups to print payslips.'); return }

  popup.document.write(`<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payslip - ${p.payment_number}</title>
  <style>
    @page { size: A5 landscape; margin: 10mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #1a1a1a; background: #fff; }
    .wrap { padding: 4mm; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #b8860b; padding-bottom: 4mm; margin-bottom: 4mm; }
    .shop-name { font-size: 14pt; font-weight: bold; color: #b8860b; }
    .title { font-size: 12pt; font-weight: bold; color: #333; text-align: right; }
    .pay-num { font-size: 8pt; color: #888; }
    .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 2mm 6mm; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 4px; padding: 3mm; margin-bottom: 4mm; font-size: 9pt; }
    .meta-item label { color: #888; display: block; font-size: 8pt; }
    .meta-item span { font-weight: 600; }
    table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    th { background: #f3f3f3; padding: 2mm 3mm; text-align: left; border-bottom: 1px solid #ddd; font-size: 8pt; text-transform: uppercase; color: #555; }
    th.r, td.r { text-align: right; }
    td { padding: 2mm 3mm; border-bottom: 1px solid #f0f0f0; }
    .section-title { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #888; padding: 2mm 3mm; background: #fafafa; letter-spacing: 0.5px; }
    .total-row td { font-weight: bold; background: #fffbea; border-top: 2px solid #b8860b; }
    .net-row td { font-weight: bold; font-size: 11pt; background: #b8860b; color: #fff; }
    .employer-row td { color: #6b21a8; background: #faf5ff; font-style: italic; }
    .footer { margin-top: 5mm; display: flex; justify-content: space-between; border-top: 1px solid #e0e0e0; padding-top: 3mm; font-size: 8pt; color: #aaa; }
    .sig { text-align: right; }
    .sig-line { border-top: 1px solid #999; width: 40mm; display: inline-block; margin-top: 8mm; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div style="display:flex;align-items:center;gap:4mm;">
      ${branding.value.logo_url ? `<img src="${branding.value.logo_url}" alt="Logo" style="height:14mm;width:14mm;object-fit:contain;border-radius:3px;" />` : ''}
      <div>
        <div class="shop-name">${branding.value.shop_name}</div>
        <div style="font-size:8pt;color:#888;margin-top:1mm;">EMPLOYEE PAYSLIP</div>
      </div>
    </div>
    <div style="text-align:right;">
      <div class="title">${p.payment_number}</div>
      <div class="pay-num">Payment Date: ${fmtDate(p.payment_date)}</div>
    </div>
  </div>

  <div class="meta">
    <div class="meta-item"><label>Employee Name</label><span>${emp.name ?? ''}</span></div>
    <div class="meta-item"><label>Employee No.</label><span>${emp.employee_number ?? ''}</span></div>
    <div class="meta-item"><label>Designation</label><span>${emp.designation ?? '—'}</span></div>
    <div class="meta-item"><label>Pay Period</label><span>${fmtDate(p.period_from)} – ${fmtDate(p.period_to)}</span></div>
    <div class="meta-item"><label>Payment Method</label><span style="text-transform:capitalize;">${(p.payment_method ?? '').replace('_',' ')}</span></div>
    <div class="meta-item"><label>GL Status</label><span>${p.journal_entry_id ? 'Posted to GL' : 'Draft'}</span></div>
  </div>

  <table>
    <tr><td colspan="2" class="section-title">Earnings</td></tr>
    <tr><td>Basic Salary</td><td class="r">LKR ${lkr(p.basic_salary)}</td></tr>
    ${Number(p.allowances) > 0 ? `<tr><td>Allowances</td><td class="r">LKR ${lkr(p.allowances)}</td></tr>` : ''}
    <tr class="total-row"><td>Gross Salary</td><td class="r">LKR ${lkr(gross)}</td></tr>

    <tr><td colspan="2" class="section-title" style="padding-top:3mm;">Deductions (Employee)</td></tr>
    ${epfEmp > 0 ? `<tr><td>EPF Employee Contribution (${currentRates.value?.epf_employee_rate ?? ''}%)</td><td class="r" style="color:#dc2626;">- LKR ${lkr(epfEmp)}</td></tr>` : ''}
    ${otherDed > 0 ? `<tr><td>Other Deductions</td><td class="r" style="color:#ea580c;">- LKR ${lkr(otherDed)}</td></tr>` : ''}
    ${epfEmp === 0 && otherDed === 0 && advDed === 0 ? '<tr><td colspan="2" style="color:#aaa;font-size:8pt;">No deductions</td></tr>' : ''}

    <tr class="net-row"><td>Net Pay to Employee</td><td class="r">LKR ${lkr(net)}</td></tr>

    ${advDed > 0 ? `
    <tr><td colspan="2" class="section-title" style="padding-top:3mm;">Advance Recovery</td></tr>
    <tr style="color:#1d4ed8;"><td>Salary Advance Deduction</td><td class="r" style="color:#1d4ed8;">- LKR ${lkr(advDed)}</td></tr>
    <tr style="font-weight:bold;background:#eff6ff;"><td>Cash Paid Out</td><td class="r" style="color:#1e40af;">LKR ${lkr(cashOut)}</td></tr>
    ` : ''}

    ${(epfEmr > 0 || etf > 0) ? `
    <tr><td colspan="2" class="section-title" style="padding-top:3mm;">Employer Contributions (not deducted from employee)</td></tr>
    ${epfEmr > 0 ? `<tr class="employer-row"><td>EPF Employer Contribution (${currentRates.value?.epf_employer_rate ?? ''}%)</td><td class="r">LKR ${lkr(epfEmr)}</td></tr>` : ''}
    ${etf > 0 ? `<tr class="employer-row"><td>ETF Employer Contribution (${currentRates.value?.etf_employer_rate ?? ''}%)</td><td class="r">LKR ${lkr(etf)}</td></tr>` : ''}
    <tr class="employer-row" style="font-weight:bold;"><td>Total Employer Contribution</td><td class="r">LKR ${lkr(epfEmr + etf)}</td></tr>
    ` : ''}
  </table>

  <div class="footer">
    <div>Generated: ${new Date().toLocaleString('en-LK')}<br/>This is a computer-generated payslip.</div>
    <div class="sig">
      <div class="sig-line"></div>
      <div style="margin-top:1mm;">Authorized Signature</div>
    </div>
  </div>
</div>
<script>window.onload=function(){window.print();}<\/script>
</body></html>`)
  popup.document.close()
}

function lkr(v) { return Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
function fmtDate(d) { return _fmtDate(d) }
function today() { return new Date().toISOString().slice(0, 10) }
function firstOfMonth() { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-01` }
function lastOfMonth() { const d = new Date(new Date().getFullYear(), new Date().getMonth()+1, 0); return d.toISOString().slice(0,10) }
function firstOfYear() { return `${new Date().getFullYear()}-01-01` }

onMounted(async () => {
  const [empRes, accRes, ratesRes, brandRes] = await Promise.all([
    axios.get('/api/employees/all'),
    axios.get('/api/accounts/all'),
    axios.get('/api/epf-etf-settings/current').catch(() => ({ data: null })),
    axios.get('/api/shop-branding').catch(() => ({ data: null })),
  ])
  allEmployees.value = empRes.data
  cashAccounts.value = accRes.data.filter(a => a.type === 'asset' && ['current_asset'].includes(a.sub_type))
  currentRates.value = ratesRes.data
  if (brandRes.data?.shop_name) branding.value.shop_name = brandRes.data.shop_name
  if (brandRes.data?.logo_url)  branding.value.logo_url  = brandRes.data.logo_url
  fetchPayments()
})
</script>
