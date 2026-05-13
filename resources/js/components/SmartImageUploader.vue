<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between gap-2">
      <p class="text-sm font-medium text-gray-700">{{ label }}</p>
      <div class="flex items-center gap-2">
        <button type="button" class="btn-secondary text-xs py-1 px-2" @click="triggerPick">Choose Image</button>
        <button type="button" class="btn-secondary text-xs py-1 px-2" @click="toggleCamera">
          {{ cameraOn ? 'Stop Camera' : 'Use Camera' }}
        </button>
      </div>
    </div>

    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      :multiple="multiple"
      class="hidden"
      @change="onPick"
    />

    <div v-if="cameraOn" class="rounded-xl border bg-gray-50 p-3 space-y-2">
      <video ref="videoEl" class="w-full max-h-56 rounded-lg bg-black" autoplay playsinline muted></video>
      <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500">Capture from webcam or laptop camera</p>
        <button type="button" class="btn-primary text-xs py-1 px-2" @click="capturePhoto">Capture</button>
      </div>
    </div>

    <div v-if="items.length" class="grid grid-cols-2 md:grid-cols-3 gap-3">
      <div v-for="item in items" :key="item.id" class="rounded-xl border overflow-hidden bg-white">
        <img :src="item.preview" class="w-full h-28 object-cover" />
        <div class="p-2 space-y-2">
          <p class="text-[11px] text-gray-500 truncate">{{ item.name }}</p>
          <div class="flex flex-wrap gap-1">
            <span v-if="item.status === 'uploaded'" class="badge bg-green-100 text-green-700 text-[10px]">Uploaded</span>
            <span v-else-if="item.status === 'uploading'" class="badge bg-amber-100 text-amber-700 text-[10px]">Uploading...</span>
            <span v-else class="badge bg-gray-100 text-gray-600 text-[10px]">Pending</span>
          </div>
          <div class="flex gap-1">
            <button type="button" class="btn-secondary text-xs py-1 px-2" @click="openEditor(item)">Edit</button>
            <button type="button" class="btn-secondary text-xs py-1 px-2" :disabled="item.status==='uploading'" @click="uploadItem(item)">Upload</button>
            <button type="button" class="btn-secondary text-xs py-1 px-2 text-red-600" @click="removeItem(item.id)">Remove</button>
          </div>
        </div>
      </div>
    </div>

    <p v-if="error" class="text-xs text-red-600 bg-red-50 border border-red-100 px-2 py-1 rounded">{{ error }}</p>

    <teleport to="body">
      <div v-if="editing" class="fixed inset-0 z-[80] bg-black/50 flex items-center justify-center p-4" @click.self="closeEditor">
        <div class="bg-white rounded-xl w-full max-w-3xl shadow-2xl overflow-hidden">
          <div class="px-4 py-3 border-b flex items-center justify-between">
            <h4 class="font-semibold text-sm">Edit Image</h4>
            <button type="button" class="text-gray-500" @click="closeEditor">Close</button>
          </div>
          <div class="p-4 grid md:grid-cols-[2fr_1fr] gap-4">
            <div class="rounded-lg border bg-gray-50 p-2 flex items-center justify-center">
              <canvas ref="editorCanvas" class="max-h-[420px] max-w-full"></canvas>
            </div>
            <div class="space-y-2">
              <label class="text-xs text-gray-500 block">Brightness {{ editor.brightness }}%</label>
              <input v-model.number="editor.brightness" type="range" min="50" max="150" @input="renderEditor" class="w-full" />

              <label class="text-xs text-gray-500 block">Contrast {{ editor.contrast }}%</label>
              <input v-model.number="editor.contrast" type="range" min="50" max="150" @input="renderEditor" class="w-full" />

              <label class="text-xs text-gray-500 block">Saturation {{ editor.saturation }}%</label>
              <input v-model.number="editor.saturation" type="range" min="50" max="150" @input="renderEditor" class="w-full" />

              <label class="text-xs text-gray-500 block">Crop Width {{ editor.cropWidth }}%</label>
              <input v-model.number="editor.cropWidth" type="range" min="40" max="100" @input="renderEditor" class="w-full" />

              <label class="text-xs text-gray-500 block">Crop Height {{ editor.cropHeight }}%</label>
              <input v-model.number="editor.cropHeight" type="range" min="40" max="100" @input="renderEditor" class="w-full" />

              <label class="text-xs text-gray-500 block">Max Width {{ editor.maxWidth }}px</label>
              <input v-model.number="editor.maxWidth" type="number" min="320" max="2560" class="form-input" />

              <label class="text-xs text-gray-500 block">Quality {{ editor.quality }}%</label>
              <input v-model.number="editor.quality" type="range" min="40" max="95" class="w-full" />
            </div>
          </div>
          <div class="px-4 py-3 border-t flex justify-end gap-2 bg-gray-50">
            <button type="button" class="btn-secondary" @click="closeEditor">Cancel</button>
            <button type="button" class="btn-primary" @click="applyEditor">Apply</button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { uploadToCloudinary } from '@/utils/cloudinary'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: 'Images' },
  multiple: { type: Boolean, default: false },
  maxItems: { type: Number, default: 10 },
  folder: { type: String, default: '' },
  tags: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)
const videoEl = ref(null)
const editorCanvas = ref(null)
const cameraOn = ref(false)
const streamRef = ref(null)
const error = ref('')

const items = ref([])
const editing = ref(null)
const editorImage = ref(null)

const editor = reactive({
  brightness: 100,
  contrast: 100,
  saturation: 100,
  cropWidth: 100,
  cropHeight: 100,
  maxWidth: 1200,
  quality: 82,
})

watch(
  () => props.modelValue,
  (val) => {
    const uploaded = (val || []).map((m, idx) => ({
      id: `remote-${idx}-${m.public_id || m.url}`,
      name: m.public_id || `image-${idx + 1}`,
      status: 'uploaded',
      preview: m.url,
      uploaded: m,
      file: null,
      processedBlob: null,
    }))

    const pending = items.value.filter(i => i.status !== 'uploaded')
    items.value = [...uploaded, ...pending]
  },
  { immediate: true, deep: true }
)

function triggerPick() {
  fileInput.value?.click()
}

async function onPick(e) {
  const files = Array.from(e.target.files || [])
  e.target.value = ''
  if (!files.length) return

  addFiles(files)
}

function addFiles(files) {
  error.value = ''
  const available = props.multiple ? props.maxItems - items.value.length : 1
  const selected = files.slice(0, Math.max(0, available))

  if (!props.multiple) {
    items.value = []
  }

  for (const file of selected) {
    const id = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
    items.value.push({
      id,
      name: file.name,
      file,
      status: 'pending',
      preview: URL.createObjectURL(file),
      processedBlob: null,
      uploaded: null,
    })
  }
}

async function toggleCamera() {
  if (cameraOn.value) {
    stopCamera()
    return
  }

  error.value = ''
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    streamRef.value = stream
    cameraOn.value = true
    await nextTick()
    if (videoEl.value) videoEl.value.srcObject = stream
  } catch (e) {
    error.value = 'Unable to access camera. Check permissions and device availability.'
  }
}

function stopCamera() {
  if (streamRef.value) {
    streamRef.value.getTracks().forEach(t => t.stop())
  }
  streamRef.value = null
  cameraOn.value = false
}

function capturePhoto() {
  if (!videoEl.value) return
  const video = videoEl.value
  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth || 1280
  canvas.height = video.videoHeight || 720
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

  canvas.toBlob((blob) => {
    if (!blob) return
    const file = new File([blob], `camera-${Date.now()}.jpg`, { type: 'image/jpeg' })
    addFiles([file])
  }, 'image/jpeg', 0.92)
}

function removeItem(id) {
  const item = items.value.find(x => x.id === id)
  if (item?.preview?.startsWith('blob:')) URL.revokeObjectURL(item.preview)
  items.value = items.value.filter(x => x.id !== id)
  syncUploaded()
}

function openEditor(item) {
  editing.value = item
  editorImage.value = new Image()
  editorImage.value.onload = () => renderEditor()
  editorImage.value.src = item.preview
  editor.brightness = 100
  editor.contrast = 100
  editor.saturation = 100
  editor.cropWidth = 100
  editor.cropHeight = 100
  editor.maxWidth = 1200
  editor.quality = 82
}

function closeEditor() {
  editing.value = null
  editorImage.value = null
}

function renderEditor() {
  if (!editorCanvas.value || !editorImage.value) return

  const img = editorImage.value
  const cw = Math.round(img.width * (editor.cropWidth / 100))
  const ch = Math.round(img.height * (editor.cropHeight / 100))
  const sx = Math.round((img.width - cw) / 2)
  const sy = Math.round((img.height - ch) / 2)

  const scale = Math.min(1, editor.maxWidth / cw)
  const outW = Math.max(1, Math.round(cw * scale))
  const outH = Math.max(1, Math.round(ch * scale))

  const canvas = editorCanvas.value
  canvas.width = outW
  canvas.height = outH

  const ctx = canvas.getContext('2d')
  ctx.filter = `brightness(${editor.brightness}%) contrast(${editor.contrast}%) saturate(${editor.saturation}%)`
  ctx.drawImage(img, sx, sy, cw, ch, 0, 0, outW, outH)
}

function applyEditor() {
  if (!editing.value || !editorCanvas.value) return

  editorCanvas.value.toBlob((blob) => {
    if (!blob || !editing.value) return

    const item = editing.value
    if (item.preview?.startsWith('blob:')) URL.revokeObjectURL(item.preview)
    item.processedBlob = blob
    item.preview = URL.createObjectURL(blob)
    item.status = 'pending'
    item.uploaded = null
    closeEditor()
  }, 'image/jpeg', editor.quality / 100)
}

async function uploadItem(item) {
  if (item.status === 'uploading') return

  item.status = 'uploading'
  error.value = ''

  try {
    const source = item.processedBlob || item.file
    const uploaded = await uploadToCloudinary(source, {
      folder: props.folder,
      tags: props.tags,
    })
    item.uploaded = uploaded
    item.status = 'uploaded'
    syncUploaded()
  } catch (e) {
    item.status = 'pending'
    error.value = e.message || 'Failed to upload image'
  }
}

async function uploadPending() {
  for (const item of items.value) {
    if (item.status !== 'uploaded') {
      await uploadItem(item)
    }
  }
  return items.value.filter(i => i.uploaded).map(i => i.uploaded)
}

function syncUploaded() {
  const uploaded = items.value.filter(i => i.uploaded).map(i => i.uploaded)
  emit('update:modelValue', uploaded)
}

onBeforeUnmount(() => {
  stopCamera()
  for (const item of items.value) {
    if (item.preview?.startsWith('blob:')) URL.revokeObjectURL(item.preview)
  }
})

defineExpose({ uploadPending })
</script>
