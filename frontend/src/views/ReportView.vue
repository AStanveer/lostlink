<template>
  <div class="report-page">
    <div class="report-container">
      <h1 class="report-title">Report an Item</h1>
      <p class="report-subtitle">Fill in the details below.</p>

      <form @submit.prevent="handleSubmit" class="report-form">

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

        <!-- Photo upload -->
        <div class="form-group">
          <label>Photo</label>
          <div
            class="photo-upload"
            :class="{ 'has-file': previewUrl }"
            @click="fileInput.click()"
            @dragover.prevent
            @drop.prevent="handleDrop"
          >
            <img v-if="previewUrl" :src="previewUrl" class="photo-preview" alt="preview" />
            <template v-else>
              <span class="camera-icon">📷</span>
              <p>Click or drag to upload a photo</p>
            </template>
            <input ref="fileInput" type="file" accept="image/*" @change="handleFileChange" />
          </div>
        </div>

        <!-- Error message -->
        <p v-if="error" class="form-error">{{ error }}</p>

        <!-- Actions -->
        <div class="form-actions">
          <button type="button" class="btn-cancel" @click="$router.push('/')">Cancel</button>
          <button type="submit" class="btn-submit" :disabled="submitting">
            {{ submitting ? 'Submitting...' : 'Submit Report' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
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

const fileInput = ref(null)
const previewUrl = ref(null)
const imageFile = ref(null)
const submitting = ref(false)
const error = ref('')

function handleFileChange(e) {
  const file = e.target.files[0]
  if (file) {
    imageFile.value = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

function handleDrop(e) {
  const file = e.dataTransfer.files[0]
  if (file && file.type.startsWith('image/')) {
    imageFile.value = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

async function handleSubmit() {
  error.value = ''
  submitting.value = true
  try {
    await api.post('/items', form.value)
    router.push('/items')
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to submit report. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.report-page {
  background: #fff;
  min-height: calc(100vh - 60px);
  padding: 2.5rem 1rem 4rem;
}

.report-container {
  max-width: 600px;
  margin: 0 auto;
}

.report-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

.report-subtitle {
  color: #666;
  font-size: 0.95rem;
  margin-bottom: 2rem;
}

/* ── Form fields ──────────────────────────────────── */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.7rem 0.9rem;
  border: 1px solid #d0d0d0;
  border-radius: 6px;
  font-size: 0.95rem;
  color: #333;
  background: #fff;
  outline: none;
  transition: border-color 0.15s;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color: var(--primary);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

/* ── Type Toggle ──────────────────────────────────── */
.type-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border: 1px solid #d0d0d0;
  border-radius: 6px;
  overflow: hidden;
}

.type-btn {
  padding: 0.75rem;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  background: #fff;
  color: #555;
  border: none;
  transition: background 0.15s, color 0.15s;
}

.type-btn:first-child {
  border-right: 1px solid #d0d0d0;
}

.type-btn.active {
  background: #fff0f0;
  color: var(--primary);
  outline: 2px solid var(--primary);
  outline-offset: -2px;
}

/* ── Photo Upload ─────────────────────────────────── */
.photo-upload {
  border: 2px dashed #d0d0d0;
  border-radius: 8px;
  padding: 2.5rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.15s;
}

.photo-upload:hover {
  border-color: var(--primary);
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
}

.photo-upload.has-file {
  padding: 0.5rem;
  border-style: solid;
  border-color: var(--primary);
}

.photo-preview {
  max-height: 200px;
  border-radius: 6px;
  object-fit: cover;
}

/* ── Error ────────────────────────────────────────── */
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
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 600;
  background: #fff;
  color: #333;
  border: 1px solid #d0d0d0;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-cancel:hover {
  background: #f5f5f5;
}

.btn-submit {
  padding: 0.75rem 2rem;
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 600;
  background: var(--primary);
  color: white;
  border: none;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-submit:hover:not(:disabled) {
  background: var(--primary-dark);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
