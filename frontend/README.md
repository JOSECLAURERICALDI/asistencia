# Asistencia Frontend

A Quasar v2 (Vue 3) SPA application for the Asistencia system.

## Prerequisites

- Node.js >= 12.22.1
- npm >= 6.13.4

## Install Dependencies

```bash
npm install
```

## Start Development Server

```bash
npm run dev
# or
quasar dev
```

Opens at http://localhost:8080 with hot-reload. API calls to `/api` are proxied to `http://localhost:8000` (Laravel backend).

## Build for Production

```bash
npm run build
# or
quasar build
```

## Project Structure

```
frontend/
├── src/
│   ├── assets/         # Static assets (images, etc.)
│   ├── boot/           # Boot files (axios, etc.)
│   │   └── axios.js    # Axios API instance
│   ├── components/     # Reusable Vue components
│   ├── css/            # Global styles (app.scss)
│   ├── layouts/        # Page layouts
│   │   └── MainLayout.vue
│   ├── pages/          # Page components
│   │   ├── IndexPage.vue
│   │   └── ErrorNotFound.vue
│   ├── router/         # Vue Router
│   │   ├── index.js
│   │   └── routes.js
│   └── stores/         # Pinia stores
│       └── example-store.js
├── quasar.config.js    # Quasar configuration
└── package.json
```
