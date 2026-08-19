import axios from 'axios'

export function cloudinaryConfig() {
  return {
    defaultFolder: import.meta.env.VITE_CLOUDINARY_FOLDER || 'jewellery',
  }
}

function compressFile(file, maxWidth = 1200, quality = 0.82) {
  return new Promise((resolve) => {
    const img = new Image()
    const url = URL.createObjectURL(file)
    img.onload = () => {
      URL.revokeObjectURL(url)
      const scale = Math.min(1, maxWidth / img.width)
      const w = Math.round(img.width * scale)
      const h = Math.round(img.height * scale)
      const canvas = document.createElement('canvas')
      canvas.width = w; canvas.height = h
      canvas.getContext('2d').drawImage(img, 0, 0, w, h)
      canvas.toBlob((blob) => resolve(blob || file), 'image/jpeg', quality)
    }
    img.onerror = () => { URL.revokeObjectURL(url); resolve(file) }
    img.src = url
  })
}

export async function deleteFromCloudinary(publicId) {
  if (!publicId) return
  try {
    await axios.delete('/api/uploads/cloudinary', { data: { public_id: publicId } })
  } catch (e) {
    console.warn('Cloudinary delete failed:', e)
  }
}

export async function uploadToCloudinary(fileOrBlob, options = {}) {
  const { defaultFolder } = cloudinaryConfig()

  // Only compress raw File objects — pre-processed Blobs (e.g. from SmartImageUploader) are skipped
  const source = fileOrBlob instanceof File
    ? await compressFile(fileOrBlob)
    : fileOrBlob

  const formData = new FormData()
  formData.append('file', source, source instanceof File ? source.name : 'photo.jpg')

  const folder = options.folder || defaultFolder
  if (folder) formData.append('folder', folder)

  if (options.tags?.length) {
    formData.append('tags', options.tags.join(','))
  }

  const { data: payload } = await axios.post('/api/uploads/cloudinary', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

  return {
    url: payload.url,
    public_id: payload.public_id,
    width: payload.width,
    height: payload.height,
    bytes: payload.bytes,
    format: payload.format,
  }
}
