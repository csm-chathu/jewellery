<template>
  <div class="flex gap-0 h-full">

    <!-- ── Left Sidebar ── -->
    <aside class="w-56 shrink-0 border-r border-gray-200 pr-2 space-y-0.5 overflow-y-auto">
      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 pt-1 pb-2">Guide Sections</p>
      <button v-for="s in sections" :key="s.key"
        @click="active = s.key"
        :class="active === s.key
          ? 'bg-gold-50 text-gold-700 font-semibold border-r-2 border-gold-500'
          : 'text-gray-600 hover:bg-gray-50'"
        class="w-full text-left flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors">
        <span class="text-base">{{ s.icon }}</span>
        <span>{{ s.label }}</span>
        <span v-if="s.key === 'checklist'" class="ml-auto text-xs font-bold"
          :class="checklistProgress === 100 ? 'text-green-600' : 'text-gray-400'">
          {{ checklistDone }}/{{ checklistTotal }}
        </span>
      </button>
    </aside>

    <!-- ── Main Content ── -->
    <div class="flex-1 pl-6 min-w-0 overflow-y-auto space-y-5 pb-10">

      <!-- ══════════ CHECKLIST ══════════ -->
      <template v-if="active === 'checklist'">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Go-Live Checklist</h2>
            <p class="text-sm text-gray-500 mt-0.5">Tick off each item before you start using the system for real transactions.</p>
          </div>
          <button @click="resetChecklist" class="text-xs text-gray-400 hover:text-red-500 transition-colors">Reset all</button>
        </div>

        <!-- Progress bar -->
        <div class="card py-4 px-5">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Setup Progress</span>
            <span class="text-sm font-bold" :class="checklistProgress === 100 ? 'text-green-600' : 'text-gold-600'">
              {{ checklistProgress }}%
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="h-3 rounded-full transition-all duration-500"
              :class="checklistProgress === 100 ? 'bg-green-500' : 'bg-gold-500'"
              :style="`width: ${checklistProgress}%`"></div>
          </div>
          <p v-if="checklistProgress === 100" class="mt-3 text-sm text-green-700 font-medium text-center">
            ✅ All done! Your system is ready to use.
          </p>
        </div>

        <!-- Checklist groups -->
        <div v-for="group in checklistGroups" :key="group.title" class="card space-y-1 py-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3 px-1">{{ group.title }}</h3>
          <label v-for="item in group.items" :key="item.id"
            class="flex items-start gap-3 px-2 py-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors group">
            <input type="checkbox"
              :checked="checked.has(item.id)"
              @change="toggleCheck(item.id)"
              class="mt-0.5 w-4 h-4 rounded border-gray-300 text-gold-600 focus:ring-gold-500 shrink-0" />
            <div>
              <span class="text-sm" :class="checked.has(item.id) ? 'line-through text-gray-400' : 'text-gray-800'">
                {{ item.label }}
              </span>
              <p v-if="item.hint" class="text-xs text-gray-400 mt-0.5">{{ item.hint }}</p>
            </div>
          </label>
        </div>
      </template>

      <!-- ══════════ SHOP SETUP ══════════ -->
      <template v-else-if="active === 'setup'">
        <h2 class="text-xl font-bold text-gray-900">Shop Setup — Do This First</h2>
        <p class="text-sm text-gray-500">Complete these steps in order before recording any real transactions.</p>

        <div v-for="(step, i) in setupSteps" :key="i" class="card">
          <div class="flex gap-4">
            <div class="w-9 h-9 rounded-full bg-gold-100 text-gold-700 font-bold text-sm flex items-center justify-center shrink-0">
              {{ i + 1 }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-semibold text-gray-900">{{ step.title }}</h3>
                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-mono">{{ step.where }}</span>
              </div>
              <p class="text-sm text-gray-600 mt-1">{{ step.desc }}</p>
              <ul v-if="step.items" class="mt-2 space-y-1">
                <li v-for="it in step.items" :key="it" class="flex items-start gap-2 text-sm text-gray-600">
                  <span class="text-gold-500 mt-0.5 shrink-0">›</span>{{ it }}
                </li>
              </ul>
              <div v-if="step.warning" class="mt-3 flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                <span class="text-amber-500 shrink-0">⚠</span>
                <p class="text-xs text-amber-800">{{ step.warning }}</p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ══════════ EXISTING DATA ══════════ -->
      <template v-else-if="active === 'existing'">
        <h2 class="text-xl font-bold text-gray-900">Entering Existing Data</h2>
        <p class="text-sm text-gray-500">You have existing stock, customers and pending cheques. Here's how to bring them in before going live.</p>

        <div v-for="block in existingDataBlocks" :key="block.title" class="card">
          <div class="flex items-start gap-3">
            <span class="text-2xl">{{ block.icon }}</span>
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900">{{ block.title }}</h3>
              <p class="text-sm text-gray-600 mt-1">{{ block.desc }}</p>
              <ol class="mt-3 space-y-2">
                <li v-for="(step, i) in block.steps" :key="i" class="flex gap-2 text-sm text-gray-700">
                  <span class="w-5 h-5 rounded-full bg-gold-100 text-gold-700 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">{{ i+1 }}</span>
                  {{ step }}
                </li>
              </ol>
              <div v-if="block.tip" class="mt-3 flex items-start gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                <span class="text-blue-500 shrink-0">💡</span>
                <p class="text-xs text-blue-800">{{ block.tip }}</p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ══════════ DAILY ROUTINE ══════════ -->
      <template v-else-if="active === 'daily'">
        <h2 class="text-xl font-bold text-gray-900">Daily Operating Routine</h2>
        <p class="text-sm text-gray-500">Follow this routine every working day for accurate records.</p>

        <div class="grid grid-cols-1 gap-4">
          <div v-for="period in dailyRoutine" :key="period.title" class="card">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-lg">{{ period.icon }}</span>
              <h3 class="font-semibold text-gray-900">{{ period.title }}</h3>
            </div>
            <div class="space-y-2">
              <div v-for="(task, i) in period.tasks" :key="i"
                class="flex items-start gap-3 px-3 py-2.5 bg-gray-50 rounded-lg">
                <span class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center shrink-0 mt-0.5"
                  :class="period.color">{{ i + 1 }}</span>
                <div>
                  <p class="text-sm font-medium text-gray-800">{{ task.label }}</p>
                  <p v-if="task.hint" class="text-xs text-gray-500 mt-0.5">{{ task.hint }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ══════════ FEATURES ══════════ -->
      <template v-else-if="active === 'features'">
        <h2 class="text-xl font-bold text-gray-900">System Features</h2>
        <p class="text-sm text-gray-500">An overview of every module and what it does.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="feat in features" :key="feat.title"
            class="card hover:shadow-md transition-shadow">
            <div class="flex items-start gap-3">
              <span class="text-2xl shrink-0">{{ feat.icon }}</span>
              <div>
                <h3 class="font-semibold text-gray-900 text-sm">{{ feat.title }}</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ feat.desc }}</p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ══════════ ACCOUNTS & GL ══════════ -->
      <template v-else-if="active === 'accounts'">
        <h2 class="text-xl font-bold text-gray-900">Accounts &amp; General Ledger</h2>
        <p class="text-sm text-gray-500">Every financial event posts a journal entry automatically. You do not need to create journals for normal operations.</p>

        <!-- Key accounts table -->
        <div class="card">
          <h3 class="font-semibold text-gray-800 mb-3">Key Account Codes</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">Code</th>
                  <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">Account Name</th>
                  <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">Type</th>
                  <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">Used When</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="acc in accountCodes" :key="acc.code" class="hover:bg-gray-50">
                  <td class="py-2 px-3 font-mono font-bold text-gold-700">{{ acc.code }}</td>
                  <td class="py-2 px-3 text-gray-900">{{ acc.name }}</td>
                  <td class="py-2 px-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                        'bg-blue-100 text-blue-700': acc.type === 'Asset',
                        'bg-red-100 text-red-700': acc.type === 'Liability',
                        'bg-green-100 text-green-700': acc.type === 'Revenue',
                        'bg-orange-100 text-orange-700': acc.type === 'Expense',
                      }">{{ acc.type }}</span>
                  </td>
                  <td class="py-2 px-3 text-gray-500 text-xs">{{ acc.used }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Journal flow table -->
        <div class="card">
          <h3 class="font-semibold text-gray-800 mb-3">What Posts to the Ledger Automatically</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">Transaction</th>
                  <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">Debit (Dr)</th>
                  <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">Credit (Cr)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="je in journalExamples" :key="je.label" class="hover:bg-gray-50">
                  <td class="py-2 px-3 font-medium text-gray-800">{{ je.label }}</td>
                  <td class="py-2 px-3 text-blue-700 text-xs">{{ je.dr }}</td>
                  <td class="py-2 px-3 text-green-700 text-xs">{{ je.cr }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- ══════════ USER ROLES ══════════ -->
      <template v-else-if="active === 'roles'">
        <h2 class="text-xl font-bold text-gray-900">User Roles &amp; Access</h2>
        <p class="text-sm text-gray-500">Each staff member should have the correct role so they only see what they need.</p>

        <div class="space-y-3">
          <div v-for="role in userRoles" :key="role.name" class="card">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0"
                :class="role.color">
                {{ role.name.charAt(0).toUpperCase() }}
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="font-semibold text-gray-900 capitalize">{{ role.name }}</h3>
                  <span class="text-xs px-2 py-0.5 rounded-full" :class="role.badgeClass">{{ role.level }}</span>
                </div>
                <p class="text-sm text-gray-600 mt-1">{{ role.desc }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                  <span v-for="cap in role.can" :key="cap"
                    class="text-xs bg-green-50 text-green-700 border border-green-200 rounded px-2 py-0.5">
                    ✓ {{ cap }}
                  </span>
                  <span v-for="cap in role.cannot" :key="cap"
                    class="text-xs bg-red-50 text-red-700 border border-red-200 rounded px-2 py-0.5">
                    ✗ {{ cap }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card bg-blue-50 border-blue-200">
          <p class="text-sm text-blue-800">
            <strong>To create users:</strong> go to <span class="font-mono bg-blue-100 px-1 rounded">Settings → Users → New User</span>.
            Each user is tied to a branch — branch users only see their own branch data.
          </p>
        </div>
      </template>

      <!-- ══════════ REPORTS ══════════ -->
      <template v-else-if="active === 'reports'">
        <h2 class="text-xl font-bold text-gray-900">Reports Guide</h2>
        <p class="text-sm text-gray-500">All reports are under the Reports menu. Generate, export to CSV, or print to PDF.</p>

        <div class="overflow-x-auto card p-0">
          <table class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500">Report</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500">What It Shows</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500">Frequency</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="r in reportsGuide" :key="r.name" class="hover:bg-gray-50">
                <td class="py-2.5 px-4 font-medium text-gray-900">{{ r.name }}</td>
                <td class="py-2.5 px-4 text-gray-600 text-xs">{{ r.desc }}</td>
                <td class="py-2.5 px-4">
                  <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="r.freqClass">{{ r.freq }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- ══════════ WARNINGS ══════════ -->
      <template v-else-if="active === 'warnings'">
        <h2 class="text-xl font-bold text-gray-900">Important Warnings</h2>
        <p class="text-sm text-gray-500">Read these carefully. Mistakes here are hard to fix once real data is in the system.</p>

        <div v-for="w in warnings" :key="w.title" class="card border-l-4" :class="w.border">
          <div class="flex items-start gap-3">
            <span class="text-xl shrink-0">{{ w.icon }}</span>
            <div>
              <h3 class="font-semibold text-gray-900">{{ w.title }}</h3>
              <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ w.body }}</p>
            </div>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const STORAGE_KEY = 'gs_checklist_v1'

const sections = [
  { key: 'checklist', label: 'Go-Live Checklist', icon: '✅' },
  { key: 'setup',     label: 'Shop Setup',         icon: '🏪' },
  { key: 'existing',  label: 'Existing Data',       icon: '📦' },
  { key: 'daily',     label: 'Daily Routine',       icon: '📅' },
  { key: 'features',  label: 'Features Overview',   icon: '💼' },
  { key: 'accounts',  label: 'Accounts & GL',       icon: '🏦' },
  { key: 'roles',     label: 'User Roles',           icon: '👥' },
  { key: 'reports',   label: 'Reports Guide',        icon: '📊' },
  { key: 'warnings',  label: 'Important Warnings',   icon: '⚠️' },
]

const active = ref('checklist')

// ── Checklist ──────────────────────────────────────────────────────────────
const checklistGroups = [
  {
    title: '1 — Shop Information',
    items: [
      { id: 'shop_name',    label: 'Enter shop name, address, phone & BR number', hint: 'Settings → Shop Settings' },
      { id: 'shop_logo',    label: 'Upload your shop logo', hint: 'Appears on all invoices and receipts' },
      { id: 'print_mode',   label: 'Set print mode (POS thermal or A5 paper)', hint: 'Settings → Shop Settings' },
    ],
  },
  {
    title: '2 — Gold & Tax Configuration',
    items: [
      { id: 'gold_rates',   label: 'Enter today\'s gold rates for all karats (18k, 22k, 24k)', hint: 'Settings → Gold Rates' },
      { id: 'carats',       label: 'Confirm active carats match what your shop uses', hint: 'Settings → Carats' },
      { id: 'tax',          label: 'Set tax rate (or leave empty if no tax applies)', hint: 'Settings → Tax Settings' },
    ],
  },
  {
    title: '3 — Accounts & Opening Balances',
    items: [
      { id: 'coa',          label: 'Review Chart of Accounts — do not delete system accounts', hint: 'Accounting → Chart of Accounts' },
      { id: 'ob_cash',      label: 'Enter opening balance for Cash in Hand (count the till)', hint: 'Accounting → Opening Balances' },
      { id: 'ob_bank',      label: 'Enter opening balance for Bank accounts (from bank statement)', hint: 'Match exactly to your bank statement on go-live date' },
      { id: 'ob_payables',  label: 'Enter opening balance for amounts owed to suppliers', hint: 'Accounts Payable — what you owe right now' },
    ],
  },
  {
    title: '4 — Master Data',
    items: [
      { id: 'categories',   label: 'Create all product categories (Rings, Bangles, Necklaces…)', hint: 'Inventory → Categories' },
      { id: 'suppliers',    label: 'Add all suppliers you buy from', hint: 'Purchases → Suppliers' },
      { id: 'employees',    label: 'Add all employees with salary and EPF/ETF details', hint: 'HR → Employees' },
      { id: 'epf',          label: 'Set EPF and ETF contribution percentages', hint: 'HR → EPF/ETF Settings' },
    ],
  },
  {
    title: '5 — Existing Inventory',
    items: [
      { id: 'products',     label: 'Enter every product currently in stock with correct quantity', hint: 'Inventory → Products — weight, karat, making charge, stock qty' },
      { id: 'barcodes',     label: 'Scan or type barcode/tag number for each product', hint: 'Enables fast barcode lookup at point of sale' },
    ],
  },
  {
    title: '6 — Existing Transactions',
    items: [
      { id: 'customers',    label: 'Add regular customers (name, phone, NIC, birthday)', hint: 'You can add walk-in customers during first sale too' },
      { id: 'cheques',      label: 'Enter any pending/uncleared cheques as purchases (Payment = Cheque)', hint: 'Then click Settle Cheque when it clears at the bank' },
      { id: 'gold_loans',   label: 'Enter any active gold loans (pledged gold + amount lent)', hint: 'Finance → Gold Loans' },
      { id: 'layaways',     label: 'Enter any active layaway / instalment plans', hint: 'Include instalments already paid so far' },
    ],
  },
  {
    title: '7 — Security',
    items: [
      { id: 'password',     label: 'Change admin password from the default', hint: 'Profile → Change Password' },
      { id: 'user_roles',   label: 'Create accounts for each staff member with correct role', hint: 'Settings → Users' },
      { id: 'backup',       label: 'Set up daily database backup on the server', hint: 'Critical — without this a server failure loses all data' },
    ],
  },
]

const checked = ref(new Set())

function loadChecklist() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved) checked.value = new Set(JSON.parse(saved))
  } catch {}
}

function saveChecklist() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify([...checked.value]))
}

function toggleCheck(id) {
  if (checked.value.has(id)) checked.value.delete(id)
  else checked.value.add(id)
  checked.value = new Set(checked.value)
  saveChecklist()
}

function resetChecklist() {
  checked.value = new Set()
  localStorage.removeItem(STORAGE_KEY)
}

const checklistTotal = computed(() => checklistGroups.reduce((n, g) => n + g.items.length, 0))
const checklistDone  = computed(() => checked.value.size)
const checklistProgress = computed(() => Math.round((checklistDone.value / checklistTotal.value) * 100))

onMounted(loadChecklist)

// ── Setup Steps ─────────────────────────────────────────────────────────────
const setupSteps = [
  {
    title: 'Shop Information',
    where: 'Settings → Shop Settings',
    desc: 'This data appears on every invoice and receipt printed or shared with customers.',
    items: ['Shop name', 'Full address', 'Phone number', 'Business Registration (BR) number', 'Shop logo image', 'Print mode: POS (thermal roll) or A5 (half-page)'],
  },
  {
    title: 'Gold Rates',
    where: 'Settings → Gold Rates',
    desc: 'Enter today\'s buying rate per gram for each karat your shop trades in.',
    items: ['24k rate (purest gold)', '22k rate', '18k rate', '14k rate (if applicable)'],
    warning: 'You must set the gold rate every morning before starting work. If you forget, the system uses the last entered rate — this makes stock value reports inaccurate.',
  },
  {
    title: 'Carat Settings',
    where: 'Settings → Carats',
    desc: 'Default carats are already loaded. Toggle off any karats your shop does not use.',
  },
  {
    title: 'Tax Settings',
    where: 'Settings → Tax Settings',
    desc: 'Add the tax rate charged on sales (e.g., NBT 3%, GST/VAT 15%). Leave empty if your shop does not charge tax.',
  },
  {
    title: 'Chart of Accounts',
    where: 'Accounting → Chart of Accounts',
    desc: 'A standard chart is pre-configured. Review it and add any extra accounts you need.',
    items: [
      'Do NOT delete system accounts (marked locked) — they are used by automatic journal entries',
      'Add extra bank accounts as sub-accounts under code 1010',
      'Add extra expense categories if needed (e.g., "Vehicle Expense", "Advertising")',
    ],
  },
  {
    title: 'Opening Balances',
    where: 'Accounting → Opening Balances',
    desc: 'Enter the real balance of every account as of the date you go live. This is the most critical step.',
    items: [
      'Cash in Hand — count the physical till',
      'Bank balance — take from your bank statement on go-live date',
      'Accounts Payable — total you owe to suppliers right now',
      'Any business loan outstanding balance',
      'Any rent deposit you have paid',
    ],
    warning: 'If opening balances are wrong, every financial report from day one will be wrong. Take time to get the exact figures.',
  },
  {
    title: 'Suppliers',
    where: 'Purchases → Suppliers',
    desc: 'Add all suppliers you purchase stock from: name, email, phone, city, country.',
  },
  {
    title: 'Employees & Payroll',
    where: 'HR → Employees',
    desc: 'Add every staff member, then set EPF/ETF rates under HR → EPF/ETF Settings.',
    items: ['Name, NIC, position, phone', 'Basic salary', 'EPF / ETF eligibility', 'Joined date'],
  },
]

// ── Existing Data ────────────────────────────────────────────────────────────
const existingDataBlocks = [
  {
    icon: '💍',
    title: 'Current Inventory (Products)',
    desc: 'Every piece of jewellery in the shop must be entered before going live. Create Categories first, then Products.',
    steps: [
      'Create all categories: Rings, Necklaces, Bangles, Bracelets, Earrings, etc.',
      'For each product enter: Name, SKU/Barcode, Category, Material, Karat, Weight (grams)',
      'Enter Making Charge (per gram or fixed) and Wastage %',
      'Enter gemstone details if applicable',
      'Enter Purchase Price (what you paid), Selling Price (your tag price)',
      'Set Stock Quantity to the exact count you have right now',
      'Assign the Supplier it came from',
    ],
    tip: 'If you have 10 identical bangles, enter stock_quantity = 10 on one product — do not create 10 separate entries.',
  },
  {
    icon: '🧾',
    title: 'Pending Cheques (Supplier Payments)',
    desc: 'Cheques you have already issued to suppliers but not yet cleared at the bank need to be entered as purchases.',
    steps: [
      'Go to Purchases → New Purchase',
      'Enter the purchase with the original date',
      'Set Payment Method = Cheque',
      'Enter cheque number, cheque date, and bank name',
      'Set status = Received (goods are already in your shop)',
      'When the cheque clears at the bank: open that purchase → click Settle Cheque',
    ],
    tip: 'If you skip Step 6 (settle cheque), your Accounts Payable will be overstated and bank balance will be wrong.',
  },
  {
    icon: '👤',
    title: 'Existing Customers',
    desc: 'Add your regular customers so their purchase history is tracked from day one.',
    steps: [
      'Go to Customers → New Customer',
      'Enter name, phone, email, NIC, birthday, address',
      'Birthday is used by the SMS birthday wish feature',
    ],
    tip: 'Walk-in customers can be added on the fly during a sale — you do not need everyone before you start.',
  },
  {
    icon: '🥇',
    title: 'Active Gold Loans',
    desc: 'If you currently have customers who pledged gold in exchange for a cash loan.',
    steps: [
      'Go to Finance → Gold Loans → New Loan',
      'Enter customer, loan date, principal amount, interest rate',
      'Describe the pledged item (weight, karat, description)',
      'The system will track repayments and show reminders when due',
    ],
  },
  {
    icon: '💳',
    title: 'Active Layaways / Instalments',
    desc: 'Customers who are currently paying in instalments for a reserved item.',
    steps: [
      'Go to Layaway → New Layaway',
      'Enter customer, product, total price, expected completion date',
      'Enter the deposit amount already paid',
      'Record any instalment payments already made using the Pay button',
      'When fully paid, use Convert to Sale — do not create a new sale manually',
    ],
  },
  {
    icon: '♻️',
    title: 'Existing Gold Buybacks / Scrap',
    desc: 'Gold you have already bought from customers or have as raw scrap.',
    steps: [
      'Go to Gold Buyback → New Buyback',
      'Enter the customer (or "Walk-in"), date of purchase, weight, karat, and amount paid',
      'Unprocessed scrap will appear in Inventory → Scrap Items',
    ],
  },
]

// ── Daily Routine ────────────────────────────────────────────────────────────
const dailyRoutine = [
  {
    title: 'Every Morning (Before First Transaction)',
    icon: '🌅',
    color: 'bg-amber-100 text-amber-700',
    tasks: [
      { label: 'Set Today\'s Gold Rate', hint: 'Settings → Gold Rates → enter rate for all karats' },
      { label: 'Check Gold Loan Reminders', hint: 'Finance → Gold Loans → Reminders tab for loans due today' },
      { label: 'Check Layaway Reminders', hint: 'Layaway → any instalments due today will be highlighted' },
    ],
  },
  {
    title: 'During the Day',
    icon: '☀️',
    color: 'bg-blue-100 text-blue-700',
    tasks: [
      { label: 'New Sales', hint: 'Sales → New Sale → scan barcode or search product → add customer → collect payment → print/share receipt' },
      { label: 'New Purchases', hint: 'Purchases → New Purchase → select supplier → add items → choose payment method' },
      { label: 'Record Every Expense', hint: 'Expenses → New Expense for every cash outgoing (tea money, electricity, repairs)' },
      { label: 'Process Returns if Any', hint: 'Sales → find the sale → click Return button → select items → process refund' },
    ],
  },
  {
    title: 'End of Day (Before Closing)',
    icon: '🌙',
    color: 'bg-purple-100 text-purple-700',
    tasks: [
      { label: 'Run Day End Report', hint: 'Reports → Day End → review sales, cash collected, expenses' },
      { label: 'Count the Physical Cash', hint: 'The till cash must match the system\'s cash balance' },
      { label: 'Save Day End', hint: 'Click Save Day End to lock the record — cannot be changed after' },
      { label: 'Handle Any Cash Difference', hint: 'If there is a gap, use Private Book → Cash Adjustment (admin only)' },
    ],
  },
  {
    title: 'Every Month',
    icon: '📆',
    color: 'bg-green-100 text-green-700',
    tasks: [
      { label: 'Run Salary Payments', hint: 'HR → Salary Payments → New Payment for each employee' },
      { label: 'Check Trial Balance', hint: 'Accounting → GL → Trial Balance should always balance to zero' },
      { label: 'Review Income Statement', hint: 'Accounting → GL → Income Statement shows monthly profit/loss' },
      { label: 'Reconcile Bank Statement', hint: 'Compare Accounting → GL → Ledger (1010) with your bank statement' },
    ],
  },
]

// ── Features ──────────────────────────────────────────────────────────────────
const features = [
  { icon: '🛒', title: 'Sales', desc: 'Create invoices, collect payment (cash/card/bank transfer/cheque), print or share receipts via link, manage delivery status.' },
  { icon: '📦', title: 'Purchases', desc: 'Record stock received from suppliers. Cash or cheque payment. Cheque settlement tracked separately when it clears.' },
  { icon: '↩️', title: 'Sales Returns', desc: 'Process item returns against any paid sale. Automatically restores stock, reverses revenue in accounts, and records the refund.' },
  { icon: '💰', title: 'Gold Buyback', desc: 'Record gold purchased from walk-in customers (old jewellery, coins). Supports cash and cheque payment.' },
  { icon: '🏦', title: 'Gold Loans', desc: 'Lend cash against pledged gold. Track principal, interest, repayments, and overdue reminders.' },
  { icon: '💳', title: 'Layaway / Instalment', desc: 'Reserve items for customers paying in instalments. Converts to a full sale when fully paid.' },
  { icon: '🎨', title: 'Custom Made Orders', desc: 'Track bespoke jewellery from design deposit → production → issue to customer.' },
  { icon: '🔧', title: 'Rework / Job Orders', desc: 'Repairs, resizing, polishing. Can use scrap or buyback gold as raw material.' },
  { icon: '💎', title: 'Products & Stock', desc: 'Full product catalogue with weight, karat, making charge, wastage, gemstone details. Stock tracked in real time.' },
  { icon: '♻️', title: 'Scrap Management', desc: 'Raw scrap gold from buybacks. Can be converted into new products.' },
  { icon: '👥', title: 'Customers', desc: 'Customer profiles with NIC, birthday, purchase history, KYC. Birthday SMS supported.' },
  { icon: '🚚', title: 'Suppliers', desc: 'Supplier directory linked to purchase orders.' },
  { icon: '👨‍💼', title: 'Employees & Payroll', desc: 'Staff records, monthly salary processing, automatic EPF/ETF calculation.' },
  { icon: '💸', title: 'Expenses', desc: 'Record all day-to-day shop expenses. Each expense posts to the general ledger.' },
  { icon: '📱', title: 'SMS Centre', desc: 'Send promotions, birthday wishes, payment reminders to customers via SMS.' },
  { icon: '🔒', title: 'Private Book', desc: 'Off-record gold purchases, private sales, cash adjustments. Separate from official accounts. Admin/Manager only.' },
  { icon: '📊', title: 'Reports', desc: 'Day End, Sales Summary, Stock Ledger, Category Stock Value, Metal Balance, Rate P&L, Salary, Expenses, Gold Loans, and full GL reports.' },
  { icon: '📋', title: 'Audit Log', desc: 'Every important action recorded with user name, timestamp, and details. Cannot be altered.' },
]

// ── Account Codes ─────────────────────────────────────────────────────────────
const accountCodes = [
  { code: '1000', name: 'Cash in Hand',       type: 'Asset',     used: 'Cash received / paid out at till' },
  { code: '1010', name: 'Bank / Card Account', type: 'Asset',     used: 'Card payments, bank transfers, cheque settlements' },
  { code: '1100', name: 'Accounts Receivable', type: 'Asset',     used: 'Booking deposits / partial payments owed' },
  { code: '2000', name: 'Accounts Payable',    type: 'Liability', used: 'Supplier invoices not yet paid (cheques pending)' },
  { code: '2100', name: 'Tax Payable',         type: 'Liability', used: 'GST / VAT collected on sales' },
  { code: '2200', name: 'Customer Deposits',   type: 'Liability', used: 'Layaway and booking advance payments held' },
  { code: '4000', name: 'Sales Revenue',       type: 'Revenue',   used: 'Every sale invoice' },
  { code: '5000', name: 'Cost of Goods',       type: 'Expense',   used: 'Product cost when sold' },
  { code: '6000+', name: 'Operating Expenses', type: 'Expense',   used: 'Salaries, rent, electricity, maintenance, etc.' },
]

const journalExamples = [
  { label: 'Cash sale',                 dr: 'Cash (1000)',              cr: 'Revenue (4000) + Tax Payable (2100)' },
  { label: 'Card / bank transfer sale', dr: 'Bank/Card (1010)',         cr: 'Revenue (4000)' },
  { label: 'Booking deposit received',  dr: 'Cash (1000)',              cr: 'Customer Deposit (2200)' },
  { label: 'Booking settled (balance)', dr: 'Cash (1000)',              cr: 'Customer Deposit (2200) → Revenue (4000)' },
  { label: 'Purchase — cash',           dr: 'Inventory / COGS',         cr: 'Cash (1000)' },
  { label: 'Purchase — cheque',         dr: 'Inventory / COGS',         cr: 'Accounts Payable (2000)' },
  { label: 'Settle supplier cheque',    dr: 'Accounts Payable (2000)',  cr: 'Bank (1010)' },
  { label: 'Gold buyback — cash',       dr: 'Metal Asset',              cr: 'Cash (1000)' },
  { label: 'Layaway payment',           dr: 'Cash (1000)',              cr: 'Customer Deposit (2200)' },
  { label: 'Layaway → full sale',       dr: 'Customer Deposit (2200)',  cr: 'Revenue (4000)' },
  { label: 'Sales return (refund)',      dr: 'Revenue (4000)',           cr: 'Cash / Bank (1000 or 1010)' },
  { label: 'Expense paid',              dr: 'Expense account (6000+)',  cr: 'Cash (1000)' },
  { label: 'Salary payment',            dr: 'Salary Expense',           cr: 'Cash (1000)' },
  { label: 'Gold loan issued',          dr: 'Loan Receivable',          cr: 'Cash (1000)' },
  { label: 'Gold loan repayment',       dr: 'Cash (1000)',              cr: 'Loan Receivable' },
]

// ── User Roles ────────────────────────────────────────────────────────────────
const userRoles = [
  {
    name: 'admin',
    level: 'Full Access',
    color: 'bg-red-600',
    badgeClass: 'bg-red-100 text-red-700',
    desc: 'Complete access to every module including private book, user management, all branches, and all reports.',
    can: ['Everything'],
    cannot: [],
  },
  {
    name: 'manager',
    level: 'High Access',
    color: 'bg-orange-600',
    badgeClass: 'bg-orange-100 text-orange-700',
    desc: 'All operations and private book. Cannot manage users or view audit logs.',
    can: ['Sales', 'Purchases', 'Reports', 'Private Book', 'HR', 'Accounting'],
    cannot: ['User Management'],
  },
  {
    name: 'gold_buyer',
    level: 'Specialised',
    color: 'bg-yellow-600',
    badgeClass: 'bg-yellow-100 text-yellow-700',
    desc: 'Focused on gold purchasing operations. Access to buybacks, informal purchases, custom orders, and private book.',
    can: ['Gold Buyback', 'Informal Purchases', 'Custom Orders', 'Private Book'],
    cannot: ['Sales', 'Accounting', 'HR', 'User Management'],
  },
  {
    name: 'branch',
    level: 'Branch Only',
    color: 'bg-blue-600',
    badgeClass: 'bg-blue-100 text-blue-700',
    desc: 'Standard cashier / sales operations for their assigned branch only. Cannot see other branches.',
    can: ['Sales', 'Purchases', 'Customers', 'Products', 'Day End', 'Buyback', 'Layaway'],
    cannot: ['Reports', 'Accounting', 'Private Book', 'HR'],
  },
  {
    name: 'cashier',
    level: 'Limited',
    color: 'bg-gray-600',
    badgeClass: 'bg-gray-100 text-gray-700',
    desc: 'Point of sale operations only. Similar to branch but with fewer inventory permissions.',
    can: ['Sales', 'Customers'],
    cannot: ['Purchases', 'Reports', 'Accounting', 'Private Book'],
  },
  {
    name: 'auditor',
    level: 'Read Only',
    color: 'bg-purple-600',
    badgeClass: 'bg-purple-100 text-purple-700',
    desc: 'View-only access to reports, audit log, accounts, and stock ledger. Cannot create or modify any records.',
    can: ['Reports', 'Audit Log', 'Accounting (read)', 'Stock Ledger'],
    cannot: ['Create / Edit anything'],
  },
]

// ── Reports Guide ─────────────────────────────────────────────────────────────
const reportsGuide = [
  { name: 'Day End',              desc: 'Daily summary of sales, purchases, cash collected, and expenses', freq: 'Daily',    freqClass: 'bg-red-100 text-red-700' },
  { name: 'Sales Summary',        desc: 'Sales totals by period, by payment method, by category',          freq: 'Weekly',   freqClass: 'bg-orange-100 text-orange-700' },
  { name: 'Purchase Summary',     desc: 'Stock received by period and supplier',                            freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
  { name: 'Stock Ledger',         desc: 'Full movement history of each product (purchases in, sales out)',  freq: 'As needed', freqClass: 'bg-gray-100 text-gray-600' },
  { name: 'Category Stock Value', desc: 'Total weight and gold value per category at today\'s gold rate',   freq: 'Weekly',   freqClass: 'bg-orange-100 text-orange-700' },
  { name: 'Metal Balance',        desc: 'Total gold purchased vs. sold — net gold position in grams',       freq: 'Weekly',   freqClass: 'bg-orange-100 text-orange-700' },
  { name: 'Gold Rate History',    desc: 'How gold rates changed over time',                                 freq: 'As needed', freqClass: 'bg-gray-100 text-gray-600' },
  { name: 'Rate P&L',             desc: 'Profit/loss from buying and selling at different rates',           freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
  { name: 'Buybacks Report',      desc: 'Gold purchased from customers — weight, karat, amount paid',       freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
  { name: 'Salary Report',        desc: 'Payroll history with EPF/ETF breakdown by period',                 freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
  { name: 'Expenses Report',      desc: 'Expense breakdown by category and period',                         freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
  { name: 'Gold Loans Report',    desc: 'Active and closed loan summary with repayment status',             freq: 'Weekly',   freqClass: 'bg-orange-100 text-orange-700' },
  { name: 'Trial Balance',        desc: 'All account balances — must sum to zero if correct',               freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
  { name: 'Balance Sheet',        desc: 'Assets vs. Liabilities vs. Equity — financial position snapshot',  freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
  { name: 'Income Statement',     desc: 'Revenue minus expenses = net profit for the period',               freq: 'Monthly',  freqClass: 'bg-yellow-100 text-yellow-700' },
]

// ── Warnings ──────────────────────────────────────────────────────────────────
const warnings = [
  {
    icon: '🌅',
    title: 'Set the Gold Rate Every Single Morning',
    body: 'If you forget, the system uses the previous day\'s rate. Category Stock Value, Metal Balance, and product pricing will all be wrong. Make this the very first task before any transaction.',
    border: 'border-red-400',
  },
  {
    icon: '🗄️',
    title: 'Never Delete Products That Have Been Sold',
    body: 'Deleting a sold product breaks the sales history and reports for that product. Instead, mark it as Inactive using the toggle. This hides it from new sales without removing history.',
    border: 'border-red-400',
  },
  {
    icon: '🏦',
    title: 'Cheque Payments Need Two Steps',
    body: 'Step 1: Enter the purchase with Payment = Cheque (records the liability in Accounts Payable). Step 2: When the cheque clears at the bank, open the purchase and click Settle Cheque (moves it to your bank account). Forgetting Step 2 makes your Accounts Payable and bank balance wrong.',
    border: 'border-orange-400',
  },
  {
    icon: '📊',
    title: 'Opening Balances Must Match Reality',
    body: 'Take your bank statement and count the physical cash before entering opening balances. If even one figure is wrong, your Trial Balance will not balance and all financial reports will be off from day one.',
    border: 'border-red-400',
  },
  {
    icon: '📦',
    title: 'Do Not Manually Adjust Stock After Go-Live',
    body: 'After the system is live, stock moves automatically — every sale deducts, every purchase adds, every return restores. Manually editing stock quantities breaks the audit trail and makes the Stock Ledger unreliable.',
    border: 'border-orange-400',
  },
  {
    icon: '💳',
    title: 'Always Use "Convert to Sale" for Layaways',
    body: 'When a layaway customer makes the final payment, press Convert to Sale — do not create a new sale manually. Creating a new sale duplicates the revenue and leaves the layaway open incorrectly.',
    border: 'border-orange-400',
  },
  {
    icon: '🔒',
    title: 'Private Book is Completely Separate',
    body: 'Nothing entered in the Private Book appears in the official accounts, GL, or financial reports. Never mix private cash with the main till. The private cashbook shows its own running balance.',
    border: 'border-yellow-400',
  },
  {
    icon: '📋',
    title: 'Day End Should Be Done Every Working Day',
    body: 'The Day End report locks that day\'s record. If you miss a day, do it the next morning before any new transactions. This is your daily reconciliation between the system and physical cash.',
    border: 'border-yellow-400',
  },
  {
    icon: '🔐',
    title: 'Change the Default Admin Password Immediately',
    body: 'The default admin credentials (admin@jewellery.com / password) are public. Change the password before giving the system to any staff. Create individual accounts — never share the admin login.',
    border: 'border-red-400',
  },
  {
    icon: '💾',
    title: 'Set Up Daily Database Backups',
    body: 'The system stores all data in a database on your server. If the server has a hardware failure or corruption without a backup, all records are permanently lost. Ask your IT person or hosting provider to run automated daily backups.',
    border: 'border-red-400',
  },
]
</script>
