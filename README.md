# LostLink — Web-based Lost and Found System

A centralized campus lost and found platform built with Vue.js, PHP Slim, and MySQL.

**WEB TECHNOLOGY (SECJ3483) | SEM 2 2025/2026 | Section 01**

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

## CI/CD

This repository uses GitHub Actions with `main` as the deployment branch.

- **CI workflow**: `.github/workflows/ci.yml`
  - Runs on `pull_request` and `push` to `main`
  - `Frontend build` job: installs dependencies in `frontend/` and runs `npm run build`
  - `Backend validation` job: installs dependencies in `backend/`, runs `composer validate --no-check-publish`, and runs PHP syntax checks
  - Set both job checks (`Frontend build`, `Backend validation`) as **required status checks** in branch protection for `main`

- **CD workflow**: `.github/workflows/cd.yml`
  - Triggers when CI succeeds on `main` (`workflow_run`) and can also be run manually (`workflow_dispatch`)
  - Default automatic deploy target is **staging**
  - Manual deploy supports:
    - target environment (`staging` or `production`)
    - `deploy_ref` (branch/tag/commit SHA), which also enables rollback by redeploying a previous commit
  - Deploy jobs depend on verification jobs before deployment
  - Frontend deploy target: **Netlify**
  - Backend deploy target: **VM via SSH**

### Required GitHub Secrets

#### Frontend / Netlify
- `NETLIFY_AUTH_TOKEN`
- `NETLIFY_SITE_ID`
- `VITE_API_BASE`

#### Backend / VM deployment
- `BACKEND_SSH_HOST`
- `BACKEND_SSH_USER`
- `BACKEND_SSH_PRIVATE_KEY`
- `BACKEND_SSH_PORT` (optional, defaults to `22`)
- `BACKEND_APP_PATH` (absolute path to backend repo/app on the server)
- `BACKEND_RESTART_COMMAND` (optional command to restart backend service after deploy)

#### Backend runtime (.env on server)
Configure these on the deployment host (or secret manager used by your host):
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `JWT_SECRET`

### Environment Protection (recommended)

Create GitHub environments named `staging` and `production`, then:
- Add required reviewers for `production` to enforce manual approval before production deploys
- Scope production secrets to the `production` environment
- Scope staging secrets to the `staging` environment
