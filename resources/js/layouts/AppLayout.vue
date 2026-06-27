<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col shrink-0">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
        <img
          v-if="branding.logo_url"
          :src="branding.logo_url"
          alt="Shop logo"
          class="h-9 w-9 object-contain rounded"
        />
        <span v-else class="text-2xl">💎</span>
        <div>
          <p class="font-bold text-gold-400 text-sm leading-tight truncate">{{ branding.shop_name }}</p>
          <p class="text-xs text-gray-400">Management System</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 overflow-y-auto">
        <template v-if="visibleNavItems.length">
          <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main</div>
        </template>
        <router-link v-for="item in visibleNavItems" :key="item.to" :to="item.to"
          class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
          :class="isNavActive(item.to)
            ? 'bg-gold-600 text-white hover:bg-gold-700'
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          {{ item.label }}
        </router-link>

        <!-- Admin/Role-based sections -->
        <template v-if="visibleAdminNavItems.length">
          <div class="px-4 mt-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
            {{ currentRole === 'gold_buyer' ? 'Gold Purchasing' : 'Admin' }}
          </div>
          <router-link v-for="item in visibleAdminNavItems" :key="item.to" :to="item.to"
            class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
            :class="isNavActive(item.to)
              ? 'bg-gold-600 text-white hover:bg-gold-700'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            {{ item.label }}
          </router-link>
        </template>

        <template v-if="visibleAccountingNavItems.length">
          <div class="px-4 mt-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Accounting</div>
          <router-link v-for="item in visibleAccountingNavItems" :key="item.to" :to="item.to"
            class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
            :class="isNavActive(item.to)
              ? 'bg-gold-600 text-white hover:bg-gold-700'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            {{ item.label }}
          </router-link>
        </template>

        <template v-if="visibleHrNavItems.length">
          <div class="px-4 mt-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Human Resources</div>
          <router-link v-for="item in visibleHrNavItems" :key="item.to" :to="item.to"
            class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
            :class="isNavActive(item.to)
              ? 'bg-gold-600 text-white hover:bg-gold-700'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            {{ item.label }}
          </router-link>
        </template>

        <template v-if="visibleFinanceNavItems.length">
          <div class="px-4 mt-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Finance</div>
          <router-link v-for="item in visibleFinanceNavItems" :key="item.to" :to="item.to"
            class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
            :class="isNavActive(item.to)
              ? 'bg-gold-600 text-white hover:bg-gold-700'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            {{ item.label }}
          </router-link>
        </template>

        <!-- Private Gold Book sub-nav (gold_buyer role) -->
        <template v-if="currentRole === 'gold_buyer'">
          <div class="px-4 mt-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Private Gold Book</div>
          <router-link
            v-for="item in privateBookNavItems" :key="item.tab"
            :to="{ path: '/informal-purchases', query: { tab: item.tab } }"
            class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
            :class="isPrivateTabActive(item.tab)
              ? 'bg-gold-600 text-white hover:bg-gold-700'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            {{ item.label }}
          </router-link>
        </template>
      </nav>

      <!-- User info -->
      <div class="px-4 py-4 border-t border-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-gold-600 flex items-center justify-center text-sm font-bold">
            {{ auth.user?.name?.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
          </div>
          <button @click="doLogout" title="Logout"
            class="p-1 rounded text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main area -->
    <div class="flex-1 flex flex-col min-h-0 min-w-0">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h1>
        <div class="flex items-center gap-3 text-sm text-gray-500">
          <router-link to="/getting-started"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gold-50 text-gold-700 border border-gold-200 hover:bg-gold-100 transition-colors font-medium text-xs">
            <QuestionMarkCircleIcon class="w-4 h-4" />
            Getting Started
          </router-link>
          <span>{{ currentDate }}</span>
        </div>
      </header>

      <!-- Page -->
      <main class="flex-1 overflow-auto p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon, CubeIcon, TagIcon, UsersIcon,
  TruckIcon, ShoppingCartIcon, ArchiveBoxIcon,
  ArrowRightOnRectangleIcon, SparklesIcon,
  UserGroupIcon, ChartBarIcon, ClipboardDocumentCheckIcon,
  ClipboardDocumentListIcon, CurrencyDollarIcon, FireIcon,
  ScaleIcon, BookOpenIcon, DocumentTextIcon, PresentationChartBarIcon,
  BanknotesIcon, BuildingLibraryIcon, HomeModernIcon,
  ReceiptPercentIcon, Cog6ToothIcon, DevicePhoneMobileIcon, LockClosedIcon,
  WrenchScrewdriverIcon, PaintBrushIcon, ScissorsIcon, TableCellsIcon,
  SquaresPlusIcon, QuestionMarkCircleIcon, ArrowsRightLeftIcon,
  BookmarkIcon, ArrowDownOnSquareIcon, ArrowUpOnSquareIcon, AdjustmentsHorizontalIcon,
} from '@heroicons/vue/24/outline'

const auth   = useAuthStore()
const router = useRouter()
const route  = useRoute()
const branding = ref({
  shop_name: import.meta.env.VITE_APP_NAME ?? 'Jewellery Store',
  logo_url: '',
})

const ALL_STANDARD = ['admin', 'manager', 'accountant', 'hr', 'finance', 'cashier', 'branch']

const navItems = [
  { to: '/',           label: 'Dashboard',  icon: HomeIcon,         roles: [...ALL_STANDARD, 'auditor'] },
  { to: '/products',   label: 'Products',   icon: CubeIcon,         roles: [...ALL_STANDARD, 'auditor'] },
  { to: '/categories', label: 'Categories', icon: TagIcon,           roles: [...ALL_STANDARD, 'auditor'] },
  { to: '/customers',  label: 'Customers',  icon: UsersIcon,         roles: [...ALL_STANDARD, 'auditor'] },
  { to: '/suppliers',  label: 'Suppliers',  icon: TruckIcon,         roles: [...ALL_STANDARD, 'auditor'] },
  { to: '/sales',      label: 'Sales',      icon: ShoppingCartIcon, roles: [...ALL_STANDARD, 'auditor'] },
  { to: '/purchases',  label: 'Purchases',  icon: ArchiveBoxIcon,   roles: [...ALL_STANDARD, 'auditor'] },
]

const adminNavItems = [
  { to: '/gold-rates', label: 'Gold Rates', icon: SparklesIcon, roles: ['admin', 'manager', 'auditor'] },
  { to: '/buy-back', label: 'Buy-Back', icon: CurrencyDollarIcon, roles: ['admin', 'manager', 'cashier', 'branch', 'auditor'] },
  { to: '/scrap', label: 'Scrap Gold', icon: FireIcon, roles: ['admin', 'manager', 'auditor'] },
  { to: '/rework-orders', label: 'Rework / Jobs', icon: WrenchScrewdriverIcon, roles: ['admin', 'manager', 'cashier', 'branch', 'auditor'] },
  { to: '/layaways', label: 'Layaways', icon: SquaresPlusIcon, roles: ['admin', 'manager', 'cashier', 'branch', 'auditor'] },
  { to: '/reports', label: 'Reports', icon: ChartBarIcon, roles: ['admin', 'manager', 'accountant', 'auditor'] },
  { to: '/stock-ledger',   label: 'Stock Ledger',       icon: ClipboardDocumentListIcon, roles: ['admin', 'manager', 'auditor'] },
  { to: '/day-end', label: 'Day End', icon: ClipboardDocumentCheckIcon, roles: ['admin', 'manager', 'cashier', 'branch', 'auditor'] },
  { to: '/audit-log', label: 'Audit Log', icon: ClipboardDocumentListIcon, roles: ['admin', 'auditor'] },
  { to: '/users', label: 'Users', icon: UserGroupIcon, roles: ['admin'] },
  { to: '/shop-settings', label: 'Shop Settings', icon: Cog6ToothIcon, roles: ['admin', 'manager', 'auditor'] },
  { to: '/expenses', label: 'Expenses', icon: ReceiptPercentIcon, roles: ['admin', 'manager', 'finance', 'auditor'] },
  { to: '/sms', label: 'SMS Centre', icon: DevicePhoneMobileIcon, roles: ['admin', 'manager', 'auditor'] },
  { to: '/informal-purchases', label: 'Private Gold Book', icon: LockClosedIcon, roles: ['gold_buyer'] },
  { to: '/custom-made-orders', label: 'Custom Made Orders', icon: PaintBrushIcon, roles: ['gold_buyer'] },
  { to: '/repair-articles',   label: 'Repair Article List', icon: ScissorsIcon,    roles: ['gold_buyer'] },
  { to: '/sl-article-sales',     label: 'S.L Article Sales',   icon: TableCellsIcon,    roles: ['gold_buyer'] },
  { to: '/gold-balance-summary', label: 'Gold Balance Summary', icon: ScaleIcon,          roles: ['gold_buyer'] },
  { to: '/gold-loan-ledger',    label: 'Udaya Loan Summary',   icon: DocumentTextIcon,   roles: ['gold_buyer'] },
  { to: '/gold-list-udaya',    label: 'Gold List Udaya',      icon: ClipboardDocumentListIcon, roles: ['gold_buyer'] },
  { to: '/cash-balance-table', label: 'Cash Balance Table',  icon: BanknotesIcon,             roles: ['gold_buyer'] },
  { to: '/getting-started', label: 'Getting Started', icon: QuestionMarkCircleIcon, roles: ['admin', 'manager', 'auditor'] },
]

const hrNavItems = [
  { to: '/employees', label: 'Employees', icon: UserGroupIcon, roles: ['admin', 'manager', 'hr', 'auditor'] },
  { to: '/salary-payments', label: 'Salary Payments', icon: BanknotesIcon, roles: ['admin', 'manager', 'hr', 'auditor'] },
]

const financeNavItems = [
  { to: '/loans', label: 'Business Loans', icon: BuildingLibraryIcon, roles: ['admin', 'manager', 'finance', 'auditor'] },
  { to: '/customer-investments', label: 'Owner Investments', icon: CurrencyDollarIcon, roles: ['admin', 'manager', 'finance', 'auditor'] },
  { to: '/rentals', label: 'Monthly Rentals', icon: HomeModernIcon, roles: ['admin', 'manager', 'finance', 'auditor'] },
  { to: '/gold-loans', label: 'Gold Loans', icon: CurrencyDollarIcon, roles: ['admin', 'manager', 'finance', 'auditor'] },
]

const accountingNavItems = [
  { to: '/opening-balances', label: 'Opening Balances', icon: ScaleIcon, roles: ['admin', 'manager', 'accountant', 'auditor'] },
  { to: '/accounts', label: 'Chart of Accounts', icon: BookOpenIcon, roles: ['admin', 'manager', 'accountant', 'auditor'] },
  { to: '/journal-entries', label: 'Journal Entries', icon: DocumentTextIcon, roles: ['admin', 'manager', 'accountant', 'auditor'] },
  { to: '/account-transfers', label: 'Account Transfers', icon: ArrowsRightLeftIcon, roles: ['admin', 'manager', 'accountant', 'auditor'] },
  { to: '/general-ledger', label: 'General Ledger', icon: PresentationChartBarIcon, roles: ['admin', 'manager', 'accountant', 'auditor'] },
]

const currentRole = computed(() => auth.user?.role ?? 'branch')
const canOverrideGoldRate = computed(() => !!auth.user?.can_override_gold_rate)

const privateBookNavItems = [
  { tab: 'cashbook',  label: 'Cashbook',          icon: BookmarkIcon },
  { tab: 'sales',     label: 'Sales (Cash In)',    icon: ArrowDownOnSquareIcon },
  { tab: 'purchases', label: 'Purchases (Buy)',    icon: ArrowUpOnSquareIcon },
  { tab: 'expenses',  label: 'Expenses',           icon: AdjustmentsHorizontalIcon },
]

function isPrivateTabActive(tab) {
  return route.path === '/informal-purchases' && (route.query.tab === tab || (!route.query.tab && tab === 'cashbook'))
}

function isAllowed(item) {
  if (item.to === '/gold-rates') {
    return item.roles.includes(currentRole.value) || canOverrideGoldRate.value
  }
  return item.roles.includes(currentRole.value)
}

const visibleNavItems = computed(() => navItems.filter(isAllowed))
const visibleAdminNavItems = computed(() => adminNavItems.filter(isAllowed))
const visibleAccountingNavItems = computed(() => accountingNavItems.filter(isAllowed))
const visibleHrNavItems = computed(() => hrNavItems.filter(isAllowed))
const visibleFinanceNavItems = computed(() => financeNavItems.filter(isAllowed))

const pageTitles = {
  dashboard:         'Dashboard',
  products:          'Products',
  categories:        'Categories',
  customers:         'Customers',
  suppliers:         'Suppliers',
  sales:             'Sales',
  'sales.new':       'New Sale',
  purchases:         'Purchases',
  'purchases.new':   'New Purchase',
  'gold-rates':      'Gold Rate Management',
  'users':           'User Management',
  'shop-settings':   'Shop Settings',
  'reports':         'Reports & Analytics',
  'day-end':         'Day-End Reconciliation',
  'audit-log':       'Audit Log',
  'buy-back':        'Gold Buy-Back',
  'scrap':           'Scrap Gold Management',
  'expenses':        'Expense Management',
  'sms':             'SMS Centre',
  'accounts':        'Chart of Accounts',
  'journal-entries': 'Journal Entries',
  'general-ledger':  'General Ledger',
  'employees':        'Employees',
  'salary-payments':  'Salary Payments',
  'loans':            'Business Loans',
  'rentals':          'Monthly Rentals',
  'gold-loans':            'Gold Loans',
  'customer-investments':  'Owner Investments',
  'rework-orders':        'Rework / Job Orders',
  'layaways':             'Layaway / Installments',
  'informal-purchases':   'Private Gold Book',
  'custom-made-orders':  'Custom Made Orders',
  'stock-ledger':        'Stock Ledger',
  'getting-started':     'Getting Started Guide',
  'account-transfers':   'Account Transfers',
}

const pageTitle  = computed(() => pageTitles[route.name] ?? 'Jewellery MS')
const currentDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/shop-branding')
    if (data?.shop_name) branding.value.shop_name = data.shop_name
    if (data?.logo_url) {
      branding.value.logo_url = data.logo_url
      const favicon = document.getElementById('app-favicon')
      if (favicon) favicon.href = data.logo_url
    }
  } catch {
    // Keep fallback branding if API is unavailable.
  }
})

async function doLogout() {
  await auth.logout()
  router.push('/login')
}

function isNavActive(targetPath) {
  if (targetPath === '/') {
    return route.path === '/'
  }
  return route.path === targetPath || route.path.startsWith(`${targetPath}/`)
}
</script>
