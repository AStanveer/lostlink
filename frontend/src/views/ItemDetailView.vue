<template>
  <div class="item-detail-page">
    <div v-if="loading" class="loading">Loading item...</div>

    <div v-else-if="!item" class="empty-state">Item not found.</div>

    <div v-else class="detail-wrap">
      <!-- Breadcrumb -->
      <div class="breadcrumb">
        <RouterLink to="/">Home</RouterLink>
        <span> &rsaquo; </span>
        <RouterLink to="/items">Browse</RouterLink>
        <span> &rsaquo; </span>
        <span>{{ item.title }}</span>
      </div>

      <div class="detail-layout">
        <!-- Left: image + info -->
        <div class="detail-main">
          <div class="item-image">
            <img v-if="item.image_path" :src="`/api${item.image_path}`" :alt="item.title"/>
            <div v-else class="image-placeholder">📦</div>
          </div>

          <div class="item-info">
            <div class="title-row">
              <h1>{{ item.title }}</h1>
              <span class="badge" :class="item.report_type">{{ item.report_type }}</span>
              <span class="badge status" :class="item.status">{{ item.status }}</span>
            </div>

            <p class="description">{{ item.description }}</p>

            <div class="meta-grid">
              <div class="meta-block">
                <span class="meta-label">Category</span>
                <span class="meta-value">{{ item.category }}</span>
              </div>
              <div class="meta-block">
                <span class="meta-label">Location</span>
                <span class="meta-value">📍 {{ item.location }}</span>
              </div>
              <div class="meta-block">
                <span class="meta-label">Date</span>
                <span class="meta-value">{{ formatDate(item.date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: action card -->
        <div class="action-card">
          <p class="posted-by-label">Posted by</p>
          <p class="posted-by-email">{{ item.posted_by_name || item.posted_by_email || 'Campus user' }}</p>
          <p class="posted-date">{{ formatDate(item.date) }}</p>

          <hr class="divider"/>

          <!-- Can claim if: item is found by someone else and still active -->
          <div v-if="canClaim">
            <p class="action-hint">Think this is yours? Submit a claim with proof of ownership.</p>
            <button class="btn btn-primary full-width" @click="showClaimModal = true">
              Submit Claim
            </button>
          </div>

          <!-- Own found item — show manage message -->
          <div v-else-if="isOwnItem && item.report_type === 'found'">
            <p class="action-hint">This is your found item listing. Manage incoming claims from your Dashboard.</p>
            <RouterLink to="/dashboard" class="btn btn-outline full-width">Go to Dashboard</RouterLink>
          </div>

          <!-- Already claimed -->
          <div v-else-if="item.status === 'claimed'">
            <p class="action-hint claimed-text">This item has already been claimed.</p>
          </div>

          <!-- Lost item — suggest viewing matches -->
          <div v-else-if="item.report_type === 'lost' && !isOwnItem">
            <p class="action-hint">This is a lost item report. If you found it, report it via the Report Item page.</p>
            <RouterLink to="/report" class="btn btn-outline full-width">Report Found Item</RouterLink>
          </div>

          <!-- Lost item, own report -->
          <div v-else-if="item.report_type === 'lost' && isOwnItem">
            <p class="action-hint">This is your lost item report. Check for matches in your Dashboard.</p>
            <RouterLink to="/dashboard" class="btn btn-outline full-width">View Matches</RouterLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Claim Modal -->
    <Teleport to="body">
      <div v-if="showClaimModal" class="modal-overlay" @click.self="showClaimModal = false">
        <div class="modal">
          <div class="modal-header">
            <h2>Submit a Claim</h2>
            <button class="modal-close" @click="showClaimModal = false">✕</button>
          </div>

          <!-- Item comparison -->
          <div class="claim-comparison">
            <div class="compare-side">
              <p class="compare-label">ITEM YOU'RE CLAIMING</p>
              <p class="compare-title">{{ item?.title }}</p>
              <p class="compare-meta">{{ item?.category }} · {{ item?.location }}</p>
            </div>
          </div>

          <form class="claim-form" @submit.prevent="submitClaim">
            <div class="form-group">
              <label>Provide specific details only the true owner would know *</label>
              <textarea
                  v-model="claimForm.description"
                  rows="4"
                  placeholder="Describe identifying details — serial number, contents, markings, colour, brand..."
                  required
              ></textarea>
              <p v-if="claimErrors.description" class="field-error">{{ claimErrors.description }}</p>
            </div>

            <div class="form-group">
              <label>Upload proof (optional)</label>
              <div class="upload-zone" @click="triggerFileInput">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    style="display:none"
                    @change="handleProofUpload"
                />
                <div v-if="proofPreview">
                  <img :src="proofPreview" class="proof-preview" alt="Proof preview"/>
                  <p class="upload-hint">Click to change</p>
                </div>
                <div v-else>
                  <p class="upload-icon">📷</p>
                  <p class="upload-hint">Click to upload a photo of you with the item</p>
                </div>
              </div>
            </div>

            <p v-if="claimError" class="form-error">{{ claimError }}</p>
            <p v-if="claimSuccess" class="form-success">{{ claimSuccess }}</p>

            <div class="modal-actions">
              <button type="button" class="btn btn-outline" @click="showClaimModal = false">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                {{ submitting ? 'Submitting...' : 'Confirm Claim' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import {onMounted} from 'vue'
import {useRoute} from 'vue-router'
import api from '@/services/api'
import {useItemDetailStore} from "@/store/itemDetailStore";
import {storeToRefs} from "pinia";

const route = useRoute()
const itemStore = useItemDetailStore();
const {
  item,
  loading,
  submitting,
  fileInput,
  proofPreview,
  proofBase64,
  claimErrors,
  claimError,
  claimSuccess,
  claimForm,
  lostItemId,
  showClaimModal,
  isOwnItem,
  canClaim,
  toast
} = storeToRefs(itemStore);


onMounted(async () => {
  try {
    const res = await api.get(`/items/${route.params.id}`)
    item.value = res.data
  } catch (e) {
    item.value = null
  } finally {
    loading.value = false
  }
})

function triggerFileInput() {
  fileInput.value?.click();
}

function handleProofUpload(e) {
  const file = e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (ev) => {
    proofPreview.value = ev.target.result
    proofBase64.value = ev.target.result.split(',')[1] // strip data URI prefix
  }
  reader.readAsDataURL(file)
}

async function submitClaim() {
  claimErrors.value = {}
  claimError.value = ''
  claimSuccess.value = ''

  if (!claimForm.value.description.trim()) {
    claimErrors.value.description = 'Please describe why this item belongs to you.'
    return
  }

  submitting.value = true
  try {
    await api.post('/claims', {
      item_id: item.value.item_id,
      description: claimForm.value.description,
      proof: proofBase64.value || undefined,
      lost_item_id: lostItemId.value || undefined,
    })
    claimSuccess.value = 'Claim submitted successfully. The item owner will review your request.'
    showToast('Claim submitted successfully', 'success')
    setTimeout(() => {
      showClaimModal.value = false
    }, 2000)
  } catch (e) {
    claimError.value = e.response?.data?.error || 'Failed to submit claim. Please try again.'
  } finally {
    submitting.value = false;
  }
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('en-MY', {day: 'numeric', month: 'short', year: 'numeric'}) : '—'
}

function showToast(message, type = 'success') {
  itemStore.toast = {message, type}
  setTimeout(() => {
    itemStore.toast = {message: '', type: ''}
  }, 3500)
}
</script>

<style scoped>
.item-detail-page {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem 1.5rem 4rem;
}

.breadcrumb {
  font-size: 0.85rem;
  color: #999;
  margin-bottom: 1.75rem;
}

.breadcrumb a {
  color: #CC0001;
  text-decoration: none;
}

.breadcrumb a:hover {
  text-decoration: underline;
}

.detail-layout {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

.detail-main {
  flex: 1;
}

.item-image {
  width: 100%;
  aspect-ratio: 4/3;
  border-radius: 12px;
  overflow: hidden;
  background: #f5f5f5;
  border: 1px solid #eee;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  font-size: 4rem;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}

.title-row h1 {
  font-size: 1.6rem;
  font-weight: 800;
  color: #1a1a1a;
  margin: 0;
}

.description {
  color: #444;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.meta-grid {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}

.meta-block {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.meta-label {
  font-size: 0.75rem;
  color: #999;
  font-weight: 600;
  text-transform: uppercase;
}

.meta-value {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1a1a1a;
}

/* Action card */
.action-card {
  width: 280px;
  flex-shrink: 0;
  border: 1px solid #e5e5e5;
  border-radius: 12px;
  padding: 1.5rem;
  background: white;
  position: sticky;
  top: 5rem;
}

.posted-by-label {
  font-size: 0.75rem;
  color: #999;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.2rem;
}

.posted-by-email {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.15rem;
}

.posted-date {
  font-size: 0.82rem;
  color: #999;
}

.divider {
  border: none;
  border-top: 1px solid #eee;
  margin: 1rem 0;
}

.action-hint {
  font-size: 0.85rem;
  color: #555;
  line-height: 1.5;
  margin-bottom: 1rem;
}

.claimed-text {
  color: #999;
}

.full-width {
  width: 100%;
  text-align: center;
  display: block;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: capitalize;
}

.badge.lost {
  background: #fff0f0;
  color: #CC0001;
}

.badge.found {
  background: #eff6ff;
  color: #1d4ed8;
}

.badge.active {
  background: #f0fdf4;
  color: #15803d;
}

.badge.claimed {
  background: #f5f3ff;
  color: #7c3aed;
}

/* Buttons */
.btn {
  display: inline-block;
  padding: 0.65rem 1.4rem;
  border-radius: 6px;
  font-size: 0.92rem;
  font-weight: 600;
  cursor: pointer;
  border: 2px solid transparent;
  text-decoration: none;
  transition: all 0.15s;
}

.btn-primary {
  background: #CC0001;
  color: white;
  border-color: #CC0001;
}

.btn-primary:hover:not(:disabled) {
  background: #a80001;
  border-color: #a80001;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-outline {
  background: white;
  color: #CC0001;
  border-color: #CC0001;
}

.btn-outline:hover {
  background: #fff0f0;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 14px;
  padding: 2rem;
  width: 100%;
  max-width: 540px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #1a1a1a;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.1rem;
  color: #999;
  cursor: pointer;
}

.modal-close:hover {
  color: #1a1a1a;
}

.claim-comparison {
  background: #fafafa;
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.compare-label {
  font-size: 0.7rem;
  font-weight: 700;
  color: #999;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.compare-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.2rem;
}

.compare-meta {
  font-size: 0.85rem;
  color: #666;
}

.claim-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group label {
  font-size: 0.88rem;
  font-weight: 600;
  color: #333;
}

.form-group textarea {
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 0.92rem;
  resize: vertical;
  font-family: inherit;
  transition: border-color 0.15s;
}

.form-group textarea:focus {
  outline: none;
  border-color: #CC0001;
}

.upload-zone {
  border: 2px dashed #e5e5e5;
  border-radius: 10px;
  padding: 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.15s;
}

.upload-zone:hover {
  border-color: #CC0001;
}

.upload-icon {
  font-size: 1.75rem;
  margin-bottom: 0.4rem;
}

.upload-hint {
  font-size: 0.83rem;
  color: #999;
  margin: 0;
}

.proof-preview {
  max-height: 140px;
  border-radius: 8px;
  margin-bottom: 0.4rem;
}

.field-error {
  color: #CC0001;
  font-size: 0.82rem;
  margin: 0;
}

.form-error {
  color: #CC0001;
  font-size: 0.88rem;
  text-align: center;
}

.form-success {
  color: #15803d;
  font-size: 0.88rem;
  text-align: center;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  z-index: 9999;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.toast.success {
  background: #15803d;
  color: white;
}

.toast.error {
  background: #CC0001;
  color: white;
}

.loading, .empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

@media (max-width: 700px) {
  .detail-layout {
    flex-direction: column;
  }

  .action-card {
    width: 100%;
    position: static;
  }
}
</style>