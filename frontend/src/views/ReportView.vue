<template>
  <div class="report-page">
    <div class="report-container">
      <h1 class="report-title">Report an Item</h1>
      <p class="report-subtitle">Fill in the details below.</p>

      <!-- Mode toggle -->
      <div class="mode-toggle">
        <button type="button" :class="['mode-btn', mode === 'ai' ? 'active' : '']" @click="mode = 'ai'">
          ✨ AI Auto-fill
        </button>
        <button type="button" :class="['mode-btn', mode === 'manual' ? 'active' : '']" @click="mode = 'manual'">
          ✏️ Manual Entry
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="report-form">

        <!-- Photo upload -->
        <div class="form-group">
          <label>
            Photo
            <span v-if="mode === 'ai'" class="vision-badge">✨ AI Auto-fill</span>
          </label>
          <!-- Hidden inputs — always in DOM so refs are never null -->
          <input ref="fileInput"   type="file" accept="image/*"                         style="display:none" @change="handleFileChange" />
          <input ref="cameraInput" type="file" accept="image/*" capture="environment"   style="display:none" @change="handleFileChange" />

          <!-- Preview (shown after photo selected) -->
          <div v-if="previewUrl" class="photo-upload has-file" @click="fileInput.click()">
            <img :src="previewUrl" class="photo-preview" alt="preview" />
          </div>

          <!-- Upload / Camera buttons (shown before photo selected) -->
          <div v-else class="photo-options">
            <div class="photo-upload" @click="fileInput.click()" @dragover.prevent @drop.prevent="handleDrop">
              <span class="camera-icon">🖼️</span>
              <p>Click or drag to upload</p>
            </div>
            <div class="photo-upload" @click="openCamera">
              <span class="camera-icon">📷</span>
              <p>Take a photo</p>
            </div>
          </div>

          <!-- Vision loading state -->
          <div v-if="analyzing" class="vision-status">
            <span class="spinner"></span> Analyzing image...
          </div>
          <div v-if="visionDone" class="vision-success">
            ✅ Fields auto-filled from image — review and edit if needed
          </div>
        </div>

        <!-- Title -->
        <div class="form-group">
          <label>Title</label>
          <input v-model="form.title" type="text" required />
        </div>

        <!-- Description -->
        <div class="form-group">
          <label>Description</label>
          <textarea
            v-model="form.description"
            placeholder="Describe the item in detail — color, brand, size..."
            rows="4"
          ></textarea>
        </div>

        <!-- Category -->
        <div class="form-group">
          <label>Category</label>
          <select v-model="form.category">
            <option>Electronics</option>
            <option>Accessories</option>
            <option>ID / Card</option>
            <option>Clothing</option>
            <option>Books</option>
            <option>Others</option>
          </select>
        </div>

        <!-- Location -->
        <div class="form-group">
          <label>Location</label>
          <input v-model="form.location" type="text" placeholder="Where was it lost or found?" />
        </div>

        <!-- Date -->
        <div class="form-group">
          <label>Date</label>
          <input v-model="form.date" type="date" />
        </div>

        <!-- Type toggle -->
        <div class="form-group">
          <label>Type</label>
          <div class="type-toggle">
            <button
              type="button"
              :class="['type-btn', form.report_type === 'lost' ? 'active' : '']"
              @click="form.report_type = 'lost'"
            >I Lost This</button>
            <button
              type="button"
              :class="['type-btn', form.report_type === 'found' ? 'active' : '']"
              @click="form.report_type = 'found'"
            >I Found This</button>
          </div>
        </div>

        <!-- Success message -->
        <div v-if="success" class="form-success">
          ✅ Report submitted successfully! Redirecting...
        </div>

        <!-- Error message -->
        <p v-if="error" class="form-error">{{ error }}</p>

        <!-- Actions -->
        <div class="form-actions">
          <button type="button" class="btn-cancel" @click="$router.push('/')">Cancel</button>
          <button type="submit" class="btn-submit" :disabled="submitting || analyzing">
            {{ submitting ? 'Submitting...' : 'Submit Report' }}
          </button>
        </div>

      </form>

      <!-- Camera modal -->
      <div v-if="cameraOpen" class="camera-modal">
        <div class="camera-modal-inner">
          <video ref="videoEl" autoplay playsinline class="camera-video"></video>
          <canvas ref="canvasEl" style="display:none"></canvas>
          <div class="camera-actions">
            <button type="button" class="btn-capture" @click="capturePhoto">📷 Capture</button>
            <button type="button" class="btn-cancel-cam" @click="closeCamera">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const form = ref({
  title: '',
  description: '',
  category: 'Electronics',
  location: '',
  date: new Date().toISOString().split('T')[0],
  report_type: 'lost'
})

const mode = ref('ai')

const fileInput = ref(null)
const cameraInput = ref(null)
const videoEl = ref(null)
const canvasEl = ref(null)
const cameraOpen = ref(false)
let mediaStream = null
const previewUrl = ref(null)
const imageData = ref(null)
const analyzing = ref(false)
const visionDone = ref(false)
const submitting = ref(false)
const error = ref('')

// ── Category mapping from Vision labels ───────────────
const CATEGORY_MAP = {
  Electronics:  ['phone', 'mobile', 'laptop', 'computer', 'tablet', 'camera', 'headphone',
                 'earphone', 'charger', 'cable', 'keyboard', 'mouse', 'speaker', 'electronic',
                 'gadget', 'device', 'usb', 'battery', 'airpod', 'watch', 'smartwatch'],
  Accessories:  ['bag', 'backpack', 'handbag', 'wallet', 'purse', 'glasses', 'sunglasses',
                 'umbrella', 'jewelry', 'ring', 'necklace', 'bracelet', 'earring', 'key',
                 'keychain', 'belt', 'hat', 'cap', 'scarf', 'glove'],
  'ID / Card':  ['card', 'id', 'passport', 'license', 'document', 'identification', 'credit'],
  Clothing:     ['shirt', 'pants', 'jacket', 'coat', 'dress', 'shoe', 'sneaker', 'clothing',
                 'fashion', 'apparel', 'hoodie', 'sweater', 'sock', 'underwear'],
  Books:        ['book', 'notebook', 'textbook', 'journal', 'magazine', 'paper', 'pen',
                 'pencil', 'stationery', 'binder', 'folder']
}

function detectCategory(labels) {
  const text = labels.join(' ').toLowerCase()
  for (const [category, keywords] of Object.entries(CATEGORY_MAP)) {
    if (keywords.some(k => text.includes(k))) return category
  }
  return 'Others'
}


function toBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result.split(',')[1])
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

function rgbToColorName(r, g, b) {
  // Convert to HSL for more perceptual accuracy
  const rn = r / 255, gn = g / 255, bn = b / 255
  const max = Math.max(rn, gn, bn), min = Math.min(rn, gn, bn)
  const l = (max + min) / 2
  const s = max === min ? 0 : l > 0.5 ? (max - min) / (2 - max - min) : (max - min) / (max + min)
  let h = 0
  if (max !== min) {
    if (max === rn) h = ((gn - bn) / (max - min) + 6) % 6
    else if (max === gn) h = (bn - rn) / (max - min) + 2
    else h = (rn - gn) / (max - min) + 4
    h = h / 6
  }
  const hDeg = h * 360

  if (l < 0.12) return 'Black'
  if (l > 0.88 && s < 0.15) return 'White'
  if (s < 0.12) {
    if (l < 0.35) return 'Dark Gray'
    if (l < 0.65) return 'Gray'
    return 'Light Gray'
  }
  if (hDeg < 8  || hDeg >= 352) return 'Red'
  if (hDeg < 50 && l < 0.35) return 'Brown'
  if (hDeg < 50)  return 'Orange'
  if (hDeg < 70)  return 'Yellow'
  if (hDeg < 150) return 'Green'
  if (hDeg < 195) return 'Teal'
  if (hDeg < 255) return 'Blue'
  if (hDeg < 285) return 'Purple'
  if (hDeg < 352) return l > 0.6 ? 'Pink' : 'Purple'
  return 'Unknown'
}

async function analyzeWithGoogleVision(file) {
  const base64 = await toBase64(file)

  const { data } = await api.post('/vision/analyze', { image: base64 })

  if (data.error) throw new Error(data.error.message || data.error)

  const resp = data.responses?.[0] ?? {}

  const objects = (resp.localizedObjectAnnotations ?? []).map(o => o.name)
  const labels  = (resp.labelAnnotations ?? []).map(a => a.description)

  const colorEntries = resp.imagePropertiesAnnotation?.dominantColors?.colors ?? []
  const topColors = [...new Set(
    colorEntries
      .filter(c => (c.pixelFraction ?? 0) >= 0.05)
      .sort((a, b) => b.pixelFraction - a.pixelFraction)
      .slice(0, 3)
      .map(c => rgbToColorName(c.color.red ?? 0, c.color.green ?? 0, c.color.blue ?? 0))
      .filter(name => name !== 'Unknown')
  )]

  const topObject = objects[0] || labels[0] || ''
  const allLabels = [...new Set([...objects, ...labels])]

  return { topObject, allLabels, colors: topColors }
}

async function processFile(file) {
  if (!file || !file.type.startsWith('image/')) return
  previewUrl.value = URL.createObjectURL(file)
  imageData.value = await toBase64(file)
  visionDone.value = false

  if (mode.value !== 'ai') return

  analyzing.value = true
  try {
    const { topObject, allLabels } = await analyzeWithGoogleVision(file)

    if (topObject) form.value.title = topObject
    if (allLabels.length) {
      form.value.description = allLabels.slice(0, 6).join(', ')
      form.value.category = detectCategory(allLabels)
    }
    visionDone.value = true
  } catch (e) {
    console.error('Vision error:', e)
    error.value = e.message || 'Image analysis failed. Fill in the fields manually.'
  } finally {
    analyzing.value = false
  }
}

async function openCamera() {
  cameraOpen.value = true
  await nextTick()
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    videoEl.value.srcObject = mediaStream
  } catch {
    cameraOpen.value = false
    error.value = 'Camera access denied. Please allow camera permissions.'
  }
}

function closeCamera() {
  cameraOpen.value = false
  if (mediaStream) {
    mediaStream.getTracks().forEach(t => t.stop())
    mediaStream = null
  }
}

function capturePhoto() {
  const video = videoEl.value
  const canvas = canvasEl.value
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  canvas.getContext('2d').drawImage(video, 0, 0)
  const dataUrl = canvas.toDataURL('image/jpeg')
  previewUrl.value = dataUrl
  imageData.value = dataUrl.split(',')[1]
  closeCamera()

  if (mode.value === 'ai') {
    canvas.toBlob(blob => {
      processFile(new File([blob], 'camera.jpg', { type: 'image/jpeg' }))
    }, 'image/jpeg')
  }
}

function handleFileChange(e) {
  processFile(e.target.files[0])
}

function handleDrop(e) {
  processFile(e.dataTransfer.files[0])
}

const success = ref(false)

async function handleSubmit() {
  error.value = ''
  submitting.value = true
  try {
    await api.post('/items', { ...form.value, image: imageData.value })
    success.value = true
    setTimeout(() => router.push('/'), 2000)
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to submit report. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.report-page {
  background: var(--bg);
  min-height: calc(100vh - 60px);
  padding: 2.5rem 1rem 4rem;
}

.report-container {
  max-width: 600px;
  margin: 0 auto;
  background: white;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 2.25rem 2rem;
}

.report-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--ink);
  margin-bottom: 0.25rem;
}

.report-subtitle {
  color: var(--muted);
  font-size: 0.95rem;
  margin-bottom: 2rem;
}

/* ── Mode Toggle ──────────────────────────────────── */
.mode-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--bg);
  border-radius: 999px;
  padding: 4px;
  gap: 4px;
  margin-bottom: 1.75rem;
}

.mode-btn {
  padding: 0.7rem;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
  background: transparent;
  color: var(--muted);
  border: none;
  border-radius: 999px;
  transition: background 0.15s, color 0.15s;
}

.mode-btn.active {
  background: white;
  color: var(--primary);
  box-shadow: var(--shadow-sm);
}

/* ── Form fields ──────────────────────────────────── */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 0.5rem;
}

.vision-badge {
  font-size: 0.75rem;
  font-weight: 600;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 0.2rem 0.55rem;
  border-radius: 20px;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.7rem 0.9rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 0.95rem;
  color: var(--ink);
  background: var(--bg);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-light);
  background: white;
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

/* ── Photo Upload ─────────────────────────────────── */
.photo-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.photo-upload {
  border: 2px dashed var(--border);
  border-radius: var(--radius-md);
  padding: 2.5rem 1rem;
  text-align: center;
  cursor: pointer;
  background: var(--bg);
  transition: border-color 0.15s, background 0.15s, transform 0.15s;
}

.photo-upload:hover {
  border-color: var(--primary);
  background: var(--primary-50);
  transform: translateY(-2px);
}

.photo-upload input {
  display: none;
}

.camera-icon {
  font-size: 2.25rem;
  display: block;
  margin-bottom: 0.5rem;
}

.photo-upload p {
  font-size: 0.9rem;
  color: #888;
  margin: 0;
}

.upload-hint {
  font-size: 0.8rem !important;
  color: #aaa !important;
  margin-top: 0.25rem !important;
}

.photo-upload.has-file {
  padding: 0.5rem;
  border-style: solid;
  border-color: var(--primary);
}

.photo-preview {
  max-height: 220px;
  width: 100%;
  border-radius: 6px;
  object-fit: cover;
}

/* ── Vision status ────────────────────────────────── */
.vision-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.6rem;
  font-size: 0.88rem;
  color: #666;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid #ccc;
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.vision-success {
  margin-top: 0.6rem;
  font-size: 0.88rem;
  color: #2e7d32;
}

/* ── Type Toggle ──────────────────────────────────── */
.type-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem;
}

.type-btn {
  padding: 0.75rem;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  background: var(--bg);
  color: var(--ink-soft);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.type-btn.active {
  background: var(--primary-light);
  color: var(--primary);
  border-color: var(--primary);
}

/* ── Camera Modal ─────────────────────────────────── */
.camera-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.camera-modal-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: #1a1a1a;
  border-radius: 12px;
  width: min(480px, 95vw);
}

.camera-video {
  width: 100%;
  border-radius: 8px;
  background: #000;
}

.camera-actions {
  display: flex;
  gap: 1rem;
}

.btn-capture {
  padding: 0.75rem 2rem;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel-cam {
  padding: 0.75rem 2rem;
  background: #444;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
}

/* ── Success / Error ──────────────────────────────── */
.form-success {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #a5d6a7;
  border-radius: 6px;
  padding: 0.85rem 1rem;
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 1rem;
  text-align: center;
}

.form-error {
  color: var(--primary);
  font-size: 0.9rem;
  margin-bottom: 1rem;
}

/* ── Actions ──────────────────────────────────────── */
.form-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}

.btn-cancel {
  padding: 0.75rem 2rem;
  border-radius: var(--radius-sm);
  font-size: 0.95rem;
  font-weight: 700;
  background: white;
  color: var(--ink-soft);
  border: 1.5px solid var(--border);
  cursor: pointer;
  transition: background 0.15s;
}

.btn-cancel:hover { background: var(--bg); }

.btn-submit {
  padding: 0.75rem 2rem;
  border-radius: var(--radius-sm);
  font-size: 0.95rem;
  font-weight: 700;
  background: var(--primary);
  color: white;
  border: none;
  cursor: pointer;
  transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
}

.btn-submit:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
