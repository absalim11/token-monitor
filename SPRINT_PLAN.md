# Sprint Plan - ABW Token Monitor Dashboard

## Sprint Overview

**Duration**: 5 Days (1 week)
**Goal**: Complete functional LLM token monitoring dashboard
**Team Size**: 1 Developer
**Start Date**: TBD

---

## Sprint Backlog

### Story 1: Docker Environment Setup (Day 1)

**ID**: SPRINT-1-001
**Priority**: Critical
**Story Points**: 3

#### Tasks:
- [ ] Create `docker/` directory structure
- [ ] Create `docker/php/Dockerfile` (PHP 8.3-FPM base)
- [ ] Create `docker/nginx/default.conf` (Nginx config for Laravel)
- [ ] Create `docker-compose.yml` with services: php, nginx, sqlite
- [ ] Create `.env.example` with all required variables
- [ ] Test `docker-compose up` - ensure containers start
- [ ] Verify Nginx serves PHP correctly

**Acceptance Criteria**:
- `docker-compose up -d` starts all services
- `localhost:8080` returns Nginx welcome or Laravel install page
- SQLite volume persists data

---

### Story 2: Laravel Foundation & Auth (Day 1)

**ID**: SPRINT-1-002
**Priority**: Critical
**Story Points**: 3

#### Tasks:
- [ ] Install Laravel 13 in docker container
- [ ] Configure `.env` for docker environment
- [ ] Generate application key
- [ ] Install Laravel Breeze with Blade stack
- [ ] Run migrations
- [ ] Create single admin user
- [ ] Test login/logout flow
- [ ] Protect dashboard routes with auth middleware

**Acceptance Criteria**:
- Can access login page at `/login`
- Can login with admin credentials
- Dashboard route redirects to `/login` when not authenticated
- Logout works correctly

---

### Story 3: Configuration & Guzzle Setup (Day 1)

**ID**: SPRINT-1-003
**Priority**: Critical
**Story Points**: 2

#### Tasks:
- [ ] Create `config/litellm.php` config file
- [ ] Add AbworksLLM configuration to `.env.example`
- [ ] Configure Guzzle HTTP client base setup
- [ ] Create service provider if needed
- [ ] Test Guzzle connection to AbworksLLM API (health check)
- [ ] Configure cache settings (Redis or File)

**Acceptance Criteria**:
- Config file loaded correctly
- Guzzle can make requests to AbworksLLM API
- Cache driver configured and working

---

### Story 4: AbworksLLM Service - Key Management (Day 2)

**ID**: SPRINT-1-004
**Priority**: High
**Story Points**: 5

#### Tasks:
- [ ] Create `app/Services/LiteLLMService.php`
- [ ] Implement Guzzle client with Bearer auth
- [ ] Implement `listKeys()` - GET `/key/list`
- [ ] Implement `getKeyInfo($key)` - GET `/key/info?key={key}`
- [ ] Implement `generateKey($data)` - POST `/key/generate`
- [ ] Implement `deleteKey($key)` - POST `/key/delete`
- [ ] Implement `blockKey($key)` - POST `/key/block`
- [ ] Implement `unblockKey($key)` - POST `/key/unblock`
- [ ] Implement `updateKey($key, $data)` - POST `/key/update`
- [ ] Add caching layer (3-5s TTL)
- [ ] Add error handling & retry logic

**Acceptance Criteria**:
- All key operations return expected data
- Responses cached with TTL
- Errors handled gracefully

---

### Story 5: AbworksLLM Service - User & Spend (Day 2)

**ID**: SPRINT-1-005
**Priority**: High
**Story Points**: 4

#### Tasks:
- [ ] Implement `listUsers()` - GET `/user/list`
- [ ] Implement `getUserInfo($userId)` - GET `/user/info?user_id={id}`
- [ ] Implement `getUserDailyActivity($start, $end)` - GET `/user/daily/activity`
- [ ] Implement `getGlobalSpendReport($start, $end)` - GET `/global/spend/report`
- [ ] Implement `getSpendLogs($start, $end, $summarize)` - GET `/spend/logs`
- [ ] Implement `listModels()` - GET `/models`
- [ ] Implement `getApiHealth()` - health check endpoint
- [ ] Add caching for user/spend endpoints

**Acceptance Criteria**:
- All user and spend operations return expected data
- Date range filtering works correctly
- Health check returns true/false

---

### Story 6: Dashboard Controller (Day 3)

**ID**: SPRINT-1-006
**Priority**: High
**Story Points**: 3

#### Tasks:
- [ ] Create `app/Http/Controllers/DashboardController.php`
- [ ] Implement `index()` - render dashboard view
- [ ] Implement `keys()` - JSON endpoint for key list
- [ ] Implement `dailySpend()` - JSON endpoint for daily spend
- [ ] Implement `userActivity()` - JSON endpoint for user activity
- [ ] Implement `health()` - JSON endpoint for API status
- [ ] Add validation for date parameters

**Acceptance Criteria**:
- All endpoints return proper JSON responses
- Date parameters validated
- Errors return appropriate HTTP codes

---

### Story 7: Key Controller (Day 3)

**ID**: SPRINT-1-007
**Priority**: High
**Story Points**: 3

#### Tasks:
- [ ] Create `app/Http/Controllers/KeyController.php`
- [ ] Implement `generate()` - generate new key
- [ ] Implement `delete()` - delete key
- [ ] Implement `block()` - block key
- [ ] Implement `unblock()` - unblock key
- [ ] Implement `update()` - update key settings
- [ ] Implement `info()` - get key details
- [ ] Add CSRF protection for POST endpoints

**Acceptance Criteria**:
- All key operations work correctly
- CSRF tokens validated
- Success/error responses returned

---

### Story 8: Routes & Middleware (Day 3)

**ID**: SPRINT-1-008
**Priority**: High
**Story Points**: 2

#### Tasks:
- [ ] Define all routes in `routes/web.php`
- [ ] Add auth middleware to protected routes
- [ ] Add rate limiting if needed
- [ ] Group API routes under `/api` prefix
- [ ] Test all routes return expected responses

**Acceptance Criteria**:
- All routes accessible
- Protected routes require authentication
- Route groups organized correctly

---

### Story 9: Dashboard Layout & Design System (Day 4)

**ID**: SPRINT-1-009
**Priority**: High
**Story Points**: 4

#### Tasks:
- [ ] Create `resources/views/layouts/app.blade.php`
- [ ] Integrate Alpine.js (CDN or npm)
- [ ] Configure Tailwind CSS colors (Tosca #20B2AA, Greys)
- [ ] Create header component (title, logout, refresh time, API status)
- [ ] Create base card component style
- [ ] Create base table component style
- [ ] Add loading spinner component
- [ ] Add error alert component

**Acceptance Criteria**:
- Tosca-grey color scheme applied
- Alpine.js loaded and working
- Responsive layout works on mobile

---

### Story 10: Dashboard Components - Key Cards (Day 4)

**ID**: SPRINT-1-010
**Priority**: High
**Story Points**: 4

#### Tasks:
- [ ] Create `resources/views/components/key-card.blade.php`
- [ ] Display: token name (masked), spend, budget, usage %
- [ ] Display: status indicator (Normal/Warning/Critical/Expired/Blocked)
- [ ] Display: expiry date with countdown
- [ ] Display: allowed models
- [ ] Add color coding based on usage % (green < 70%, yellow < 90%, red > 90%)
- [ ] Add hover effects

**Acceptance Criteria**:
- All key info displayed correctly
- Status colors match thresholds
- Expiry shown in human-readable format

---

### Story 11: Dashboard Components - Stats Table (Day 4)

**ID**: SPRINT-1-011
**Priority**: High
**Story Points**: 3

#### Tasks:
- [ ] Create `resources/views/components/stats-table.blade.php`
- [ ] Columns: Token Name, Models, Spend, Budget, Usage %, User ID, Status, Expires, Actions
- [ ] Add action buttons: Refresh, Delete, Block/Unblock
- [ ] Add confirm dialogs for destructive actions
- [ ] Add sorting (optional)
- [ ] Add row hover effects

**Acceptance Criteria**:
- All columns display correctly
- Action buttons trigger correct endpoints
- Delete/block show confirmation

---

### Story 12: Dashboard Components - Daily Cost Tracker (Day 4)

**ID**: SPRINT-1-012
**Priority**: Medium
**Story Points**: 3

#### Tasks:
- [ ] Create `resources/views/components/daily-cost-tracker.blade.php`
- [ ] Display total daily spend (last 7 days)
- [ ] Display daily spend per key
- [ ] Add simple bar chart (using Tailwind/CSS only)
- [ ] Show cost trend (up/down indicators)
- [ ] Add date range filter (last 7, 30, 90 days)

**Acceptance Criteria**:
- Daily costs displayed correctly
- Bar chart renders without JS libraries
- Date filter works

---

### Story 13: Dashboard Main View (Day 5)

**ID**: SPRINT-1-013
**Priority**: High
**Story Points**: 4

#### Tasks:
- [ ] Create `resources/views/dashboard/index.blade.php`
- [ ] Integrate all components
- [ ] Add Alpine.js auto-refresh (11 seconds)
- [ ] Add refresh countdown timer
- [ ] Add last refresh timestamp
- [ ] Add manual refresh button
- [ ] Handle loading states
- [ ] Handle error states

**Acceptance Criteria**:
- Dashboard renders all components
- Auto-refreshes every 11 seconds
- Manual refresh works
- Errors displayed gracefully

---

### Story 14: Loading & Error States (Day 5)

**ID**: SPRINT-1-014
**Priority**: Medium
**Story Points**: 2

#### Tasks:
- [ ] Add skeleton loading state for cards
- [ ] Add skeleton loading state for table
- [ ] Add API error handling (show alert)
- [ ] Add retry mechanism for failed requests
- [ ] Add offline indicator

**Acceptance Criteria**:
- Loading states display during API calls
- Errors shown with clear messages
- Retry button on failure

---

### Story 15: Testing & Bug Fixes (Day 5)

**ID**: SPRINT-1-015
**Priority**: High
**Story Points**: 4

#### Tasks:
- [ ] Test Docker compose - cold start
- [ ] Test 11-second auto-refresh - verify no API abuse
- [ ] Test cache verification - check TTL works
- [ ] Test API failure simulation - verify degraded UI
- [ ] Test all key operations (block/unblock/delete/generate)
- [ ] Test date filters for spend tracking
- [ ] Test responsive design (mobile/tablet/desktop)
- [ ] Fix any bugs found
- [ ] Performance optimization

**Acceptance Criteria**:
- All features working as expected
- No console errors
- Cache hits > 80% during refresh
- Page load < 1 second

---

## Sprint Timeline

| Day | Focus | Stories |
|-----|-------|---------|
| Day 1 | Foundation | SPRINT-1-001, SPRINT-1-002, SPRINT-1-003 |
| Day 2 | Service Layer | SPRINT-1-004, SPRINT-1-005 |
| Day 3 | Backend | SPRINT-1-006, SPRINT-1-007, SPRINT-1-008 |
| Day 4 | Frontend Components | SPRINT-1-009, SPRINT-1-010, SPRINT-1-011, SPRINT-1-012 |
| Day 5 | Integration & Testing | SPRINT-1-013, SPRINT-1-014, SPRINT-1-015 |

---

## Sprint Goals (Success Criteria)

- [ ] Dockerized application runs with single command
- [ ] Single user authentication working
- [ ] Dashboard displays key stats with auto-refresh (11s)
- [ ] Daily cost tracking functional
- [ ] Token expiry/masa aktif displayed
- [ ] Tosca-grey color scheme implemented
- [ ] All key operations working (CRUD + block/unblock)
- [ ] API responses cached (3-5s TTL)
- [ ] Error handling graceful
- [ ] Responsive design

---

## Definition of Done

**For Each Story**:
- Code written and committed
- Code reviewed (self-review)
- Testing completed
- Documentation updated (if needed)
- No blocking bugs

**For Sprint**:
- All stories completed
- Sprint goals met
- Demo-ready application
- Deployment documentation complete

---

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| AbworksLLM API changes | High | Use flexible response parsing, add fallbacks |
| API rate limiting | Medium | Implement caching (3-5s TTL), handle 429 errors |
| Docker compatibility | Low | Use standard base images, test on multiple systems |
| Authentication complexity | Low | Use Laravel Breeze, single user only |

---

## Notes

- Sprint can be adjusted based on actual velocity
- If blockers occur, deprioritize non-critical features (charts, advanced filtering)
- Keep communication with stakeholder on progress
- Daily standup check-ins recommended
