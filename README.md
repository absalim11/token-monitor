# ABW Token Monitor

ABW Token Monitor is a Laravel dashboard for monitoring AbworksLLM virtual key usage, spend, budget, expiry, and health status.

## Stack

- Laravel 13
- PHP 8.3
- Blade
- Alpine.js via CDN
- Tailwind CSS via CDN
- Docker Compose (`php` + `nginx`)
- SQLite for auth/session-related app data

## Current Frontend Approach

The project intentionally uses a simple frontend runtime:

- no Vite build step for the dashboard
- no Node container requirement
- dashboard behavior implemented in `public/js/dashboard.js`

This keeps the Docker workflow simple while still allowing modular Alpine logic.

## Main Features

- Real-time dashboard with 11-second auto-refresh
- Virtual key cards and detailed statistics table
- Key detail modal
- Daily Cost Tracker with `7 Days` and `30 Days` filters
- Overall spend and total max budget summary
- Block / unblock key actions
- Dark / light mode with persisted browser preference
- AbworksLLM API health status

## AbworksLLM Compatibility Notes

The dashboard currently adapts to real AbworksLLM response behavior:

- sends `start_date` / `end_date` to AbworksLLM
- falls back from `/global/spend/report` to `/spend/logs` when enterprise-only endpoints are unavailable
- normalizes inconsistent `key/list` and `key/info` payload shapes
- hydrates incomplete key list entries through `key/info` when needed

## Running The App

```bash
docker-compose up -d
```

Open:

```text
http://localhost:8081
```

## Configuration

Important `.env` variables:

```env
APP_NAME="ABW Token Monitor"
APP_URL=http://localhost:8081

LITELLM_API_URL=https://ai.abworks.web.id
LITELLM_API_KEY=your-master-key

DB_CONNECTION=sqlite
```

## Documentation

- [PRD.md](./PRD.md)
- [GUIDE.md](./GUIDE.md)
- [MILESTONE.md](./MILESTONE.md)
