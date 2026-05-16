<template>
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <span class="brand-name">LostLink</span>
        <p>Connecting UTM students with their lost belongings</p>
      </div>
      <div class="footer-links">
        <h4>Quick Links</h4>
        <RouterLink to="/items">Browse Items</RouterLink>
        <RouterLink to="/report">Report Lost Item</RouterLink>
      </div>
      <div class="footer-contact">
        <h4>Contact</h4>
        <p>Universiti Teknologi Malaysia</p>
        <p>81310 Johor Bahru</p>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  background: #fff;
  border-top: 1px solid #e8e8e8;
  padding: 2.5rem 2rem;
  margin-top: auto;
}

.footer-inner {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 2rem;
}

.brand-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary);
  display: block;
  margin-bottom: 0.5rem;
}

.footer-brand p {
  font-size: 0.88rem;
  color: #666;
  line-height: 1.5;
}

.footer-links h4,
.footer-contact h4 {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.75rem;
}

.footer-links a {
  display: block;
  font-size: 0.88rem;
  color: #555;
  text-decoration: none;
  margin-bottom: 0.4rem;
  transition: color 0.15s;
}

.footer-links a:hover {
  color: var(--primary);
}

.footer-contact p {
  font-size: 0.88rem;
  color: #555;
  margin-bottom: 0.25rem;
}

@media (max-width: 600px) {
  .footer-inner {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
}
</style>
