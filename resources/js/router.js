import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '',          name: 'dashboard',  component: () => import('@/pages/Dashboard.vue') },
            { path: 'products',  name: 'products',   component: () => import('@/pages/Products.vue') },
            { path: 'categories',name: 'categories', component: () => import('@/pages/Categories.vue') },
            { path: 'customers', name: 'customers',  component: () => import('@/pages/Customers.vue') },
            { path: 'suppliers', name: 'suppliers',  component: () => import('@/pages/Suppliers.vue') },
            { path: 'sales',          name: 'sales',          component: () => import('@/pages/Sales.vue') },
            { path: 'sales/new',      name: 'sales.new',      component: () => import('@/pages/NewSale.vue') },
            { path: 'sales/:id',      name: 'sales.receipt',  component: () => import('@/pages/SaleReceipt.vue') },
            { path: 'purchases', name: 'purchases',  component: () => import('@/pages/Purchases.vue') },
            { path: 'purchases/new', name: 'purchases.new', component: () => import('@/pages/NewPurchase.vue') },
            { path: 'gold-rates',    name: 'gold-rates',    component: () => import('@/pages/GoldRates.vue') },
            { path: 'buy-back',      name: 'buy-back',      component: () => import('@/pages/BuyBack.vue') },
            { path: 'scrap',         name: 'scrap',         component: () => import('@/pages/ScrapManagement.vue') },
            { path: 'users',         name: 'users',         component: () => import('@/pages/Users.vue') },
            { path: 'shop-settings', name: 'shop-settings', component: () => import('@/pages/ShopSettings.vue') },
            { path: 'expenses',      name: 'expenses',      component: () => import('@/pages/ExpenseManagement.vue') },
            { path: 'sms',           name: 'sms',           component: () => import('@/pages/SmsCenter.vue') },
            { path: 'reports',       name: 'reports',       component: () => import('@/pages/Reports.vue') },
            { path: 'day-end',       name: 'day-end',       component: () => import('@/pages/DayEnd.vue') },
            { path: 'audit-log',     name: 'audit-log',     component: () => import('@/pages/AuditLog.vue') },
            // Accounting
            { path: 'opening-balances',  name: 'opening-balances',  component: () => import('@/pages/OpeningBalances.vue') },
            { path: 'accounts',          name: 'accounts',          component: () => import('@/pages/ChartOfAccounts.vue') },
            { path: 'journal-entries',   name: 'journal-entries',   component: () => import('@/pages/JournalEntries.vue') },
            { path: 'general-ledger',    name: 'general-ledger',    component: () => import('@/pages/GeneralLedger.vue') },
            // Human Resources
            { path: 'employees',         name: 'employees',         component: () => import('@/pages/Employees.vue') },
            { path: 'salary-payments',   name: 'salary-payments',   component: () => import('@/pages/SalaryPayments.vue') },
            // Finance
            { path: 'loans',             name: 'loans',             component: () => import('@/pages/Loans.vue') },
            { path: 'rentals',           name: 'rentals',           component: () => import('@/pages/Rentals.vue') },
            { path: 'gold-loans',           name: 'gold-loans',           component: () => import('@/pages/GoldLoans.vue') },
            { path: 'customer-investments', name: 'customer-investments', component: () => import('@/pages/CustomerInvestments.vue') },
            // Rework / Job Orders
            { path: 'rework-orders', name: 'rework-orders', component: () => import('@/pages/ReworkOrders.vue') },
            // Layaway / Installments
            { path: 'layaways', name: 'layaways', component: () => import('@/pages/Layaways.vue') },
            // Private / off-record
            { path: 'informal-purchases', name: 'informal-purchases', component: () => import('@/pages/InformalPurchases.vue') },
        ],
    },
    {
        path: '/receipt/:token',
        name: 'public-receipt',
        component: () => import('@/pages/PublicSaleReceipt.vue'),
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to) => {
    const auth = useAuthStore()
    if (to.meta.requiresAuth && !auth.token) return '/login'
    if (to.meta.guest && auth.token) {
        return auth.user?.role === 'gold_buyer' ? '/informal-purchases' : '/'
    }
    // Redirect gold_buyer away from the dashboard root to their home page
    if (to.path === '/' && auth.token && auth.user?.role === 'gold_buyer') {
        return '/informal-purchases'
    }
})

export default router
