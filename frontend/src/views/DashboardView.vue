<template>
  <div class="dashboard">
    <!-- Pending claims banner -->
    <div v-if="pendingClaimsCount > 0" class="alert-banner">
      <span class="alert-icon">🔔</span>
      <div>
        <strong>You have {{ pendingClaimsCount }} claim(s) waiting for verification</strong>
        <p>People are claiming items you reported as Found. Review and verify their proof of ownership in the "My Reports" tab.</p>
      </div>
    </div>

    <div class="dashboard-header">
      <h1>My Dashboard</h1>
      <p class="subtitle">Welcome, {{ auth.user?.name || auth.user?.email }}</p>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="tab"
        :class="{ active: activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">Loading...</div>

    <!-- My Reports Tab -->
    <div v-else-if="activeTab === 'reports'" class="tab-content">
      <div class="tab-toolbar">
        <h2>My Reports</h2>
        <RouterLink to="/report" class="btn btn-primary">+ New Report</RouterLink>
      </div>

      <div v-if="reports.length === 0" class="empty-state">
        You haven't reported any items yet.
      </div>

      <div v-else class="reports-list">
        <div v-for="item in reports" :key="item.item_id" class="report-card">
          <div class="report-info">
            <div class="report-meta">
              <span class="badge" :class="item.report_type">{{ item.report_type }}</span>
              <span class="badge status" :class="item.status">{{ item.status }}</span>
              <button class="btn-delete" @click="deleteReport(item.item_id)" title="Delete this report">🗑 Delete</button>
            </div>
            <h3>{{ item.title }}</h3>
            <p class="meta-row">📍 {{ item.location }} &nbsp;·&nbsp; 🗂 {{ item.category }} &nbsp;·&nbsp; 📅 {{ formatDate(item.date) }}</p>
          </div>

          <!-- Incoming claims on this item (only for found items) -->
          <div v-if="item.report_type === 'found' && item.status === 'active'" class="claims-section">
            <button class="btn-link" @click="toggleClaims(item.item_id)">
              {{ expandedItem === item.item_id ? 'Hide' : 'View' }} incoming claims
            </button>

            <div v-if="expandedItem === item.item_id" class="incoming-claims">
              <div v-if="loadingClaims" class="loading-sm">Loading claims...</div>
              <div v-else-if="incomingClaims.length === 0" class="empty-sm">No claims yet.</div>
              <div v-else v-for="claim in incomingClaims" :key="claim.request_id" class="claim-row">
                <div class="claim-info">
                  <p class="claimant">✉️ {{ claim.claimant_name || claim.claimant_email }}</p>
                  <p class="claim-desc">{{ claim.description }}</p>
                  <div v-if="claim.proof_path" class="proof-img-wrap">
                    <p class="proof-label">Proof submitted:</p>
                    <img :src="`/api${claim.proof_path}`" class="proof-img" alt="Proof" />
                  </div>
                  <p class="claim-date">Submitted {{ formatDate(claim.created_at) }}</p>
                </div>
                <div class="claim-status-badge">
                  <span class="badge" :class="claim.status">{{ claim.status }}</span>
                </div>
                <div v-if="claim.status === 'pending'" class="claim-actions">
                  <button class="btn btn-primary btn-sm" @click="respondClaim(claim.request_id, 'approved')">Approve</button>
                  <button class="btn btn-danger btn-sm" @click="respondClaim(claim.request_id, 'rejected')">Reject</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- My Claims Tab -->
    <div v-else-if="activeTab === 'claims'" class="tab-content">
      <h2>My Claims</h2>

      <div v-if="claims.length === 0" class="empty-state">
        You haven't submitted any claims yet.
      </div>

      <table v-else class="claims-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Submitted</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="claim in claims" :key="claim.request_id">
            <td>{{ claim.item_title }}</td>
            <td>{{ formatDate(claim.created_at) }}</td>
            <td><span class="badge" :class="claim.status">{{ claim.status }}</span></td>
            <td>
              <button
                v-if="claim.status === 'approved'"
                class="btn btn-primary btn-sm"
                @click="markReceived(claim.request_id)"
              >
                Mark as Received
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Matches Tab -->
    <div v-else-if="activeTab === 'matches'" class="tab-content">
      <h2>Smart Matches</h2>
      <p class="tab-desc">Items that may correspond to your lost reports, ranked by similarity.</p>

      <div v-if="loadingMatches" class="loading">Finding matches...</div>

      <div v-else-if="myMatches.length === 0" class="empty-state">
        No matches found yet. Check back after more items are reported.
      </div>

      <div v-else class="match-list">
        <div v-for="(match, i) in myMatches" :key="i" class="match-card">
          <!-- Lost side -->
          <div class="match-side lost-side">
            <p class="match-label">YOUR LOST ITEM</p>
            <h3>{{ match.lost_item.title }}</h3>
            <p class="match-meta">{{ match.lost_item.category }}</p>
            <p class="match-meta">📍 {{ match.lost_item.location }}</p>
          </div>

          <!-- Center arrow -->
          <div class="match-center">
            <span class="arrow">→</span>
            <span class="match-word">Match</span>
          </div>

          <!-- Found side -->
          <div class="match-side found-side">
            <p class="match-label">MATCHED FOUND ITEM</p>
            <h3>{{ match.found_item.title }}</h3>
            <p class="match-meta">{{ match.found_item.category }}</p>
            <p class="match-meta">📍 {{ match.found_item.location }}</p>
          </div>

          <!-- Action -->
          <RouterLink
            :to="`/items/${match.found_item.item_id}?lost_item_id=${match.lost_item.item_id}`"
            class="btn btn-outline view-claim-btn"
          >
            View &amp; Claim
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Success / error toast -->
    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/store/authStore'
import api from '@/services/api'
import { useRouter, useRoute } from 'vue-router'
const router = useRouter()
const route = useRoute()

const auth = useAuthStore()
const tabs = [
  { key: 'reports', label: 'My Reports' },
  { key: 'claims',  label: 'My Claims'  },
  { key: 'matches', label: 'Matches'    },
]
const validTabKeys = tabs.map(t => t.key)
const activeTab = ref(validTabKeys.includes(route.query.tab) ? route.query.tab : 'reports')
const loading = ref(true)
const loadingMatches = ref(false)
const loadingClaims = ref(false)

const reports = ref([])
const claims = ref([])
const allMatches = ref([])
const incomingClaims = ref([])
const expandedItem = ref(null)
const toast = ref({ message: '', type: '' })
watch(activeTab, async (newTab) => {
  if (newTab === 'reports') await fetchDashboard()
})

// Count pending claims across all found items user has posted
const pendingClaimsCount = computed(() => {
  // We surface this after loading matches/claims — use dashboard data
  return 0 // updated dynamically after fetching per-item claims
})

// Filter matches to only show pairs where the lost item belongs to this user
const myMatches = computed(() => {
  const userId = auth.user?.id
  return allMatches.value.filter(m => Number(m.lost_item.posted_by) === Number(userId))
})

onMounted(async () => {
  await fetchDashboard()
  await fetchMatches()
})

async function fetchDashboard() {
  loading.value = true
  try {
    if (!auth.user?.id) {
      showToast('Session expired, please login again', 'error')
      await router.push('/login')
      return
    }
    const res = await api.get(`/dashboard/${auth.user.id}`)
    reports.value = res.data.reports
    claims.value  = res.data.claims
  } catch (e) {
    showToast('Failed to load dashboard', 'error')
  } finally {
    loading.value = false
  }
}

async function fetchMatches() {
  loadingMatches.value = true
  try {
    const res = await api.get('/matches')
    allMatches.value = res.data.matches || []  
  } catch (e) {
    showToast('Failed to load matches', 'error')
    allMatches.value = []
  } finally {
    loadingMatches.value = false
  }
}

async function toggleClaims(itemId) {
  if (expandedItem.value === itemId) {
    expandedItem.value = null
    return
  }
  expandedItem.value = itemId
  loadingClaims.value = true
  try {
    const res = await api.get(`/claims/item/${itemId}`)
    incomingClaims.value = res.data
  } catch (e) {
    showToast('Failed to load claims', 'error')
  } finally {
    loadingClaims.value = false
  }
}

async function respondClaim(claimId, status) {
  try {
    await api.put(`/claims/${claimId}`, { status })
    showToast(`Claim ${status}`, status === 'approved' ? 'success' : 'error')
    // Refresh incoming claims and reports
    if (expandedItem.value) {
      const res = await api.get(`/claims/item/${expandedItem.value}`)
      incomingClaims.value = res.data
    }
    await fetchDashboard()
  } catch (e) {
    showToast('Action failed', 'error')
  }
}

async function markReceived(claimId) {
  try {
    await api.post(`/claims/${claimId}/received`)
    showToast('Item marked as received', 'success')
    await fetchDashboard()
  } catch (e) {
    showToast('Failed to update', 'error')
  }
}

async function deleteReport(itemId) {
  if (!confirm('Delete this report? This cannot be undone.')) return
  try {
    await api.delete(`/items/${itemId}`)
    reports.value = reports.value.filter(r => r.item_id !== itemId)
    showToast('Report deleted', 'success')
  } catch (e) {
    showToast(e.response?.data?.error || 'Failed to delete report', 'error')
  }
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
}

function showToast(message, type = 'success') {
  toast.value = { message, type }
  setTimeout(() => { toast.value = { message: '', type: '' } }, 3000)
}

</script>

<style scoped>
.dashboard {
  max-width: 960px;
  margin: 0 auto;
  padding: 2rem 1.5rem 4rem;
}

/* Alert banner */
.alert-banner {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  background: #fffbeb;
  border: 1px solid #f59e0b;
  border-radius: 10px;
  padding: 1rem 1.25rem;
  margin-bottom: 2rem;
}
.alert-icon { font-size: 1.4rem; }
.alert-banner strong { display: block; color: #92400e; margin-bottom: 0.2rem; }
.alert-banner p { margin: 0; color: #78350f; font-size: 0.88rem; }

/* Header */
.dashboard-header { margin-bottom: 1.75rem; }
.dashboard-header h1 { font-size: 1.9rem; font-weight: 800; color: #1a1a1a; margin-bottom: 0.2rem; }
.subtitle { color: #666; font-size: 0.95rem; }

/* Tabs */
.tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
.tab {
  padding: 0.55rem 1.25rem;
  border-radius: 999px;
  border: 1.5px solid #e5e5e5;
  background: white;
  color: #555;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.tab:hover:not(.active) { border-color: var(--primary); color: var(--primary); }

.tab-content { animation: fadeIn 0.2s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

.tab-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.tab-toolbar h2, .tab-content > h2 { font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.25rem; }
.tab-desc { color: #666; font-size: 0.9rem; margin-bottom: 1.5rem; }

/* Badges */
.badge {
  display: inline-block;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: capitalize;
}
.badge.lost    { background: #fff0f0; color: var(--primary); }
.badge.found   { background: #eff6ff; color: #1d4ed8; }
.badge.active  { background: #f0fdf4; color: #15803d; }
.badge.claimed { background: #f5f3ff; color: #7c3aed; }
.badge.pending  { background: #fffbeb; color: #92400e; }
.badge.approved { background: #f0fdf4; color: #15803d; }
.badge.rejected { background: #fff0f0; color: var(--primary); }

/* Report cards */
.reports-list { display: flex; flex-direction: column; gap: 1rem; }
.report-card {
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.5rem;
  background: white;
  box-shadow: var(--shadow-sm);
  transition: box-shadow 0.15s, transform 0.15s;
}
.report-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}
.report-meta { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.btn-delete {
  margin-left: auto;
  background: none;
  border: none;
  color: #999;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
.btn-delete:hover { color: var(--primary); }
.report-card h3 { font-size: 1.05rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.35rem; }
.meta-row { font-size: 0.85rem; color: #666; }

/* Incoming claims */
.claims-section { margin-top: 1rem; border-top: 1px solid #f0f0f0; padding-top: 0.75rem; }
.btn-link { background: none; border: none; color: var(--primary); font-size: 0.88rem; font-weight: 600; cursor: pointer; padding: 0; }
.btn-link:hover { text-decoration: underline; }
.incoming-claims { margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.75rem; }
.claim-row {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  background: #fafafa;
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 0.9rem 1rem;
  flex-wrap: wrap;
}
.claim-info { flex: 1; }
.claimant { font-size: 0.85rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.2rem; }
.claim-desc { font-size: 0.85rem; color: #444; margin-bottom: 0.2rem; }
.claim-date { font-size: 0.78rem; color: #999; }
.claim-actions { display: flex; gap: 0.5rem; align-items: center; }
.loading-sm, .empty-sm { font-size: 0.88rem; color: #999; padding: 0.5rem 0; }

/* Claims table */
.claims-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.claims-table th { text-align: left; font-size: 0.8rem; color: #999; font-weight: 600; text-transform: uppercase; padding: 0.5rem 0.75rem; border-bottom: 1px solid #eee; }
.claims-table td { padding: 0.85rem 0.75rem; border-bottom: 1px solid #f0f0f0; font-size: 0.92rem; color: #1a1a1a; }

/* Proof image */
.proof-img-wrap { margin-top: 0.5rem; }
.proof-label { font-size: 0.78rem; color: #999; margin-bottom: 0.25rem; }
.proof-img { max-height: 120px; border-radius: 6px; border: 1px solid #eee; }

/* Match cards */
.match-list { display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; }
.match-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.5rem;
  background: white;
  box-shadow: var(--shadow-sm);
  transition: box-shadow 0.15s, transform 0.15s;
  flex-wrap: wrap;
}
.match-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}
.match-side { flex: 1; min-width: 160px; }
.match-label { font-size: 0.7rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.3rem; }
.match-side h3 { font-size: 1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.2rem; }
.match-meta { font-size: 0.85rem; color: #666; margin: 0; }
.match-center { display: flex; flex-direction: column; align-items: center; gap: 0.2rem; padding: 0 0.5rem; }
.arrow { font-size: 1.25rem; color: var(--primary); }
.match-word { font-size: 0.75rem; font-weight: 700; color: var(--primary); }
.view-claim-btn { white-space: nowrap; flex-shrink: 0; }

/* Buttons */
.btn {
  display: inline-block;
  padding: 0.55rem 1.25rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.15s;
}
.btn-primary { background: var(--primary); color: white; border-color: var(--primary); }
.btn-primary:hover { background: #a80001; border-color: #a80001; }
.btn-outline { background: white; color: var(--primary); border-color: var(--primary); }
.btn-outline:hover { background: #fff0f0; }
.btn-danger { background: white; color: var(--primary); border: 2px solid var(--primary); }
.btn-danger:hover { background: #fff0f0; }
.btn-sm { padding: 0.35rem 0.85rem; font-size: 0.82rem; }

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
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.toast.success { background: #15803d; color: white; }
.toast.error   { background: var(--primary); color: white; }

/* Empty state */
.empty-state { text-align: center; color: #999; padding: 3rem 0; font-size: 0.95rem; }
.loading { text-align: center; color: #999; padding: 2rem 0; }

@media (max-width: 640px) {
  .match-card { flex-direction: column; }
  .match-center { flex-direction: row; gap: 0.5rem; }
  .view-claim-btn { width: 100%; text-align: center; }
}
</style>