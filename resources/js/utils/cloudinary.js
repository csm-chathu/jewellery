import axios from 'axios'

export function cloudinaryConfig() {
  return {
    defaultFolder: import.meta.env.VITE_CLOUDINARY_FOLDER || 'jewellery',
  }
}

export async function uploadToCloudinary(fileOrBlob, options = {}) {
  const { defaultFolder } = cloudinaryConfig()

  const formData = new FormData()
  formData.append('file', fileOrBlob)

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
