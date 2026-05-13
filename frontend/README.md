# LostLink Frontend

Vue.js 3 SPA built with Vite.

## Setup

```bash
npm install
npm run dev       # dev server at http://localhost:5173
npm run build     # production build → dist/
```

## Structure

```
src/
├── main.js           # App entry
├── App.vue           # Root component
├── router/           # Vue Router (all routes + auth guards)
├── store/            # Pinia stores (auth)
├── services/         # Axios API client with JWT interceptor
├── components/       # Shared components (NavBar, etc.)
├── views/            # Page-level components
└── assets/           # Global CSS
```

## Environment

Create a `.env.local` file to override the API base URL if needed:

```
VITE_API_BASE=http://localhost:8080
```
