<template>
  <div class="profile-page">
    <div class="profile-container">
      <h1 class="profile-title">Edit Profile</h1>
      <p class="profile-subtitle">Update your account details.</p>

      <form @submit.prevent="handleSubmit" class="profile-form" novalidate>

        <div class="form-group">
          <label>Name</label>
          <input v-model="form.name" type="text" required />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input v-model="form.email" type="email" required />
        </div>

        <hr class="divider" />

        <h2 class="section-heading">Change Password</h2>
        <p class="section-hint">Leave blank to keep your current password.</p>

        <div class="form-group">
          <label>Current Password</label>
          <input v-model="form.currentPassword" type="password" placeholder="Required to change password" autocomplete="current-password" />
        </div>

        <div class="form-group">
          <label>New Password</label>
          <input v-model="form.newPassword" type="password" placeholder="At least 8 characters" autocomplete="new-password" />
        </div>

        <div class="form-group">
          <label>Confirm New Password</label>
          <input v-model="form.confirmPassword" type="password" placeholder="Repeat new password" autocomplete="new-password" />
        </div>

        <div v-if="success" class="form-success">✅ Profile updated successfully!</div>
        <p v-if="error" class="form-error">{{ error }}</p>

        <div class="form-actions">
          <button type="button" class="btn-cancel" @click="$router.push('/dashboard')">Cancel</button>
          <button type="submit" class="btn-submit" :disabled="submitting">
            {{ submitting ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/store/authStore'
import api from '@/services/api'

const auth = useAuthStore()

const form = ref({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

const submitting = ref(false)
const success = ref(false)
const error = ref('')

async function handleSubmit() {
  error.value = ''
  success.value = false

  if (form.value.newPassword) {
    if (!form.value.currentPassword) {
      error.value = 'Enter your current password to set a new one.'
      return
    }
    if (form.value.newPassword.length < 8) {
      error.value = 'New password must be at least 8 characters.'
      return
    }
    if (form.value.newPassword !== form.value.confirmPassword) {
      error.value = 'New passwords do not match.'
      return
    }
  }

  submitting.value = true
  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
    }
    if (form.value.newPassword) {
      payload.current_password = form.value.currentPassword
      payload.new_password = form.value.newPassword
    }

    const { data } = await api.put(`/users/${auth.user.id}`, payload)
    auth.updateUser(data.user)
    form.value.currentPassword = ''
    form.value.newPassword = ''
    form.value.confirmPassword = ''
    success.value = true
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to update profile. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.profile-page {
  background: var(--bg);
  min-height: calc(100vh - 60px);
  padding: 2.5rem 1rem 4rem;
}

.profile-container {
  max-width: 520px;
  margin: 0 auto;
  background: white;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  padding: 2.25rem 2rem;
}

.profile-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--ink);
  margin-bottom: 0.25rem;
}

.profile-subtitle {
  color: var(--muted);
  font-size: 0.95rem;
  margin-bottom: 2rem;
}

.divider {
  border: none;
  border-top: 1px solid var(--border);
  margin: 1.5rem 0;
}

.section-heading {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 0.2rem;
}

.section-hint {
  font-size: 0.85rem;
  color: var(--muted);
  margin-bottom: 1.25rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 0.5rem;
}

.form-group input {
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

.form-group input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-light);
  background: white;
}

.form-success {
  background: var(--accent-green-light);
  color: var(--accent-green);
  border: 1px solid #a5d6a7;
  border-radius: var(--radius-sm);
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
