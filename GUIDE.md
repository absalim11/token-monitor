# ABW Token Monitor Dashboard - User Guide

## Overview

ABW Token Monitor Dashboard adalah aplikasi monitoring real-time untuk penggunaan token LLM melalui LiteLLM Proxy API. Dashboard ini menyediakan tracking per-key, biaya harian, expiry management, dan status koneksi API.

## Features

- **Real-time Monitoring**: Auto-refresh setiap 10 detik
- **Per-Key Statistics**: Track spend, budget, usage, dan expiry per key
- **Daily Cost Tracking**: Visualisasi biaya harian (7d, 30d, 90d)
- **Key Management**: Block, unblock, delete keys
- **Dark Mode**: Support tema gelap
- **Database Error Handling**: Auto-stop refresh saat koneksi database gagal
- **Responsive Design**: Mobile-friendly

## Prerequisites

- Docker & Docker Compose
- LiteLLM Proxy API dengan database terkoneksi
- Web browser modern (Chrome, Firefox, Safari, Edge)

## Installation

### 1. Clone Repository

```bash
git clone <repository-url>
cd llm-monitor
```

### 2. Configure Environment

Edit `.env` file:

```env
APP_NAME="ABW Token Monitor"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=http://localhost:8081

# LiteLLM API Configuration
LITELLM_API_URL=https://litellm-api.up.railway.app
LITELLM_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxx
LITELLM_CACHE_TTL=5
LITELLM_TIMEOUT=30
LITELLM_RETRY_TIMES=3
LITELLM_RETRY_DELAY=1000

# Database
DB_CONNECTION=sqlite

# Breeze credentials (default admin)
# Email: admin@abw.com
# Password: password
```

### 3. Start Application

```bash
docker-compose up -d
```

### 4. Access Dashboard

Buka browser: `http://localhost:8081`

Login dengan:
- Email: `admin@abw.com`
- Password: `password`

## Configuration

### LiteLLM API Settings

Edit `config/litellm.php`:

```php
return [
    'api_url' => env('LITELLM_API_URL', 'https://litellm-api.example.com'),
    'api_key' => env('LITELLM_API_KEY', ''),
    'cache_ttl' => env('LITELLM_CACHE_TTL', 5),
    'timeout' => env('LITELLM_TIMEOUT', 30),
    'retry_times' => env('LITELLM_RETRY_TIMES', 3),
    'retry_delay' => env('LITELLM_RETRY_DELAY', 1000),
];
```

### Port Configuration

Edit `docker-compose.yml` untuk mengubah port:

```yaml
services:
  nginx:
    ports:
      - "8081:80"  # Ubah 8081 ke port yang diinginkan
```

## Usage

### Dashboard Overview

**Token Overview Section**:
- API Status indicator (Connected/Error/DB Error)
- Refresh button untuk manual refresh
- Last refresh timestamp
- Next refresh countdown

**Key Overview Cards**:
- Display name (alias atau masked key)
- Models yang diizinkan
- Current spend vs budget
- Usage percentage dengan progress bar
- Expiry date
- User ID
- Status (Normal/Warning/Critical/Expired/Blocked)

**Detailed Statistics Table**:
- Kolom: Token, Models, Spend, Budget, Usage, User, Status, Expires, Actions
- Actions: Refresh, Block/Unblock, Delete

**Daily Cost Tracker**:
- Period selector: 7 Days, 30 Days, 90 Days
- Total spend untuk period terpilih
- Average daily spend
- Visualisasi bar chart per hari

### Key Status Indicators

| Status | Kondisi | Warna |
|--------|---------|-------|
| Normal | Usage ≤ 70% | Hijau |
| Warning | 70% < Usage ≤ 90% | Kuning |
| Critical | Usage > 90% | Merah |
| Expired | Expiry date passed | Merah |
| Blocked | Key diblock admin | Abu-abu |

### Managing Keys

**Block a Key**:
1. Klik tombol ❌ di kolom Actions
2. Confirm dialog akan muncul
3. Status berubah ke "Blocked"

**Unblock a Key**:
1. Klik tombol ✓ di kolom Actions
2. Confirm dialog akan muncul
3. Status kembali ke normal

**Delete a Key**:
1. Klik tombol 🗑️ di kolom Actions
2. Confirm dialog akan muncul dengan nama key
3. **Irreversible** - key akan dihapus permanen

### Dark Mode

Toggle dark mode dengan klik icon 🌙/☀️ di navbar (sebelah profil user).
Preference disimpan di browser localStorage.

## Troubleshooting

### "No connected db" Error

**Problem**: LiteLLM API reported database connection failed.

**Solution**:
1. Check LiteLLM server status dan koneksi database
2. Verify LITELLM_API_URL dan LITELLM_API_KEY di .env
3. Auto-refresh akan otomatis berhenti untuk mencegah excessive API calls
4. Perbaiki koneksi database LiteLLM
5. Refresh dashboard secara manual

### Dashboard Not Loading

**Problem**: Blank screen atau error saat akses dashboard.

**Solution**:
1. Check Docker containers: `docker-compose ps`
2. Check logs: `docker-compose logs -f`
3. Clear caches: `docker-compose exec php php artisan view:clear`
4. Restart containers: `docker-compose restart`

### API Health Check Failed

**Problem**: API status shows "Error" atau "Disconnected".

**Solution**:
1. Verify LITELLM_API_URL correct
2. Check firewall/network ke LiteLLM API
3. Verify API key valid
4. Test API directly: `curl -H "Authorization: Bearer YOUR_KEY" https://litellm-api.example.com/health`

### Data Not Updating

**Problem**: Data tidak refresh secara otomatis.

**Solution**:
1. Check auto-refresh status (tidak aktif jika db error)
2. Click Refresh button untuk manual refresh
3. Check browser console untuk JavaScript errors
4. Clear browser cache dan localStorage

## Development

### Project Structure

```
llm-monitor/
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardController.php  # Dashboard endpoints
│   │   └── KeyController.php         # Key management endpoints
│   ├── Services/
│   │   └── LiteLLMService.php        # LiteLLM API client
│   └── Exceptions/
│       └── LiteLLMDatabaseException.php
├── config/
│   └── litellm.php                   # LiteLLM configuration
├── resources/views/
│   ├── layouts/
│   │   ├── app.blade.php             # Main layout
│   │   └── navigation.blade.php      # Navigation bar
│   └── dashboard/
│       └── index.blade.php           # Dashboard view dengan Alpine.js
├── routes/
│   └── web.php                       # Route definitions
├── docker/
│   ├── php/Dockerfile
│   └── nginx/default.conf
└── docker-compose.yml                # Container orchestration
```

### API Endpoints

**Authentication**: Required untuk semua endpoint

```
GET  /api/health           # Health check
GET  /api/keys             # List all keys
GET  /api/daily-spend      # Get daily spend report
GET  /api/models           # List available models
POST /api/keys/delete     # Delete a key
POST /api/keys/block      # Block a key
POST /api/keys/unblock    # Unblock a key
```

### Running Tests

```bash
# Run PHPUnit tests
docker-compose exec php php artisan test

# Run specific test
docker-compose exec php php artisan test --filter DashboardTest
```

### Adding New Features

**Menambah endpoint API baru**:
1. Tambah method di `DashboardController.php`
2. Tambah route di `routes/web.php`
3. Tambah fetch call di Alpine.js component

**Menambah section dashboard baru**:
1. Tambah HTML di `resources/views/dashboard/index.blade.php`
2. Tambah data properties di Alpine.js `dashboard()` function
3. Tambah methods untuk data fetching/manipulation

### Customizing Colors

Edit Tailwind config di `resources/views/layouts/app.blade.php`:

```javascript
tailwind.config = {
    theme: {
        extend: {
            colors: {
                tosca: '#20B2AA',           // Main brand color
                'tosca-dark': '#008080',    // Hover state
                'tosca-light': '#48D1CC',   // Light variant
            }
        }
    }
}
```

## Security

- **Authentication**: Laravel Breeze dengan single user admin
- **CSRF Protection**: Token required untuk semua POST requests
- **API Key Encryption**: LiteLLM API key disimpan di environment variable
- **Rate Limiting**: Cache TTL mencegah excessive API calls
- **Input Validation**: Laravel validation untuk semua inputs

## Maintenance

### Backup

Backup database dan configuration:

```bash
# Backup SQLite database
docker-compose exec php cp /var/www/html/database/database.sqlite ./database.backup

# Backup environment
cp .env .env.backup
```

### Update

```bash
# Pull latest changes
git pull origin main

# Rebuild containers
docker-compose up -d --build

# Clear caches
docker-compose exec php php artisan view:clear
docker-compose exec php php artisan config:clear
docker-compose exec php php artisan cache:clear
```

### Logs

View application logs:

```bash
# Laravel logs
docker-compose exec php tail -f /var/www/html/storage/logs/laravel.log

# Nginx logs
docker-compose logs -f nginx

# PHP-FPM logs
docker-compose logs -f php
```

## License

Proprietary - ABW Internal

## Support

Untuk issues atau questions, hubungi development team.
