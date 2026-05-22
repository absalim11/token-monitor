# PRD - ABW Token Monitor Dashboard

## Project Overview

**Objective**: Real-time monitoring dashboard for LLM token usage via LiteLLM API.

**Scope**: Single-page dashboard with 3-second auto-refresh, dockerized Laravel application, direct API integration (no database storage).

**Focus Areas**:
- Per-key usage monitoring
- Daily cost tracking
- Token expiry/masa aktif

**LiteLLM API Base URL**: `https://ai.abworks.web.id`

---

## Architecture Change

### Before (Deprecated)
- Store token statistics in database
- Periodic Laravel Jobs fetch usage

### After (Current)
- Direct Guzzle HTTP calls to LiteLLM API
- Response caching (3-5 seconds) to avoid API throttling
- No persistent storage for statistics
- Stateless monitoring

---

## Functional Requirements

### 1. Authentication
- **Single User**: Built-in Laravel Auth (Breeze)
- **API Credentials**: LiteLLM Master Key stored in `.env`
- **Session**: Default Laravel session management

### 2. Dashboard Components

#### A. Header
- Application title: "ABW Token Monitor"
- Logout button
- Last refresh timestamp
- API status indicator (Connected/Error)

#### B. Key Statistics Cards (Summary)
Display per virtual key:
- Token ID/Name (masked key with alias)
- Total spend (USD)
- Max budget
- Usage percentage (spend/max_budget)
- Status indicator (Normal/Warning/Critical/Expired/Blocked)
- **Expiry date** (masa aktif)
- Models allowed

#### C. Detailed Statistics Table
Columns:
- Token Name / Key (masked)
- Models Allowed
- Spend (USD)
- Budget (USD)
- Usage %
- User ID
- Status
- **Expires** (masa aktif)
- Actions (Refresh/View Details/Block/Unblock)

#### D. Daily Cost Tracking
- Total spend across all loaded virtual keys
- Total max budget across all loaded virtual keys
- Daily spend list and trend chart for `7 Days` and `30 Days`
- Daily average for the selected period

#### E. User Statistics (Optional)
- Total users count
- Per-user spend summary

### 3. API Integration (LiteLLM)

**Base URL**: `https://litellm-api.up.railway.app/`

**Authentication**: `Authorization: Bearer <master-key>`

---

## LiteLLM API Endpoints

### Key Management (Primary for Dashboard)

| Method | Endpoint | Purpose | Headers | Body |
|--------|----------|---------|---------|------|
| GET | `/key/list` | List all virtual keys | Bearer token | - |
| GET | `/key/info?key=<key>` | Get key details & spend | Bearer token | - |
| POST | `/key/delete` | Delete a key | Bearer token | `{"key": "..."}` |
| POST | `/key/block` | Block a key | Bearer token | `{"key": "..."}` |
| POST | `/key/unblock` | Unblock a key | Bearer token | `{"key": "..."}` |
| POST | `/key/update` | Update key (budget, rotation) | Bearer token | `{"key": "...", ...}` |
| POST | `/key/generate` | Generate new key | Bearer token | See below |

### Key Generation Body
```json
{
  "models": ["gpt-4", "claude-3"],
  "metadata": {"tags": ["project-x"]},
  "user_id": "user-123",
  "aliases": ["dev-key"],
  "duration": "30d",
  "max_budget": 100.0,
  "config": {},
  "auto_rotate": false
}
```

### User Management

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/user/info?user_id=<id>` | Get user spend & keys |
| GET | `/user/list` | List all users |
| GET | `/user/daily/activity?start=<date>&end=<date>` | Daily breakdown with spend/tokens |
| POST | `/user/new` | Create new user |

### Spend Tracking

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/global/spend/report?start=<date>&end=<date>` | Daily spend report (enterprise-only on some LiteLLM deployments) |
| GET | `/spend/logs?start=<date>&end=<date>&summarize=true` | Spend logs (aggregated) |
| POST | `/global/spend/reset` | Reset all spend (master only) |

### Models

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/models` | List available models |

---

## Non-Functional Requirements

### Performance
- **Auto-refresh**: Every 3 seconds via JavaScript/Alpine.js
- **API Caching**: Laravel Cache (3-5 seconds TTL) to prevent API abuse
- **Page load**: Under 1 second
- **Database**: SQLite (for auth only)

### Design System
- **Color Scheme**: Tosca → Grey gradient
  - Primary: Tosca (#20B2AA or #008080)
  - Secondary: Grey (#6B7280, #9CA3AF, #E5E7EB)
  - Background: Light grey (#F9FAFB)
  - Cards: White with subtle border
- **Style**: Modern, sleek, minimalist
- **Framework**: Laravel Blade + Alpine.js + Tailwind CSS

---

## Technical Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3+ / Laravel 13 |
| HTTP Client | Guzzle (built-in) |
| Frontend | Laravel Blade + Alpine.js CDN + Tailwind CSS CDN |
| Auth | Laravel Breeze |
| Cache | Laravel Cache (Redis or File) |
| Database | SQLite (auth only) |
| Orchestration | Docker Compose |

---

## Docker Structure

```
llm-monitor/
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   └── nginx/
│       └── default.conf
├── docker-compose.yml
├── .env.example
└── Laravel app structure
```

**Services**:
- `php` (FPM)
- `nginx` (web server)
- `sqlite` (auth only - volume)

---

## LiteLLM Service

### Service Class: `LiteLLMService`

**Responsibilities**:
- Make Guzzle HTTP calls to LiteLLM API
- Handle authentication via master key
- Parse and normalize API responses
- Apply fallbacks when LiteLLM enterprise-only endpoints are unavailable
- Cache responses with TTL

**Methods**:
```php
class LiteLLMService
{
    // Key Management
    public function listKeys(): array;
    public function getKeyInfo(string $key): array;
    public function deleteKey(string $key): bool;
    public function blockKey(string $key): bool;
    public function unblockKey(string $key): bool;
    public function updateKey(string $key, array $data): array;
    public function generateKey(array $data): string;

    // User Management
    public function getUserInfo(string $userId): array;
    public function listUsers(): array;
    public function getUserDailyActivity(string $startDate, string $endDate): array;

    // Spend Tracking
    public function getGlobalSpendReport(string $startDate, string $endDate): array;
    public function getDailySpendReport(string $startDate, string $endDate): array;
    public function getSpendLogs(string $startDate, string $endDate, bool $summarize): array;

    // Models
    public function listModels(): array;

    // Health Check
    public function getApiHealth(): bool;
}
```

**Cache Strategy**:
- Cache key: `litellm:{endpoint}:{params_hash}`
- TTL: 3-5 seconds
- Cache driver: Redis (recommended) or File
- Invalidations: Manual refresh, delete/update operations

---

## API Endpoints (Laravel Internal)

| Method | Route | Purpose |
|--------|-------|---------|
| GET | /dashboard | Main dashboard view |
| GET | /api/keys | List all keys (from LiteLLM) |
| GET | /api/keys/info?key=... | Get key details |
| POST | /api/keys/generate | Generate new key |
| POST | /api/keys/delete | Delete key |
| POST | /api/keys/block | Block key |
| POST | /api/keys/unblock | Unblock key |
| POST | /api/keys/update | Update key |
| GET | /api/users | List all users |
| GET | /api/users/{id}/info | Get user info |
| GET | /api/users/activity | Get daily activity |
| GET | /api/spend/daily | Get daily spend report |
| GET | /api/spend/logs | Get spend logs |
| GET | /api/health | Check LiteLLM API status |

---

## Implementation Phases

### Phase 1: Foundation (1 day)
- [ ] Docker environment setup
- [ ] Laravel 13 installation
- [ ] Laravel Breeze auth (single user)
- [ ] Configure Guzzle for API calls
- [ ] Environment configuration (.env)

### Phase 2: LiteLLM Service (1-2 days)
- [ ] Create `LiteLLMService` class
- [ ] Implement Guzzle client with master key auth
- [ ] Add caching layer with TTL
- [ ] Implement key management methods
- [ ] Implement user methods
- [ ] Implement spend tracking methods
- [ ] Error handling & retry logic

### Phase 3: Backend Controllers (1 day)
- [ ] DashboardController with stats endpoint
- [ ] KeyController for key operations
- [ ] Route configuration
- [ ] API validation

### Phase 4: Frontend (1-2 days)
- [ ] Dashboard Blade layout with Tailwind
- [ ] Alpine.js for auto-refresh
- [ ] Tosca-grey color scheme
- [ ] Key cards component
- [ ] Details table component
- [ ] Daily cost tracking section
- [ ] Loading states & error handling

### Phase 5: Testing (1 day)
- [ ] Docker compose validation
- [ ] 3-second refresh testing
- [ ] API failure simulation
- [ ] Cache verification
- [ ] Key operations testing (block/unblock/delete/generate)

---

## Configuration (.env)

```env
APP_NAME="ABW Token Monitor"
APP_ENV=local
APP_URL=http://localhost:8080

# LiteLLM API Configuration
LITELLM_API_URL=https://litellm-api.up.railway.app
LITELLM_API_KEY=your_master_key_here

# Cache Configuration
CACHE_DRIVER=file
CACHE_TTL=5

# Database (auth only)
DB_CONNECTION=sqlite
```

---

## Expected API Response Formats

### GET /key/list Response
```json
{
  "keys": [
    {
      "key": "sk-...",
      "user_id": "user-123",
      "spend": 12.50,
      "max_budget": 100.0,
      "expires": "2026-12-31T23:59:59Z",
      "models": ["gpt-4", "claude-3"],
      "aliases": ["dev-key"],
      "blocked": false
    }
  ]
}
```

### GET /key/info?key={key} Response
```json
{
  "key": "sk-...",
  "spend": 12.50,
  "max_budget": 100.0,
  "expires": "2026-12-31T23:59:59Z",
  "models": ["gpt-4", "claude-3"],
  "aliases": ["dev-key"],
  "user_id": "user-123",
  "blocked": false,
  "config": {}
}
```

### GET /user/info?user_id={id} Response
```json
{
  "user_id": "user-123",
  "spend": 45.00,
  "max_budget": 500.0,
  "keys": [
    {
      "key": "sk-...",
      "spend": 12.50,
      "models": ["gpt-4"]
    }
  ]
}
```

### GET /global/spend/report Response
```json
{
  "spend": [
    {
      "date": "2026-05-20",
      "spend": 25.50,
      "models": {
        "gpt-4": 15.00,
        "claude-3": 10.50
      }
    }
  ]
}
```

### GET /user/daily/activity Response
```json
{
  "daily_activity": [
    {
      "date": "2026-05-20",
      "spend": 15.00,
      "total_tokens": 50000,
      "api_calls": 120,
      "models": {
        "gpt-4": {
          "spend": 10.00,
          "tokens": 35000,
          "calls": 80
        }
      }
    }
  ]
}
```

---

## Success Criteria

- [ ] Docker container runs with `docker-compose up`
- [ ] Login page works with Laravel default auth
- [ ] Dashboard displays all virtual keys with spend/budget
- [ ] Shows token expiry/masa aktif for each key
- [ ] Daily cost tracking displayed (7-day and 30-day views)
- [ ] Auto-refreshes every 3 seconds without full page reload
- [ ] Tosca-grey color scheme implemented
- [ ] API responses cached (3-5s TTL)
- [ ] Key operations work (block/unblock/detail/generate)
- [ ] Handles API errors gracefully

---

## Notes & Constraints

- **No database for statistics**: All data from LiteLLM API only
- **Cache required**: Prevent API abuse with 3-5s TTL cache
- **Stateless**: Monitoring data not persisted
- **Single user level**: No role/permission system needed
- **No complex state management**: Use Alpine.js for minimal reactivity
- **Error handling**: Show degraded UI if API is unavailable
- **Master Key Required**: All operations require LiteLLM master key
- **Team features removed**: Focus on key and user monitoring only
- **Frontend simplicity preferred**: Avoid requiring a frontend build pipeline for the dashboard

---

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       ├── DashboardController.php
│       └── KeyController.php
├── Services/
│   └── LiteLLMService.php
├── Data/
│   ├── KeyData.php
│   ├── UserData.php
│   └── SpendData.php
resources/
├── views/
│   ├── auth/ (Laravel Breeze)
│   ├── dashboard/
│   │   └── index.blade.php
│   ├── components/
│   │   ├── key-card.blade.php
│   │   ├── stats-table.blade.php
│   │   └── daily-cost-tracker.blade.php
│   └── layouts/
│       └── app.blade.php
config/
└── litellm.php
docker/
├── php/
│   └── Dockerfile
└── nginx/
    └── default.conf
docker-compose.yml
.env.example
```

---

## Sources

- [LiteLLM Documentation](https://docs.litellm.ai/)
- [LiteLLM Proxy - Virtual Keys](https://docs.litellm.ai/docs/proxy/virtual_keys)
- [LiteLLM Proxy - Cost Tracking](https://docs.litellm.ai/docs/proxy/cost_tracking)
- [LiteLLM API Swagger](https://litellm-api.up.railway.app/)
