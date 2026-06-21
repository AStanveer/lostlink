<template>
  <div class="home">

    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-blob hero-blob-1"></div>
      <div class="hero-blob hero-blob-2"></div>
      <div class="hero-content">
        <span class="hero-badge">🎓 Built for UTM students &amp; staff</span>
        <h1 class="hero-title">Lost Something on Campus?</h1>
        <p class="hero-subtitle">
          LostLink helps UTM students and staff report and recover lost items — fast and hassle-free.
        </p>
        <div class="hero-actions">
          <RouterLink to="/report" class="btn btn-primary">Report Lost Item</RouterLink>
          <RouterLink to="/items" class="btn btn-outline">Browse Found Items</RouterLink>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
      <h2 class="section-title">How It Works</h2>
      <p class="section-subtitle">Three simple steps to reunite with your lost belongings</p>
      <div class="steps">
        <div class="step">
          <div class="step-icon">📝</div>
          <div class="step-number">Step 1</div>
          <h3>Report</h3>
          <p>Submit a detailed report for the item you lost or found including category, location, and date.</p>
        </div>
        <div class="step-arrow">→</div>
        <div class="step">
          <div class="step-icon">🔗</div>
          <div class="step-number">Step 2</div>
          <h3>Match</h3>
          <p>LostLink automatically compares reports and surfaces the best matches for you.</p>
        </div>
        <div class="step-arrow">→</div>
        <div class="step">
          <div class="step-icon">🎉</div>
          <div class="step-number">Step 3</div>
          <h3>Recover</h3>
          <p>Submit a claim, verify ownership, and get your item back through one platform.</p>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="features">
      <h2 class="section-title">Why Use LostLink?</h2>
      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">🔎</div>
          <h3>Search &amp; Filter</h3>
          <p>Quickly find relevant listings by keyword, category, or location.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🤖</div>
          <h3>Smart Matching</h3>
          <p>Our engine compares keywords, categories, and locations to surface item pairs automatically.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🔒</div>
          <h3>Secure Claims</h3>
          <p>Claimants provide proof before items are handed over, keeping the process honest and safe.</p>
        </div>
      </div>
    </section>

    <!-- CTA Banner -->
    <section class="cta-banner">
      <h2 v-if="auth.isLoggedIn">Got something to report?</h2>
      <h2 v-else>Ready to get your stuff back?</h2>
      <p v-if="auth.isLoggedIn">Head to your dashboard to track reports, claims, and matches.</p>
      <p v-else>Create a free account and start reporting in under a minute.</p>
      <div class="cta-actions">
        <template v-if="auth.isLoggedIn">
          <RouterLink to="/dashboard" class="btn btn-white">Go to Dashboard</RouterLink>
          <RouterLink to="/report" class="btn btn-outline-white">Report an Item</RouterLink>
        </template>
        <template v-else>
          <RouterLink to="/register" class="btn btn-white">Create Account</RouterLink>
          <RouterLink to="/items" class="btn btn-outline-white">Browse Items</RouterLink>
        </template>
      </div>
    </section>

  </div>
</template>

<script setup>
import { useAuthStore } from '@/store/authStore'

const auth = useAuthStore()
</script>

<style scoped>
/* ── Hero ─────────────────────────────────────────── */
.hero {
  position: relative;
  background: linear-gradient(180deg, #fff9f9 0%, #ffffff 65%);
  text-align: center;
  padding: 6rem 2rem 4.5rem;
  border-bottom: 1px solid var(--border);
  overflow: hidden;
}

.hero-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.55;
  z-index: 0;
}

.hero-blob-1 {
  width: 360px;
  height: 360px;
  background: var(--primary-light);
  top: -140px;
  left: -100px;
}

.hero-blob-2 {
  width: 280px;
  height: 280px;
  background: var(--accent-blue-light);
  top: -60px;
  right: -80px;
}

.hero-content {
  position: relative;
  z-index: 1;
}

.hero-badge {
  display: inline-block;
  background: var(--primary-light);
  color: var(--primary-dark);
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  margin-bottom: 1.5rem;
}

.hero-title {
  font-size: 2.9rem;
  font-weight: 800;
  color: var(--ink);
  line-height: 1.15;
  margin-bottom: 1.25rem;
  max-width: 640px;
  margin-left: auto;
  margin-right: auto;
}

.hero-subtitle {
  font-size: 1.1rem;
  color: var(--ink-soft);
  line-height: 1.7;
  max-width: 520px;
  margin: 0 auto 2.25rem;
}

.hero-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

/* ── Buttons ──────────────────────────────────────── */
.btn {
  display: inline-block;
  padding: 0.75rem 1.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.95rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
  cursor: pointer;
}

.btn-primary {
  background: var(--primary);
  color: white;
  border: 2px solid var(--primary);
}
.btn-primary:hover {
  background: var(--primary-dark);
  border-color: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-outline {
  background: transparent;
  color: var(--primary);
  border: 2px solid var(--primary);
}
.btn-outline:hover {
  background: var(--primary-light);
  transform: translateY(-1px);
}

.btn-white {
  background: white;
  color: var(--primary);
  border: 2px solid white;
}
.btn-white:hover {
  background: #f5f5f5;
  transform: translateY(-1px);
}

.btn-outline-white {
  background: transparent;
  color: white;
  border: 2px solid white;
}
.btn-outline-white:hover {
  background: rgba(255,255,255,0.15);
  transform: translateY(-1px);
}

/* ── How It Works ─────────────────────────────────── */
.how-it-works {
  background: var(--bg);
  text-align: center;
  padding: 4.5rem 2rem;
}

.section-title {
  font-size: 1.9rem;
  font-weight: 800;
  color: var(--ink);
  margin-bottom: 0.5rem;
}

.section-subtitle {
  color: var(--muted);
  font-size: 1rem;
  margin-bottom: 3rem;
}

.steps {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 0;
  flex-wrap: wrap;
}

.step {
  background: white;
  border-radius: var(--radius-lg);
  padding: 2.25rem 1.75rem;
  width: 230px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  text-align: center;
  transition: transform 0.2s, box-shadow 0.2s;
}

.step:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.step-icon {
  font-size: 2.25rem;
  margin-bottom: 0.5rem;
}

.step-number {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.step h3 {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.step p {
  font-size: 0.88rem;
  color: var(--muted);
  line-height: 1.6;
}

.step-arrow {
  font-size: 1.5rem;
  color: var(--border);
  padding: 2.5rem 0.75rem 0;
  flex-shrink: 0;
  align-self: center;
}

/* ── Features ─────────────────────────────────────── */
.features {
  background: #ffffff;
  text-align: center;
  padding: 4.5rem 2rem;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
  max-width: 900px;
  margin-left: auto;
  margin-right: auto;
}

.feature-card {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2rem 1.5rem;
  text-align: left;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.feature-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
  border-color: var(--primary-light);
}

.feature-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  font-size: 1.5rem;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  margin-bottom: 1rem;
}

.feature-card h3 {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
}

.feature-card p {
  font-size: 0.9rem;
  color: var(--muted);
  line-height: 1.6;
}

/* ── CTA Banner ───────────────────────────────────── */
.cta-banner {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: white;
  text-align: center;
  padding: 4.5rem 2rem;
}

.cta-banner h2 {
  font-size: 1.85rem;
  font-weight: 800;
  color: white;
  margin-bottom: 0.6rem;
}

.cta-banner p {
  font-size: 1rem;
  opacity: 0.9;
  margin-bottom: 2rem;
}

.cta-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

/* ── Responsive ───────────────────────────────────── */
@media (max-width: 680px) {
  .hero { padding: 4.5rem 1.5rem 3.5rem; }
  .hero-title { font-size: 2.1rem; }

  .step-arrow { display: none; }

  .steps {
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
  }

  .step { width: 100%; max-width: 320px; }
}
</style>
