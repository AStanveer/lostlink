<template>
  <div class="home">

    <!-- Hero Section -->
    <section class="hero">
      <h1 class="hero-title">Lost Something on Campus?</h1>
      <p class="hero-subtitle">
        LostLink helps UTM students and staff report and recover lost items — fast and hassle-free.
      </p>
      <div class="hero-actions">
        <RouterLink to="/report" class="btn btn-primary">Report Lost Item</RouterLink>
        <RouterLink to="/report" class="btn btn-outline">Report Found Item</RouterLink>
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

  </div>
</template>

<script setup>
import { useAuthStore } from '@/store/authStore'

const auth = useAuthStore()
</script>

<style scoped>
/* ── Hero ─────────────────────────────────────────── */
.hero {
  background: #ffffff;
  text-align: center;
  padding: 5rem 2rem 4rem;
  border-bottom: 1px solid #eee;
}

.hero-title {
  font-size: 2.8rem;
  font-weight: 800;
  color: #1a1a1a;
  line-height: 1.2;
  margin-bottom: 1.25rem;
  max-width: 640px;
  margin-left: auto;
  margin-right: auto;
}

.hero-subtitle {
  font-size: 1.1rem;
  color: #555;
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
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 600;
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
  box-shadow: 0 4px 14px rgba(211,47,47,0.3);
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
}

.btn-outline-white {
  background: transparent;
  color: white;
  border: 2px solid white;
}
.btn-outline-white:hover {
  background: rgba(255,255,255,0.12);
}

/* ── How It Works ─────────────────────────────────── */
.how-it-works {
  background: #f8f8f8;
  text-align: center;
  padding: 4rem 2rem;
}

.section-title {
  font-size: 1.85rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.section-subtitle {
  color: #666;
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
  border-radius: 12px;
  padding: 2rem 1.5rem;
  width: 220px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  text-align: center;
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
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.step p {
  font-size: 0.88rem;
  color: #666;
  line-height: 1.6;
}

.step-arrow {
  font-size: 1.5rem;
  color: #ccc;
  padding: 2rem 0.75rem 0;
  flex-shrink: 0;
  align-self: center;
}

/* ── Features ─────────────────────────────────────── */
.features {
  background: #ffffff;
  text-align: center;
  padding: 4rem 2rem;
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
  background: #fafafa;
  border: 1px solid #eee;
  border-radius: 12px;
  padding: 2rem 1.5rem;
  text-align: left;
  transition: transform 0.2s, box-shadow 0.2s;
}

.feature-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(211,47,47,0.1);
  border-color: var(--primary-light);
}

.feature-icon {
  font-size: 1.75rem;
  margin-bottom: 0.75rem;
}

.feature-card h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.4rem;
}

.feature-card p {
  font-size: 0.88rem;
  color: #666;
  line-height: 1.6;
}

/* ── CTA Banner ───────────────────────────────────── */
.cta-banner {
  background: var(--primary);
  color: white;
  text-align: center;
  padding: 4rem 2rem;
}

.cta-banner h2 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.6rem;
}

.cta-banner p {
  font-size: 1rem;
  opacity: 0.85;
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
  .hero-title { font-size: 2rem; }

  .step-arrow { display: none; }

  .steps {
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
  }

  .step { width: 100%; max-width: 320px; }
}
</style>
