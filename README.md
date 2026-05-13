# LostLink — Web-based Lost and Found System

A centralized campus lost and found platform built with Vue.js, PHP Slim, and MySQL.

**WEB TECHNOLOGY (SECJ3483) | SEM 2 2025/2026 | Section 01**

| Name | Matric | Role |
|------|--------|------|
| Anjum Siddiqua Tanveer Siddiqui | A23CS0289 | Frontend Developer |
| Chuah Hui Wen | A23CS0219 | Backend Developer |
| Thang Wei Jie | A23CS0280 | Database Developer |

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | Vue.js 3, HTML, CSS, JavaScript |
| Backend | PHP Slim Framework |
| Database | MySQL (PDO) |
| Auth | JWT |
| Communication | AJAX / REST API |

---

## Features

- **Authentication** — Register, login, JWT-protected routes
- **Item Management** — Report lost/found items with image upload (CRUD)
- **Search & Filter** — Keyword, category, location filters
- **Smart Matching** — Suggests lost/found item pairs by keyword + location
- **Claim Verification** — Submit, review, approve/reject claims with proof
- **Status Tracking** — Lost → Found → Claimed lifecycle

---

## Project Structure

```
lostlink/
├── frontend/       # Vue.js SPA
├── backend/        # PHP Slim REST API
└── database/       # SQL schema and seed files
```

## Setup

See [`frontend/README.md`](frontend/README.md) and [`backend/README.md`](backend/README.md) for setup instructions.

---

