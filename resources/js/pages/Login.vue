<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 to-gray-800 py-12 px-4">
    <div class="max-w-md w-full">
      <div class="text-center mb-8">
        <img
          v-if="branding.logo_url"
          :src="branding.logo_url"
          alt="Shop logo"
          class="h-16 w-auto mx-auto object-contain"
        />
        <span v-else class="text-6xl">💎</span>
        <h2 class="mt-4 text-3xl font-bold text-white">{{ branding.shop_name }}</h2>
        <p class="mt-2 text-gray-400">Sign in to your account</p>
      </div>

      <div class="card">
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" required class="form-input" placeholder="admin@jewellery.com" />
          </div>
          <div>
            <label class="form-label">Password</label>
            <input v-model="form.password" type="password" required class="form-input" placeholder="••••••••" />
          </div>
          <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
          <button type="submit" :disabled="loading" class="btn-primary w-full">
            {{ loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()
const form   = reactive({ email: '', password: '' })
const error  = ref('')
const loading = ref(false)
const branding = ref({
  shop_name: import.meta.env.VITE_APP_NAME ?? 'Jewellery Store',
  logo_url: '',
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/shop-branding')
    if (data?.shop_name) branding.value.shop_name = data.shop_name
    if (data?.logo_url) branding.value.logo_url = data.logo_url
  } catch {
    // Keep fallback branding if API is unavailable.
  }
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>
