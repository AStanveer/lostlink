<template>
  <div class="auth-layout">

    <!-- Left branding panel -->
    <div class="auth-panel">
      <div class="panel-content">
        <div class="panel-logo">🔗</div>
        <h1>LostLink</h1>
        <p>Campus lost and found, simplified. Reuniting UTM students with their belongings.</p>
        <div class="panel-features">
          <div class="panel-feature">✅ Report lost or found items</div>
          <div class="panel-feature">🔍 Smart item matching</div>
          <div class="panel-feature">🔒 Secure claim verification</div>
        </div>
      </div>
    </div>

    <!-- Right form panel -->
    <div class="auth-form-side">
      <div class="auth-card">
        <h2>Create account</h2>
        <p class="auth-sub">Join LostLink to report and recover lost items</p>

        <!-- Tabs -->
        <div class="auth-tabs">
          <RouterLink to="/login" class="tab">Login</RouterLink>
          <button class="tab active">Register</button>
        </div>

        <form @submit.prevent="handleRegister">
          <div class="field">
            <label>Name</label>
            <input v-model="name" type="text" placeholder="Your full name" required />
          </div>
          <div class="field">
            <label>Email</label>
            <input v-model="email" type="email" placeholder="your@utm.my" required />
          </div>
          <div class="field">
            <label>Password</label>
            <input v-model="password" type="password" placeholder="Create a password" required />
          </div>
          <div class="field">
            <label>Confirm Password</label>
            <input v-model="confirmPassword" type="password" placeholder="Repeat your password" required />
          </div>

          <p v-if="error" class="form-error">{{ error }}</p>
          <p v-if="success" class="form-success">{{ success }}</p>

          <button type="submit" class="btn-submit" :disabled="loading">
            {{ loading ? 'Creating account...' : 'Create Account' }}
          </button>
        </form>

        <p class="switch-link">
          Already have an account?
          <RouterLink to="/login">Sign in here</RouterLink>
        </p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const error = ref('')
const success = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''
  success.value = ''

  if (password.value !== confirmPassword.value) {
    error.value = 'Passwords do not match.'
    return
  }

  loading.value = true
  try {
    await auth.register(email.value, password.value)
    success.value = 'Account created! Redirecting to login...'
    setTimeout(() => router.push('/login'), 1500)
  } catch (err) {
    error.value = err.response?.data?.error || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-layout {
  display: flex;
  min-height: 100vh;
}

/* ── Left Panel ───────────────────────────────────── */
.auth-panel {
  flex: 1;
  background: linear-gradient(145deg, #c62828 0%, #d32f2f 50%, #b71c1c 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 2.5rem;
  color: white;
}

.panel-content {
  max-width: 360px;
}

.panel-logo {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.panel-content h1 {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 1rem;
  letter-spacing: -0.5px;
}

.panel-content p {
  font-size: 1rem;
  line-height: 1.7;
  opacity: 0.88;
  margin-bottom: 2rem;
}

.panel-features {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.panel-feature {
  font-size: 0.95rem;
  opacity: 0.92;
  background: rgba(255,255,255,0.12);
  padding: 0.6rem 1rem;
  border-radius: 8px;
}

/* ── Right Form ───────────────────────────────────── */
.auth-form-side {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f7f7f7;
  padding: 2rem 1.5rem;
}

.auth-card {
  background: white;
  border-radius: 16px;
  padding: 2.5rem 2.25rem;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}

.auth-card h2 {
  font-size: 1.6rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.3rem;
}

.auth-sub {
  color: #888;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

/* ── Tabs ─────────────────────────────────────────── */
.auth-tabs {
  display: flex;
  gap: 0;
  background: #f2f2f2;
  border-radius: 8px;
  padding: 4px;
  margin-bottom: 1.75rem;
}

.tab {
  flex: 1;
  padding: 0.55rem;
  font-size: 0.92rem;
  font-weight: 600;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
  background: transparent;
  color: #888;
  transition: background 0.2s, color 0.2s, box-shadow 0.2s;
}

.tab.active {
  background: white;
  color: var(--primary);
  box-shadow: 0 1px 6px rgba(0,0,0,0.1);
}

/* ── Fields ───────────────────────────────────────── */
.field {
  margin-bottom: 1rem;
}

.field label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #444;
  margin-bottom: 0.4rem;
}

.field input {
  width: 100%;
  padding: 0.72rem 0.9rem;
  border: 1.5px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: inherit;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  background: #fafafa;
}

.field input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(211,47,47,0.1);
  background: white;
}

.form-error {
  color: var(--primary);
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
  background: #fff5f5;
  border: 1px solid #ffd0d0;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
}

.form-success {
  color: #2e7d32;
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
  background: #f1f8f1;
  border: 1px solid #c8e6c9;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
}

/* ── Button ───────────────────────────────────────── */
.btn-submit {
  width: 100%;
  padding: 0.82rem;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s, transform 0.1s;
  margin-top: 0.5rem;
  letter-spacing: 0.3px;
}

.btn-submit:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.switch-link {
  text-align: center;
  margin-top: 1.25rem;
  font-size: 0.88rem;
  color: #888;
}

.switch-link a {
  color: var(--primary);
  font-weight: 600;
  text-decoration: none;
}

.switch-link a:hover {
  text-decoration: underline;
}

/* ── Responsive ───────────────────────────────────── */
@media (max-width: 700px) {
  .auth-layout { flex-direction: column; }
  .auth-panel { padding: 2rem 1.5rem; min-height: auto; }
  .panel-content h1 { font-size: 1.8rem; }
  .panel-features { display: none; }
}
</style>
