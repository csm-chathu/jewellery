<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">SMS Centre</h2>
        <p class="text-sm text-gray-500 mt-0.5">Send promotions and birthday wishes to customers via SMSlenz</p>
      </div>
      <!-- Sender ID badge -->
      <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-100 px-3 py-1.5 rounded-lg">
        <DevicePhoneMobileIcon class="w-4 h-4" />
        Sender: <span class="font-semibold text-gray-800">{{ senderIdDisplay }}</span>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex gap-1 -mb-px">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
          class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
          :class="activeTab === tab.id
            ? 'border-gold-600 text-gold-700'
            : 'border-transparent text-gray-500 hover:text-gray-700'">
          <component :is="tab.icon" class="w-4 h-4 inline mr-1.5" />
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- ── PROMOTION TAB ─────────────────────────────────────────── -->
    <div v-if="activeTab === 'promotion'" class="space-y-4">
      <div class="card space-y-4">
        <h3 class="font-semibold text-gray-800">Send Promotional SMS</h3>

        <!-- Customer targeting -->
        <div>
          <label class="form-label">Send To</label>
          <div class="flex gap-3 mt-1">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="promoTarget" value="all" class="text-gold-600" />
              <span class="text-sm">All customers with phone ({{ allCustomers.length }})</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="promoTarget" value="selected" class="text-gold-600" />
              <span class="text-sm">Select specific customers</span>
            </label>
          </div>
        </div>

        <!-- Customer multi-select -->
        <div v-if="promoTarget === 'selected'" class="space-y-2">
          <div class="flex items-center gap-2">
            <input v-model="customerSearch" placeholder="Search customers..." class="form-input flex-1" />
            <span class="text-sm text-gray-500">{{ selectedCustomerIds.length }} selected</span>
            <button @click="selectedCustomerIds = []" class="btn-secondary text-xs">Clear</button>
          </div>
          <div class="border rounded-lg max-h-48 overflow-y-auto">
            <label v-for="c in filteredCustomers" :key="c.id"
              class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer border-b last:border-b-0">
              <input type="checkbox" :value="c.id" v-model="selectedCustomerIds" class="rounded text-gold-600" />
              <span class="text-sm font-medium">{{ c.name }}</span>
              <span class="text-xs text-gray-500">{{ c.phone }}</span>
            </label>
            <div v-if="!filteredCustomers.length" class="px-4 py-3 text-sm text-gray-400 text-center">No customers found</div>
          </div>
        </div>

        <!-- Campaign name -->
        <div>
          <label class="form-label">Campaign Name <span class="text-gray-400 font-normal">(optional)</span></label>
          <input v-model="promoForm.campaign_name" placeholder="e.g. Avurudu Offer 2026" class="form-input" />
        </div>

        <!-- Message -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="form-label">Message <span class="text-red-500">*</span></label>
            <span class="text-xs text-gray-500">{{ promoForm.message.length }}/621</span>
          </div>
          <textarea v-model="promoForm.message" rows="4" maxlength="621"
            placeholder="Enter your promotional message here..."
            class="form-input resize-none"></textarea>
          <!-- Quick placeholders -->
          <div class="flex flex-wrap gap-2 mt-2">
            <span class="text-xs text-gray-500">Insert:</span>
            <button v-for="p in placeholders" :key="p.label" @click="insertText(promoForm, p.value)"
              class="text-xs px-2 py-0.5 bg-gray-100 hover:bg-gray-200 rounded-full text-gray-600 transition-colors">
              {{ p.label }}
            </button>
          </div>
        </div>

        <div v-if="promoError" class="text-sm text-red-600 bg-red-50 rounded p-3">{{ promoError }}</div>
        <div v-if="promoSuccess" class="text-sm text-green-700 bg-green-50 rounded p-3">{{ promoSuccess }}</div>

        <div class="flex gap-3">
          <button @click="sendPromotion" :disabled="promoSending"
            class="btn-primary flex items-center gap-2 disabled:opacity-60">
            <PaperAirplaneIcon v-if="!promoSending" class="w-4 h-4" />
            <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
            {{ promoSending ? 'Sending...' : `Send to ${promoRecipientCount} customers` }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── BIRTHDAY TAB ──────────────────────────────────────────── -->
    <div v-if="activeTab === 'birthday'" class="space-y-4">
      <div class="card space-y-4">
        <h3 class="font-semibold text-gray-800">Birthday Wishes</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Date picker -->
          <div>
            <label class="form-label">Birthday Date</label>
            <input v-model="birthdayDate" type="date" class="form-input" @change="previewBirthdays" />
            <p class="text-xs text-gray-500 mt-1">Defaults to today. Change to send for a different date.</p>
          </div>
          <!-- Preview count -->
          <div class="flex items-end">
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 w-full text-center">
              <p class="text-2xl font-bold text-amber-700">{{ birthdayCustomers.length }}</p>
              <p class="text-xs text-gray-600">customers with birthdays on {{ fmtDate(birthdayDate) }}</p>
            </div>
          </div>
        </div>

        <!-- Birthday customer list -->
        <div v-if="birthdayCustomers.length" class="border rounded-lg overflow-hidden">
          <div class="bg-gray-50 px-4 py-2 text-xs font-semibold text-gray-600 uppercase">Recipients</div>
          <div class="divide-y max-h-40 overflow-y-auto">
            <div v-for="c in birthdayCustomers" :key="c.id" class="flex items-center justify-between px-4 py-2">
              <span class="text-sm font-medium">{{ c.name }}</span>
              <span class="text-xs text-gray-500">{{ c.phone }}</span>
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-gray-400 text-center py-4 border rounded-lg">
          No customers with birthdays on this date
        </div>

        <!-- Message -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="form-label">Message <span class="text-red-500">*</span></label>
            <span class="text-xs text-gray-500">{{ birthdayForm.message.length }}/621</span>
          </div>
          <textarea v-model="birthdayForm.message" rows="4" maxlength="621" class="form-input resize-none"></textarea>
          <div class="flex flex-wrap gap-2 mt-2">
            <span class="text-xs text-gray-500">Insert:</span>
            <button v-for="p in placeholders" :key="p.label" @click="insertText(birthdayForm, p.value)"
              class="text-xs px-2 py-0.5 bg-gray-100 hover:bg-gray-200 rounded-full text-gray-600 transition-colors">
              {{ p.label }}
            </button>
          </div>
        </div>

        <div v-if="birthdayError" class="text-sm text-red-600 bg-red-50 rounded p-3">{{ birthdayError }}</div>
        <div v-if="birthdaySuccess" class="text-sm text-green-700 bg-green-50 rounded p-3">{{ birthdaySuccess }}</div>

        <button @click="sendBirthdays" :disabled="birthdaySending || !birthdayCustomers.length"
          class="btn-primary flex items-center gap-2 disabled:opacity-60">
          <PaperAirplaneIcon v-if="!birthdaySending" class="w-4 h-4" />
          <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
          {{ birthdaySending ? 'Sending...' : `Send Birthday Wishes (${birthdayCustomers.length})` }}
        </button>
      </div>
    </div>

    <!-- ── CUSTOM / SINGLE TAB ───────────────────────────────────── -->
    <div v-if="activeTab === 'custom'" class="space-y-4">
      <div class="card space-y-4">
        <h3 class="font-semibold text-gray-800">Send Custom SMS</h3>

        <div>
          <label class="form-label">Phone Numbers <span class="text-red-500">*</span></label>
          <textarea v-model="customContacts" rows="3" class="form-input resize-none font-mono text-sm"
            placeholder="+94771234567&#10;+94751234567&#10;(one per line)"></textarea>
          <p class="text-xs text-gray-500 mt-1">Enter one number per line. Sri Lanka format: +947XXXXXXXX or 07XXXXXXXX</p>
        </div>

        <div>
          <label class="form-label">Campaign Name <span class="text-gray-400 font-normal">(optional)</span></label>
          <input v-model="customForm.campaign_name" placeholder="e.g. Special Offer" class="form-input" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="form-label">Message <span class="text-red-500">*</span></label>
            <span class="text-xs text-gray-500">{{ customForm.message.length }}/621</span>
          </div>
          <textarea v-model="customForm.message" rows="4" maxlength="621"
            placeholder="Enter your message here..."
            class="form-input resize-none"></textarea>
          <div class="flex flex-wrap gap-2 mt-2">
            <span class="text-xs text-gray-500">Insert:</span>
            <button v-for="p in placeholders" :key="p.label" @click="insertText(customForm, p.value)"
              class="text-xs px-2 py-0.5 bg-gray-100 hover:bg-gray-200 rounded-full text-gray-600 transition-colors">
              {{ p.label }}
            </button>
          </div>
        </div>

        <div v-if="customError" class="text-sm text-red-600 bg-red-50 rounded p-3">{{ customError }}</div>
        <div v-if="customSuccess" class="text-sm text-green-700 bg-green-50 rounded p-3">{{ customSuccess }}</div>

        <button @click="sendCustom" :disabled="customSending"
          class="btn-primary flex items-center gap-2 disabled:opacity-60">
          <PaperAirplaneIcon v-if="!customSending" class="w-4 h-4" />
          <ArrowPathIcon v-else class="w-4 h-4 animate-spin" />
          {{ customSending ? 'Sending...' : 'Send SMS' }}
        </button>
      </div>
    </div>

    <!-- ── SMS HISTORY TAB ───────────────────────────────────────── -->
    <div v-if="activeTab === 'history'" class="space-y-4">
      <!-- Filters -->
      <div class="card">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <select v-model="logFilters.type" class="form-input" @change="loadLogs">
            <option value="">All Types</option>
            <option value="promotion">Promotion</option>
            <option value="birthday">Birthday Wish</option>
            <option value="custom">Custom</option>
          </select>
          <select v-model="logFilters.status" class="form-input" @change="loadLogs">
            <option value="">All Status</option>
            <option value="sent">Sent</option>
            <option value="partial">Partial</option>
            <option value="failed">Failed</option>
          </select>
          <input v-model="logFilters.from_date" type="date" class="form-input" @change="loadLogs" />
          <input v-model="logFilters.to_date" type="date" class="form-input" @change="loadLogs" />
        </div>
      </div>

      <!-- Log Table -->
      <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="table-th">Date & Time</th>
                <th class="table-th">Type</th>
                <th class="table-th">Campaign</th>
                <th class="table-th">Recipients</th>
                <th class="table-th">Message (preview)</th>
                <th class="table-th">Sent By</th>
                <th class="table-th">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="log in smsLogs" :key="log.id" class="hover:bg-gray-50">
                <td class="table-td text-sm">{{ fmtDateTime(log.created_at) }}</td>
                <td class="table-td">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="typeBadge(log.type)">
                    {{ typeLabel(log.type) }}
                  </span>
                </td>
                <td class="table-td text-sm text-gray-600">{{ log.campaign_name || '—' }}</td>
                <td class="table-td text-sm">
                  <span class="font-semibold">{{ log.success_count }}</span>
                  <span class="text-gray-400"> / {{ log.total_count }}</span>
                </td>
                <td class="table-td text-sm max-w-xs truncate text-gray-600">{{ log.message }}</td>
                <td class="table-td text-sm">{{ log.sent_by?.name }}</td>
                <td class="table-td">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="statusBadge(log.status)">
                    {{ log.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="!smsLogs.length">
                <td colspan="7" class="table-td text-center text-gray-400 py-10">No SMS history found</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="logsMeta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t text-sm text-gray-600">
          <span>Showing {{ logsMeta.from }}–{{ logsMeta.to }} of {{ logsMeta.total }}</span>
          <div class="flex gap-1">
            <button v-for="p in logsMeta.last_page" :key="p" @click="logsPage = p; loadLogs()"
              class="px-2.5 py-1 rounded border text-xs"
              :class="p === logsPage ? 'bg-gold-600 text-white border-gold-600' : 'hover:bg-gray-100'">
              {{ p }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import {
  DevicePhoneMobileIcon,
  PaperAirplaneIcon,
  ArrowPathIcon,
  MegaphoneIcon,
  CakeIcon,
  ChatBubbleLeftEllipsisIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { fmtDate, fmtDateTime } from '../utils/date.js'

// ─── Tabs ────────────────────────────────────────────────────────────────────
const tabs = [
  { id: 'promotion', label: 'Promotion',      icon: MegaphoneIcon },
  { id: 'birthday',  label: 'Birthday Wishes', icon: CakeIcon },
  { id: 'custom',    label: 'Custom SMS',       icon: ChatBubbleLeftEllipsisIcon },
  { id: 'history',   label: 'SMS History',      icon: ClockIcon },
]
const activeTab = ref('promotion')

// ─── Sender ID (from shop settings) ─────────────────────────────────────────
const senderIdDisplay = ref('SMSlenzDEMO')

// ─── Promotion ───────────────────────────────────────────────────────────────
const allCustomers        = ref([])
const promoTarget         = ref('all')
const selectedCustomerIds = ref([])
const customerSearch      = ref('')
const promoSending        = ref(false)
const promoError          = ref('')
const promoSuccess        = ref('')
const promoForm           = reactive({ message: '', campaign_name: '' })

const filteredCustomers = computed(() =>
  customerSearch.value
    ? allCustomers.value.filter(c =>
        c.name.toLowerCase().includes(customerSearch.value.toLowerCase()) ||
        c.phone.includes(customerSearch.value)
      )
    : allCustomers.value
)

const promoRecipientCount = computed(() =>
  promoTarget.value === 'all' ? allCustomers.value.length : selectedCustomerIds.value.length
)

async function sendPromotion() {
  promoError.value = ''
  promoSuccess.value = ''
  if (!promoForm.message.trim()) { promoError.value = 'Message is required'; return }
  if (promoTarget.value === 'selected' && !selectedCustomerIds.value.length) {
    promoError.value = 'Select at least one customer'; return
  }
  promoSending.value = true
  try {
    const payload = {
      message: promoForm.message,
      campaign_name: promoForm.campaign_name || null,
    }
    if (promoTarget.value === 'selected') payload.customer_ids = selectedCustomerIds.value

    const { data } = await axios.post('/api/sms/send-promotion', payload)
    promoSuccess.value = data.message
    promoForm.message = ''
    promoForm.campaign_name = ''
  } catch (e) {
    promoError.value = e.response?.data?.message ?? 'Failed to send SMS'
  } finally {
    promoSending.value = false
  }
}

// ─── Birthday ─────────────────────────────────────────────────────────────────
const birthdayDate     = ref(new Date().toISOString().split('T')[0])
const birthdayCustomers = ref([])
const birthdaySending  = ref(false)
const birthdayError    = ref('')
const birthdaySuccess  = ref('')
const birthdayForm     = reactive({
  message: "Dear {Name}, wishing you a very Happy Birthday! 🎂 Thank you for being our valued customer. Visit us for a special birthday treat! — {ShopName}",
})

async function previewBirthdays() {
  const { data } = await axios.get('/api/sms/birthday-preview', { params: { date: birthdayDate.value } })
  birthdayCustomers.value = data.customers
}

async function sendBirthdays() {
  birthdayError.value = ''
  birthdaySuccess.value = ''
  if (!birthdayForm.message.trim()) { birthdayError.value = 'Message is required'; return }
  birthdaySending.value = true
  try {
    const { data } = await axios.post('/api/sms/send-birthdays', {
      message: birthdayForm.message,
      date: birthdayDate.value,
    })
    birthdaySuccess.value = data.message
  } catch (e) {
    birthdayError.value = e.response?.data?.message ?? 'Failed to send birthday wishes'
  } finally {
    birthdaySending.value = false
  }
}

// ─── Custom ──────────────────────────────────────────────────────────────────
const customContacts = ref('')
const customSending  = ref(false)
const customError    = ref('')
const customSuccess  = ref('')
const customForm     = reactive({ message: '', campaign_name: '' })

async function sendCustom() {
  customError.value = ''
  customSuccess.value = ''
  const contacts = customContacts.value
    .split('\n')
    .map(s => s.trim())
    .filter(Boolean)
  if (!contacts.length) { customError.value = 'Enter at least one phone number'; return }
  if (!customForm.message.trim()) { customError.value = 'Message is required'; return }

  customSending.value = true
  try {
    const { data } = await axios.post('/api/sms/send-custom', {
      contacts,
      message: customForm.message,
      campaign_name: customForm.campaign_name || null,
    })
    customSuccess.value = data.message
    customContacts.value = ''
    customForm.message = ''
    customForm.campaign_name = ''
  } catch (e) {
    customError.value = e.response?.data?.message ?? 'Failed to send SMS'
  } finally {
    customSending.value = false
  }
}

// ─── SMS History ─────────────────────────────────────────────────────────────
const smsLogs    = ref([])
const logsPage   = ref(1)
const logsMeta   = ref({ last_page: 1, from: 0, to: 0, total: 0 })
const logFilters = reactive({ type: '', status: '', from_date: '', to_date: '' })

async function loadLogs() {
  const { data } = await axios.get('/api/sms/logs', {
    params: { ...logFilters, page: logsPage.value }
  })
  smsLogs.value = data.data
  logsMeta.value = { last_page: data.last_page, from: data.from, to: data.to, total: data.total }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
const placeholders = [
  { label: 'Shop Name', value: '{ShopName}' },
  { label: 'Customer Name', value: '{Name}' },
  { label: 'Phone', value: '{Phone}' },
]

function insertText(form, text) {
  form.message += text
}

function typeBadge(type) {
  return {
    promotion: 'bg-blue-100 text-blue-700',
    birthday:  'bg-pink-100 text-pink-700',
    custom:    'bg-purple-100 text-purple-700',
  }[type] ?? 'bg-gray-100 text-gray-700'
}

function typeLabel(type) {
  return { promotion: 'Promotion', birthday: 'Birthday', custom: 'Custom' }[type] ?? type
}

function statusBadge(status) {
  return {
    sent:    'bg-green-100 text-green-700',
    partial: 'bg-amber-100 text-amber-700',
    failed:  'bg-red-100 text-red-700',
  }[status] ?? 'bg-gray-100 text-gray-600'
}

// ─── Mount ───────────────────────────────────────────────────────────────────
onMounted(async () => {
  const [custRes] = await Promise.all([
    axios.get('/api/sms/customer-list'),
    previewBirthdays(),
    loadLogs(),
  ])
  allCustomers.value = custRes.data.customers

  // Load sender ID from shop settings
  try {
    const { data } = await axios.get('/api/shop-settings')
    if (data.sms_sender_id) senderIdDisplay.value = data.sms_sender_id
  } catch {}
})
</script>
