<template>
  <div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between mb-2">
      <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <Cog6ToothIcon class="w-6 h-6 text-amber-500" /> Shop Settings
      </h2>
    </div>

    <div class="card space-y-5">
      <h3 class="font-semibold text-gray-700 border-b border-gray-100 pb-2">Business Information</h3>

      <!-- Logo upload -->
      <div>
        <label class="form-label">Shop Logo</label>
        <div class="flex items-center gap-4">
          <div class="w-20 h-20 border-2 border-dashed border-gray-200 rounded-xl flex items-center justify-center bg-gray-50 shrink-0 overflow-hidden">
            <img v-if="form.logo_url" :src="form.logo_url" alt="Logo" class="w-full h-full object-contain p-1" />
            <PhotoIcon v-else class="w-8 h-8 text-gray-300" />
          </div>
          <div class="flex-1 space-y-2">
            <label class="flex items-center gap-2 cursor-pointer">
              <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />
              <button type="button" @click="logoInput.click()"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <ArrowUpTrayIcon class="w-4 h-4" />
                {{ logoUploading ? 'Uploading…' : 'Choose Image' }}
              </button>
            </label>
            <p class="text-xs text-gray-400">PNG, JPG, GIF or WebP · max 2 MB</p>
            <p v-if="logoError" class="text-xs text-red-500">{{ logoError }}</p>
          </div>
        </div>
      </div>

      <div>
        <label class="form-label">Shop / Jewellery Name <span class="text-red-400">*</span></label>
        <input v-model="form.shop_name" type="text" placeholder="e.g. Royal Gems Jewellers" class="form-input" maxlength="200" />
      </div>

      <div>
        <label class="form-label">Address</label>
        <textarea v-model="form.address" rows="3" placeholder="Street, City, Province" class="form-input resize-none"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Phone Number</label>
          <input v-model="form.phone" type="text" placeholder="+94 XX XXX XXXX" class="form-input" maxlength="50" />
        </div>
        <div>
          <label class="form-label">BR / Business Reg. No.</label>
          <input v-model="form.br_number" type="text" placeholder="BR/SP/XXXXXXXXXX" class="form-input" maxlength="100" />
        </div>
      </div>
    </div>

    <!-- Print Settings -->
    <div class="card space-y-4">
      <h3 class="font-semibold text-gray-700 border-b border-gray-100 pb-2">Print Settings</h3>
      <div>
        <label class="form-label">Default Print Format</label>
        <div class="grid grid-cols-2 gap-3 mt-1">
          <button type="button"
            @click="form.print_mode = 'pos'"
            :class="form.print_mode === 'pos'
              ? 'border-amber-500 bg-amber-50 text-amber-800 ring-2 ring-amber-400'
              : 'border-gray-200 hover:border-gray-300 text-gray-600'"
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl transition-all text-sm font-medium">
            <PrinterIcon class="w-7 h-7" />
            <span>POS / Thermal</span>
            <span class="text-xs font-normal text-gray-400">76mm roll receipt</span>
          </button>
          <button type="button"
            @click="form.print_mode = 'a5'"
            :class="form.print_mode === 'a5'
              ? 'border-amber-500 bg-amber-50 text-amber-800 ring-2 ring-amber-400'
              : 'border-gray-200 hover:border-gray-300 text-gray-600'"
            class="flex flex-col items-center gap-2 p-4 border-2 rounded-xl transition-all text-sm font-medium">
            <DocumentTextIcon class="w-7 h-7" />
            <span>A5 Paper Invoice</span>
            <span class="text-xs font-normal text-gray-400">148 × 210mm full bill</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Save -->
    <div class="flex items-center gap-3">
      <button @click="save" :disabled="saving"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white rounded-lg font-medium transition-colors">
        <ArrowPathIcon v-if="saving" class="w-4 h-4 animate-spin" />
        <CheckCircleIcon v-else class="w-4 h-4" />
        {{ saving ? 'Saving…' : 'Save Settings' }}
      </button>
      <span v-if="saved" class="text-sm text-green-600 flex items-center gap-1">
        <CheckCircleIcon class="w-4 h-4" /> Saved!
      </span>
      <span v-if="error" class="text-sm text-red-600">{{ error }}</span>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { uploadToCloudinary } from '@/utils/cloudinary'
import {
  Cog6ToothIcon, PrinterIcon, DocumentTextIcon,
  ArrowPathIcon, CheckCircleIcon, PhotoIcon, ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'

const form = ref({
  shop_name:       '',
  address:         '',
  phone:           '',
  br_number:       '',
  logo_url:        '',
  logo_public_id:  '',
  print_mode:      'pos',
})
const saving       = ref(false)
const saved        = ref(false)
const error        = ref('')
const logoInput    = ref(null)
const logoUploading = ref(false)
const logoError    = ref('')

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/shop-settings')
    Object.keys(form.value).forEach(k => {
      if (data[k] !== undefined && data[k] !== null) form.value[k] = data[k]
    })
  } catch {
    // settings table may be empty on first load
  }
})

async function onLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  logoError.value     = ''
  logoUploading.value = true
  try {
    const result = await uploadToCloudinary(file, { folder: 'shop_logo', tags: ['shop_logo'] })
    form.value.logo_url       = result.url
    form.value.logo_public_id = result.public_id
  } catch (err) {
    logoError.value = err.response?.data?.message ?? err.message ?? 'Upload failed.'
  } finally {
    logoUploading.value = false
    e.target.value = ''
  }
}

async function save() {
  saving.value = true
  saved.value  = false
  error.value  = ''
  try {
    await axios.post('/api/shop-settings', form.value)
    saved.value = true
    setTimeout(() => { saved.value = false }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save settings.'
  } finally {
    saving.value = false
  }
}
</script>
