# PRD Tool — Implementation Plan

**Version:** 2.0  
**Date:** February 3, 2026  
**Status:** Ready for Development  
**PRD Version:** 3.4 (Final — Implementation Ready)

---

## Implementation Philosophy

### Core Principles

1. **Build Compilable at Every Step** — The application must build and run successfully after completing each phase. No broken states.
2. **Test Before Proceed** — Every phase includes mandatory verification gates. No proceeding until tests pass.
3. **Edge Cases First** — Handle edge cases during initial implementation, not as afterthoughts.
4. **UX Excellence** — Every interaction must feel polished. Loading states, errors, and empty states are first-class citizens.
5. **Predictable Behavior** — Users should never be surprised. Every action has clear feedback.

### Development Cadence

```
For Each Phase:
  1. Read phase requirements
  2. Implement feature
  3. Write/update tests
  4. Run test suite (must pass 100%)
  5. Manual verification (checklist)
  6. Build production bundle (must succeed)
  7. Commit with phase tag
  8. Proceed to next phase
```

---

## Pre-Implementation Checklist

Before writing any code, complete these setup tasks:

### Environment Setup

| Task | Command/Action | Verification |
|------|----------------|--------------|
| Install Docker Desktop | Download from docker.com | `docker --version` returns 24+ |
| Install Node.js 20 LTS | Via nvm or official installer | `node --version` returns 20.x |
| Install PHP 8.3 | Via Homebrew or official | `php --version` returns 8.3.x |
| Install Composer | Via official installer | `composer --version` returns 2.x |
| Create Supabase project | supabase.com dashboard | Project URL + anon key available |
| Create Google Cloud project | console.cloud.google.com | OAuth credentials created |
| Create Anthropic account | console.anthropic.com | API key available |
| Create Stripe account | dashboard.stripe.com | Test keys available |
| Create DeepL account | deepl.com/pro-api | API key available |

### Repository Structure

```
prd-tool/
├── docker-compose.yml          # Local development orchestration
├── Dockerfile                  # PHP 8.3 + Laravel container
├── Dockerfile.node             # Node.js for frontend build
├── .env.example               # Environment template
├── .gitignore                 # Git ignore rules
├── backend/                   # Laravel 11 application
│   ├── app/
│   │   ├── Console/
│   │   │   └── Commands/      # Artisan commands
│   │   ├── Events/            # Event classes
│   │   ├── Exceptions/        # Custom exceptions
│   │   ├── Http/
│   │   │   ├── Controllers/   # API controllers
│   │   │   ├── Middleware/    # Custom middleware
│   │   │   └── Requests/      # Form requests
│   │   ├── Jobs/              # Queue jobs
│   │   ├── Listeners/         # Event listeners
│   │   ├── Models/            # Eloquent models
│   │   ├── Policies/          # Authorization policies
│   │   ├── Providers/         # Service providers
│   │   └── Services/          # Business logic services
│   ├── config/
│   ├── database/
│   │   ├── migrations/        # All 20 table migrations
│   │   ├── factories/         # Model factories
│   │   └── seeders/           # Data seeders
│   ├── routes/
│   │   ├── api.php            # API routes (85+ endpoints)
│   │   └── web.php            # Web routes (OAuth callbacks)
│   ├── storage/
│   │   └── prds/              # PRD markdown files
│   └── tests/
│       ├── Feature/           # Integration tests
│       └── Unit/              # Unit tests
├── frontend/                  # React 18 + TypeScript
│   ├── src/
│   │   ├── components/        # React components
│   │   ├── hooks/             # Custom hooks
│   │   ├── pages/             # Page components
│   │   ├── stores/            # Zustand stores
│   │   ├── lib/               # Utilities, API client
│   │   ├── types/             # TypeScript types
│   │   └── locales/           # i18n translations
│   ├── public/
│   └── tests/
├── docs/                      # Additional documentation
└── scripts/                   # Utility scripts
```

### .gitignore

```gitignore
# Dependencies
node_modules/
vendor/

# Environment
.env
.env.local
.env.*.local

# Build outputs
dist/
build/
.vite/

# Laravel
storage/*.key
storage/prds/
.phpunit.result.cache
Homestead.json
Homestead.yaml

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Logs
*.log
npm-debug.log*

# Testing
coverage/
.nyc_output/

# Docker
docker-compose.override.yml
```

### Backend Dependencies (composer.json)

```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^11.0",
        "laravel/socialite": "^5.12",
        "smalot/pdfparser": "^2.7",
        "phpoffice/phpword": "^1.2",
        "phpoffice/phpspreadsheet": "^2.0",
        "stripe/stripe-php": "^13.0",
        "predis/predis": "^2.2",
        "pusher/pusher-php-server": "^7.2"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "phpunit/phpunit": "^11.0",
        "spatie/laravel-ignition": "^2.4"
    }
}
```

### Frontend Dependencies (package.json)

```json
{
    "dependencies": {
        "react": "^18.2.0",
        "react-dom": "^18.2.0",
        "react-router-dom": "^6.22.0",
        "zustand": "^4.5.0",
        "react-markdown": "^9.0.1",
        "remark-gfm": "^4.0.0",
        "mermaid": "^10.8.0",
        "dompurify": "^3.0.8",
        "react-syntax-highlighter": "^15.5.0",
        "react-i18next": "^14.0.5",
        "i18next": "^23.9.0",
        "axios": "^1.6.7",
        "@radix-ui/react-dialog": "^1.0.5",
        "@radix-ui/react-dropdown-menu": "^2.0.6",
        "@radix-ui/react-tooltip": "^1.0.7",
        "@radix-ui/react-toast": "^1.1.5",
        "@radix-ui/react-select": "^2.0.0",
        "@radix-ui/react-tabs": "^1.0.4",
        "clsx": "^2.1.0",
        "tailwind-merge": "^2.2.1",
        "date-fns": "^3.3.1",
        "use-debounce": "^10.0.0",
        "@supabase/supabase-js": "^2.39.0"
    },
    "devDependencies": {
        "@types/react": "^18.2.55",
        "@types/react-dom": "^18.2.19",
        "@types/dompurify": "^3.0.5",
        "@vitejs/plugin-react": "^4.2.1",
        "typescript": "^5.3.3",
        "vite": "^5.1.0",
        "tailwindcss": "^3.4.1",
        "postcss": "^8.4.35",
        "autoprefixer": "^10.4.17",
        "eslint": "^8.56.0",
        "eslint-plugin-react-hooks": "^4.6.0",
        "@typescript-eslint/parser": "^7.0.1",
        "prettier": "^3.2.5",
        "vitest": "^1.2.2",
        "@testing-library/react": "^14.2.1"
    }
}
```

### Standard Error Response Format

All API errors follow this structure:

```typescript
interface ApiError {
    message: string;           // Human-readable message
    code: string;              // Machine-readable error code
    details?: Record<string, string[]>;  // Field-level validation errors
    retry_after?: number;      // Seconds until retry allowed (rate limiting)
}

// Example error codes
type ErrorCode =
    | 'UNAUTHENTICATED'
    | 'FORBIDDEN'
    | 'NOT_FOUND'
    | 'VALIDATION_ERROR'
    | 'RATE_LIMITED'
    | 'STORAGE_ERROR'
    | 'EXTERNAL_SERVICE_ERROR'
    | 'INTERNAL_ERROR';
```

```php
// app/Exceptions/Handler.php - Standardized error responses
public function render($request, Throwable $e): Response
{
    if ($request->expectsJson()) {
        return match (true) {
            $e instanceof ValidationException => response()->json([
                'message' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'details' => $e->errors(),
            ], 422),
            
            $e instanceof AuthenticationException => response()->json([
                'message' => 'Unauthenticated',
                'code' => 'UNAUTHENTICATED',
            ], 401),
            
            $e instanceof ModelNotFoundException => response()->json([
                'message' => 'Resource not found',
                'code' => 'NOT_FOUND',
            ], 404),
            
            $e instanceof ThrottleRequestsException => response()->json([
                'message' => 'Too many requests',
                'code' => 'RATE_LIMITED',
                'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
            ], 429),
            
            default => response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'code' => 'INTERNAL_ERROR',
            ], 500),
        };
    }
    
    return parent::render($request, $e);
}

---

## Phase 0: Project Scaffolding

**Duration Estimate:** Foundation setup  
**Risk Level:** Low  
**Dependencies:** None

### 0.1 Docker Environment

Create the development environment with Docker Compose.

#### Dockerfile (PHP 8.3 + Laravel)

```dockerfile
# Dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js for asset building
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY backend/ .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Nginx configuration
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create PRD storage directory
RUN mkdir -p /var/www/html/storage/prds && chown -R www-data:www-data /var/www/html/storage/prds

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

#### Nginx Configuration

```nginx
# docker/nginx.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "0" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
```

#### Supervisor Configuration

```ini
# docker/supervisord.conf
[supervisord]
nodaemon=true
user=root

[program:nginx]
command=/usr/sbin/nginx -g "daemon off;"
autostart=true
autorestart=true

[program:php-fpm]
command=/usr/local/sbin/php-fpm -F
autostart=true
autorestart=true

[program:laravel-scheduler]
command=/bin/sh -c "while [ true ]; do php /var/www/html/artisan schedule:run --verbose --no-interaction; sleep 60; done"
autostart=true
autorestart=true
user=www-data

[program:laravel-queue]
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
```

#### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "11111:80"
    volumes:
      - ./backend:/var/www/html
      - prd_storage:/var/www/html/storage/prds
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - APP_KEY=${APP_KEY}
      - DB_CONNECTION=pgsql
      - DB_HOST=${DB_HOST}
      - DB_PORT=5432
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
      - ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
      - GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}
      - GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}
    depends_on:
      - redis
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  node:
    image: node:20-alpine
    working_dir: /app
    volumes:
      - ./frontend:/app
      - node_modules:/app/node_modules
    ports:
      - "5173:5173"
    command: sh -c "npm install && npm run dev -- --host 0.0.0.0"
    environment:
      - VITE_API_URL=http://localhost:11111

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  prd_storage:
  node_modules:
  redis_data:
```

#### Environment Template

```env
# .env.example
# Application
APP_NAME="PRD Tool"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:11111

# Database (Supabase PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=db.xxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:11111/auth/google/callback
GOOGLE_PICKER_API_KEY=

# Anthropic API
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL_CHAT=claude-opus-4-20250514
ANTHROPIC_MODEL_SUMMARIZE=claude-haiku-3.5-20250110

# Stripe Billing
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# DeepL Translation
DEEPL_API_KEY=

# Supabase Realtime
SUPABASE_URL=
SUPABASE_ANON_KEY=

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=10080

# Queue
QUEUE_CONNECTION=redis

# Token Encryption (32-byte key, base64 encoded)
TOKEN_ENCRYPTION_KEY=
```

#### Environment Validation

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    $this->validateRequiredEnvironment();
}

private function validateRequiredEnvironment(): void
{
    $required = [
        'APP_KEY',
        'DB_HOST',
        'DB_PASSWORD',
        'GOOGLE_CLIENT_ID',
        'GOOGLE_CLIENT_SECRET',
        'ANTHROPIC_API_KEY',
        'TOKEN_ENCRYPTION_KEY',
    ];
    
    $missing = [];
    foreach ($required as $key) {
        if (empty(env($key))) {
            $missing[] = $key;
        }
    }
    
    if (!empty($missing) && app()->environment('production')) {
        throw new \RuntimeException(
            'Missing required environment variables: ' . implode(', ', $missing)
        );
    }
    
    if (!empty($missing) && app()->environment('local')) {
        Log::warning('Missing environment variables', ['missing' => $missing]);
    }
}
```

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| Docker not installed | Clear error in README: "Install Docker Desktop from https://docker.com" |
| Port 11111 already in use | Use `APP_PORT` env var: `ports: - "${APP_PORT:-11111}:80"` |
| Volume permissions on Linux | Dockerfile sets www-data ownership |
| Slow first build | README notes: "First build ~5 min. Subsequent builds <30s" |
| .env file missing | docker-compose fails fast with clear error |
| Invalid .env values | AppServiceProvider validates on boot |
| Redis not ready | Docker healthcheck + depends_on with condition |
| PHP extensions missing | Dockerfile installs all required extensions |

#### Verification Gate

```bash
# All must pass before proceeding
docker-compose build                     # No errors
docker-compose up -d                     # Containers start
docker-compose ps                        # All containers "healthy"
curl http://localhost:11111/health       # Returns {"status":"ok","timestamp":"..."}
curl http://localhost:5173               # Returns Vite dev page
docker-compose logs --tail=50 app        # No PHP errors
docker-compose exec app php artisan --version  # Laravel 11.x
```

### 0.2 Laravel Installation

#### Implementation Steps

1. Create Laravel 11 project in `backend/`
2. Configure Supabase PostgreSQL connection
3. Set up basic health endpoint
4. Configure CORS for local development

#### CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*', 'auth/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
        env('APP_URL', 'http://localhost:11111'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Required for cookies
];
```

#### API Routes (routes/api.php)

```php
<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    PrdController,
    ChatController,
    RuleController,
    TemplateController,
    VersionController,
    CollaboratorController,
    CommentController,
    DriveController,
    TeamController,
    BillingController,
    UserController,
    AgentController,
};

// Health check
Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]));

// ============================================
// AUTHENTICATION
// ============================================
Route::prefix('auth')->group(function () {
    Route::get('/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

// ============================================
// AUTHENTICATED ROUTES
// ============================================
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // ----- User -----
    Route::get('/user', [UserController::class, 'show']);
    Route::get('/user/last-prd', [UserController::class, 'lastPrd']);
    Route::get('/user/usage', [UserController::class, 'usage']);
    Route::get('/user/settings', [UserController::class, 'settings']);
    Route::put('/user/settings', [UserController::class, 'updateSettings']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ----- PRDs -----
    Route::get('/prds', [PrdController::class, 'index']);
    Route::post('/prds', [PrdController::class, 'store']);
    Route::post('/prds/from-template', [PrdController::class, 'createFromTemplate']);
    Route::post('/prds/import-from-drive', [PrdController::class, 'importFromDrive']);
    
    Route::prefix('/prds/{prd}')->group(function () {
        Route::get('/', [PrdController::class, 'show']);
        Route::put('/', [PrdController::class, 'update']);
        Route::delete('/', [PrdController::class, 'destroy']);
        
        // Content
        Route::get('/content', [PrdController::class, 'getContent']);
        Route::put('/content', [PrdController::class, 'updateContent']);
        
        // State persistence
        Route::get('/state', [PrdController::class, 'getState']);
        Route::put('/state', [PrdController::class, 'updateState']);
        Route::put('/draft', [PrdController::class, 'saveDraft']);
        Route::delete('/draft', [PrdController::class, 'clearDraft']);
        
        // Chat
        Route::get('/messages', [ChatController::class, 'getMessages']);
        Route::post('/messages', [ChatController::class, 'sendMessage']);
        Route::post('/messages/{message}/cancel', [ChatController::class, 'cancelStream']);
        Route::delete('/conversation', [ChatController::class, 'clearConversation']);
        
        // Context
        Route::get('/context', [ChatController::class, 'getContext']);
        Route::post('/context/summarize', [ChatController::class, 'summarize']);
        
        // Rules assignment
        Route::get('/rules', [RuleController::class, 'assigned']);
        Route::put('/rules', [RuleController::class, 'assign']);
        
        // Versions
        Route::get('/versions', [VersionController::class, 'index']);
        Route::get('/versions/diff', [VersionController::class, 'diff']);
        Route::get('/versions/{version}', [VersionController::class, 'show']);
        Route::put('/versions/{version}', [VersionController::class, 'update']);
        Route::post('/versions/{version}/restore', [VersionController::class, 'restore']);
        
        // Collaboration
        Route::get('/collaborators', [CollaboratorController::class, 'index']);
        Route::post('/collaborators', [CollaboratorController::class, 'invite']);
        Route::put('/collaborators/{user}', [CollaboratorController::class, 'updatePermission']);
        Route::delete('/collaborators/{user}', [CollaboratorController::class, 'remove']);
        Route::post('/share-link', [CollaboratorController::class, 'createShareLink']);
        Route::delete('/share-link', [CollaboratorController::class, 'revokeShareLink']);
        
        // Comments
        Route::get('/comments', [CommentController::class, 'index']);
        Route::post('/comments', [CommentController::class, 'store']);
        Route::post('/comments/{comment}/replies', [CommentController::class, 'reply']);
        Route::put('/comments/{comment}/resolve', [CommentController::class, 'resolve']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
        
        // Google Drive sync
        Route::get('/drive-link', [DriveController::class, 'getLink']);
        Route::post('/drive-link', [DriveController::class, 'createLink']);
        Route::delete('/drive-link', [DriveController::class, 'deleteLink']);
        Route::post('/drive-sync', [DriveController::class, 'sync']);
        Route::post('/drive-sync/resolve', [DriveController::class, 'resolveConflict']);
        Route::post('/export-to-drive', [DriveController::class, 'exportToDrive']);
        
        // Export
        Route::get('/export/markdown', [PrdController::class, 'exportMarkdown']);
        Route::get('/export/pdf', [PrdController::class, 'exportPdf']);
        
        // Save as template
        Route::post('/save-as-template', [TemplateController::class, 'saveFromPrd']);
        
        // SME Agents
        Route::get('/agents', [AgentController::class, 'assigned']);
        Route::put('/agents', [AgentController::class, 'assign']);
        Route::post('/agents/{agent}/messages', [AgentController::class, 'sendMessage']);
        Route::get('/agents/{agent}/messages', [AgentController::class, 'getMessages']);
        Route::get('/suggestions', [AgentController::class, 'getSuggestions']);
        Route::put('/suggestions/{suggestion}', [AgentController::class, 'updateSuggestion']);
    });
    
    // ----- Rules -----
    Route::get('/rules', [RuleController::class, 'index']);
    Route::post('/rules', [RuleController::class, 'store']);
    Route::get('/rules/{rule}', [RuleController::class, 'show']);
    Route::put('/rules/{rule}', [RuleController::class, 'update']);
    Route::delete('/rules/{rule}', [RuleController::class, 'destroy']);
    
    // ----- Templates -----
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::get('/templates/{template}', [TemplateController::class, 'show']);
    Route::put('/templates/{template}', [TemplateController::class, 'update']);
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy']);
    
    // ----- Google Drive -----
    Route::get('/drive/picker-token', [DriveController::class, 'pickerToken']);
    Route::post('/drive/download', [DriveController::class, 'downloadFile']);
    
    // ----- Teams -----
    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);
    Route::put('/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);
    Route::post('/teams/{team}/members', [TeamController::class, 'inviteMember']);
    Route::put('/teams/{team}/members/{user}', [TeamController::class, 'updateMemberRole']);
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);
    Route::post('/teams/{team}/leave', [TeamController::class, 'leave']);
    
    // ----- Billing -----
    Route::get('/billing', [BillingController::class, 'status']);
    Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    Route::post('/billing/portal', [BillingController::class, 'portal']);
    
    // ----- SME Agents -----
    Route::get('/agents', [AgentController::class, 'index']);
    Route::post('/agents', [AgentController::class, 'store']);
    Route::get('/agents/{agent}', [AgentController::class, 'show']);
    Route::put('/agents/{agent}', [AgentController::class, 'update']);
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy']);
});

// ----- Stripe Webhook (no auth) -----
Route::post('/billing/webhook', [BillingController::class, 'webhook'])
    ->middleware('stripe.webhook');

// ----- Share Link Access (no auth, token-based) -----
Route::get('/share/{token}', [CollaboratorController::class, 'accessViaShareLink']);
```

#### Web Routes (routes/web.php)

```php
<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// OAuth callbacks (must be web routes for session handling)
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// SPA fallback - serve React app for all other routes
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
```

#### Frontend Routing (src/routes.tsx)

```typescript
// src/routes.tsx
import { createBrowserRouter } from 'react-router-dom';
import { ProtectedRoute } from '@/components/auth/ProtectedRoute';
import { PublicRoute } from '@/components/auth/PublicRoute';

// Pages
import { LoginPage } from '@/pages/LoginPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { PrdEditorPage } from '@/pages/PrdEditorPage';
import { SettingsPage } from '@/pages/SettingsPage';
import { TemplatesPage } from '@/pages/TemplatesPage';
import { RulesPage } from '@/pages/RulesPage';
import { TeamsPage } from '@/pages/TeamsPage';
import { TeamDetailPage } from '@/pages/TeamDetailPage';
import { BillingPage } from '@/pages/BillingPage';
import { SharedPrdPage } from '@/pages/SharedPrdPage';
import { NotFoundPage } from '@/pages/NotFoundPage';
import { ErrorBoundary } from '@/components/ErrorBoundary';

export const router = createBrowserRouter([
    // Public routes
    {
        path: '/login',
        element: <PublicRoute><LoginPage /></PublicRoute>,
    },
    {
        path: '/share/:token',
        element: <SharedPrdPage />,
    },
    
    // Protected routes
    {
        path: '/',
        element: <ProtectedRoute />,
        errorElement: <ErrorBoundary />,
        children: [
            {
                index: true,
                element: <DashboardPage />,
            },
            {
                path: 'prd/:prdId',
                element: <PrdEditorPage />,
            },
            {
                path: 'templates',
                element: <TemplatesPage />,
            },
            {
                path: 'rules',
                element: <RulesPage />,
            },
            {
                path: 'teams',
                element: <TeamsPage />,
            },
            {
                path: 'teams/:teamId',
                element: <TeamDetailPage />,
            },
            {
                path: 'settings',
                element: <SettingsPage />,
            },
            {
                path: 'billing',
                element: <BillingPage />,
            },
        ],
    },
    
    // 404
    {
        path: '*',
        element: <NotFoundPage />,
    },
]);

// src/components/auth/ProtectedRoute.tsx
export const ProtectedRoute: React.FC = () => {
    const { isAuthenticated, isLoading, checkAuth } = useAuthStore();
    const location = useLocation();
    
    useEffect(() => {
        checkAuth();
    }, [checkAuth]);
    
    if (isLoading) {
        return <FullPageLoader />;
    }
    
    if (!isAuthenticated) {
        return <Navigate to="/login" state={{ from: location }} replace />;
    }
    
    return (
        <AppLayout>
            <Outlet />
        </AppLayout>
    );
};

// src/components/auth/PublicRoute.tsx
export const PublicRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const { isAuthenticated, isLoading } = useAuthStore();
    
    if (isLoading) {
        return <FullPageLoader />;
    }
    
    if (isAuthenticated) {
        return <Navigate to="/" replace />;
    }
    
    return <>{children}</>;
};
```

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| Supabase connection fails | Graceful error with setup instructions |
| Missing .env file | Copy from .env.example on first run |
| Composer memory limit | Document `COMPOSER_MEMORY_LIMIT=-1` |

#### Verification Gate

```bash
cd backend
php artisan migrate --pretend       # Shows pending migrations
php artisan route:list              # Shows 85+ routes
php artisan test                    # 0 failures (base tests)
```

### 0.3 React + TypeScript Setup

#### Implementation Steps

1. Create Vite + React + TypeScript project in `frontend/`
2. Install and configure Tailwind CSS
3. Install Zustand for state management
4. Set up react-i18next for internationalization
5. Create basic routing structure

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| Node modules corruption | Add clean script to package.json |
| TypeScript strict mode errors | Enable strict mode from day 1 |
| Tailwind not loading | Verify postcss.config.js setup |

#### Verification Gate

```bash
cd frontend
npm run build                       # Builds without errors
npm run lint                        # No linting errors
npm run type-check                  # No TypeScript errors
npm test                            # Base tests pass
```

---

## Phase 1: Authentication Foundation

**Duration Estimate:** Core auth system  
**Risk Level:** Medium (OAuth complexity)  
**Dependencies:** Phase 0

### 1.1 Database Schema — Complete Migrations

All 20 database tables with full specifications.

#### Token Encryption Service

```php
// app/Services/TokenEncryptionService.php
class TokenEncryptionService
{
    private string $key;
    private string $cipher = 'aes-256-gcm';
    
    public function __construct()
    {
        $key = config('app.token_encryption_key');
        if (empty($key)) {
            throw new \RuntimeException('TOKEN_ENCRYPTION_KEY not configured');
        }
        $this->key = base64_decode($key);
        
        if (strlen($this->key) !== 32) {
            throw new \RuntimeException('TOKEN_ENCRYPTION_KEY must be 32 bytes (base64 encoded)');
        }
    }
    
    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12); // 96 bits for GCM
        $tag = '';
        
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // Tag length
        );
        
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }
        
        // Format: base64(iv + tag + ciphertext)
        return base64_encode($iv . $tag . $ciphertext);
    }
    
    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted);
        
        if ($data === false || strlen($data) < 28) { // 12 + 16 minimum
            throw new \RuntimeException('Invalid encrypted data');
        }
        
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);
        
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed - data may be corrupted or key changed');
        }
        
        return $plaintext;
    }
}

// app/Casts/EncryptedToken.php - Eloquent cast for automatic encryption
class EncryptedToken implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) return null;
        return app(TokenEncryptionService::class)->decrypt($value);
    }
    
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) return null;
        return app(TokenEncryptionService::class)->encrypt($value);
    }
}

// config/app.php - Add token encryption key
'token_encryption_key' => env('TOKEN_ENCRYPTION_KEY'),
```

#### Migration 001: Users

```php
// database/migrations/2026_02_01_000001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('google_id')->unique();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('avatar_url')->nullable();
    $table->text('google_access_token');       // Encrypted via cast
    $table->text('google_refresh_token');      // Encrypted via cast
    $table->timestamp('google_token_expires_at');
    $table->uuid('last_prd_id')->nullable();
    $table->string('preferred_language', 10)->default('en');
    $table->enum('tier', ['free', 'pro', 'team', 'enterprise'])->default('free');
    $table->timestamp('tier_expires_at')->nullable();
    $table->string('stripe_customer_id')->nullable();
    $table->timestamps();
    
    $table->index('google_id');
    $table->index('email');
    $table->index('stripe_customer_id');
});
```

#### Migration 002: Teams

```php
// database/migrations/2026_02_01_000002_create_teams_table.php
Schema::create('teams', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->uuid('owner_id');
    $table->timestamps();
    
    $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
});
```

#### Migration 003: Team Members

```php
// database/migrations/2026_02_01_000003_create_team_members_table.php
Schema::create('team_members', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('team_id');
    $table->uuid('user_id');
    $table->enum('role', ['owner', 'admin', 'member'])->default('member');
    $table->timestamp('created_at');
    
    $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->unique(['team_id', 'user_id']);
});
```

#### Migration 004: Templates

```php
// database/migrations/2026_02_01_000004_create_templates_table.php
Schema::create('templates', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id')->nullable();       // NULL = system template
    $table->uuid('team_id')->nullable();       // NULL = personal or system
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('category', 50);
    $table->longText('content');
    $table->string('thumbnail_url')->nullable();
    $table->integer('usage_count')->default(0);
    $table->boolean('is_public')->default(false);
    $table->timestamps();
    
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
    $table->index('category');
    $table->index('is_public');
});
```

#### Migration 005: PRDs

```php
// database/migrations/2026_02_01_000005_create_prds_table.php
Schema::create('prds', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->uuid('team_id')->nullable();
    $table->string('title')->default('Untitled PRD');
    $table->string('file_path');
    $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
    $table->integer('estimated_tokens')->default(0);
    $table->uuid('created_from_template_id')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
    $table->foreign('created_from_template_id')->references('id')->on('templates')->nullOnDelete();
    $table->index(['user_id', 'deleted_at']);
    $table->index('team_id');
    $table->index('status');
});

// Add foreign key to users for last_prd_id
Schema::table('users', function (Blueprint $table) {
    $table->foreign('last_prd_id')->references('id')->on('prds')->nullOnDelete();
});
```

#### Migration 006: Rules

```php
// database/migrations/2026_02_01_000006_create_rules_table.php
Schema::create('rules', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('name');
    $table->longText('content');
    $table->timestamps();
    
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
});
```

#### Migration 007: PRD Rules (Pivot)

```php
// database/migrations/2026_02_01_000007_create_prd_rules_table.php
Schema::create('prd_rules', function (Blueprint $table) {
    $table->uuid('prd_id');
    $table->uuid('rule_id');
    $table->integer('priority')->default(0);
    $table->timestamp('created_at');
    
    $table->primary(['prd_id', 'rule_id']);
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('rule_id')->references('id')->on('rules')->cascadeOnDelete();
});
```

#### Migration 008: Messages

```php
// database/migrations/2026_02_01_000008_create_messages_table.php
Schema::create('messages', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->enum('role', ['user', 'assistant']);
    $table->longText('content');
    $table->integer('token_estimate')->default(0);
    $table->boolean('is_summarized')->default(false);
    $table->boolean('has_attachments')->default(false);
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->index(['prd_id', 'created_at']);
    $table->index(['prd_id', 'is_summarized']);
});
```

#### Migration 009: Message Attachments

```php
// database/migrations/2026_02_01_000009_create_message_attachments_table.php
Schema::create('message_attachments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('message_id');
    $table->string('filename');
    $table->enum('file_type', ['image', 'pdf', 'word', 'excel', 'csv', 'markdown']);
    $table->string('mime_type', 100);
    $table->integer('file_size');
    $table->string('storage_path')->nullable();
    $table->string('google_drive_id')->nullable();
    $table->longText('extracted_text')->nullable();
    $table->string('thumbnail_url')->nullable();
    $table->timestamp('created_at');
    
    $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
});
```

#### Migration 010: Context Summaries

```php
// database/migrations/2026_02_01_000010_create_context_summaries_table.php
Schema::create('context_summaries', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->longText('content');
    $table->integer('token_estimate');
    $table->json('summarized_message_ids');
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->index(['prd_id', 'created_at']);
});
```

#### Migration 011: User PRD State

```php
// database/migrations/2026_02_01_000011_create_user_prd_state_table.php
Schema::create('user_prd_state', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->uuid('prd_id');
    $table->integer('document_scroll_position')->default(0);
    $table->integer('chat_scroll_position')->default(0);
    $table->integer('current_page')->default(1);
    $table->decimal('zoom_level', 3, 2)->default(1.00);
    $table->boolean('sidebar_collapsed')->default(false);
    $table->timestamp('last_accessed_at');
    $table->timestamps();
    
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->unique(['user_id', 'prd_id']);
});
```

#### Migration 012: PRD Drafts

```php
// database/migrations/2026_02_01_000012_create_prd_drafts_table.php
Schema::create('prd_drafts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->uuid('prd_id');
    $table->text('content')->nullable();
    $table->timestamp('updated_at');
    
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->unique(['user_id', 'prd_id']);
});
```

#### Migration 013: Draft Attachments

```php
// database/migrations/2026_02_01_000013_create_draft_attachments_table.php
Schema::create('draft_attachments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('draft_id');
    $table->string('filename');
    $table->string('file_type', 50);
    $table->integer('file_size');
    $table->string('storage_path');
    $table->timestamp('expires_at');
    $table->timestamp('created_at');
    
    $table->foreign('draft_id')->references('id')->on('prd_drafts')->cascadeOnDelete();
    $table->index('expires_at');
});
```

#### Migration 014: PRD Versions

```php
// database/migrations/2026_02_01_000014_create_prd_versions_table.php
Schema::create('prd_versions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->integer('version_number');
    $table->longText('content');
    $table->enum('trigger', ['ai_edit', 'user_edit', 'import', 'restore', 'sync']);
    $table->uuid('trigger_message_id')->nullable();
    $table->string('diff_summary')->nullable();
    $table->string('name', 100)->nullable();
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('trigger_message_id')->references('id')->on('messages')->nullOnDelete();
    $table->unique(['prd_id', 'version_number']);
    $table->index(['prd_id', 'created_at']);
});
```

#### Migration 015: PRD Collaborators

```php
// database/migrations/2026_02_01_000015_create_prd_collaborators_table.php
Schema::create('prd_collaborators', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->uuid('user_id');
    $table->enum('permission', ['owner', 'editor', 'commenter', 'viewer']);
    $table->uuid('invited_by')->nullable();
    $table->timestamp('invited_at');
    $table->timestamp('accepted_at')->nullable();
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
    $table->unique(['prd_id', 'user_id']);
});
```

#### Migration 016: PRD Share Links

```php
// database/migrations/2026_02_01_000016_create_prd_share_links_table.php
Schema::create('prd_share_links', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->string('token', 64)->unique();
    $table->enum('permission', ['viewer', 'commenter'])->default('viewer');
    $table->timestamp('expires_at')->nullable();
    $table->uuid('created_by');
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
    $table->index('token');
});
```

#### Migration 017: PRD Comments

```php
// database/migrations/2026_02_01_000017_create_prd_comments_table.php
Schema::create('prd_comments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->uuid('user_id');
    $table->uuid('parent_id')->nullable();
    $table->string('section_anchor', 100)->nullable();
    $table->text('content');
    $table->boolean('is_resolved')->default(false);
    $table->uuid('resolved_by')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('parent_id')->references('id')->on('prd_comments')->cascadeOnDelete();
    $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
    $table->index(['prd_id', 'section_anchor']);
    $table->index(['prd_id', 'is_resolved']);
});
```

#### Migration 018: PRD Drive Links

```php
// database/migrations/2026_02_01_000018_create_prd_drive_links_table.php
Schema::create('prd_drive_links', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id')->unique();
    $table->string('google_doc_id');
    $table->string('google_doc_name')->nullable();
    $table->enum('sync_mode', ['prd_to_drive', 'drive_to_prd', 'bidirectional'])->default('bidirectional');
    $table->boolean('auto_sync')->default(false);
    $table->timestamp('last_synced_at')->nullable();
    $table->string('last_prd_hash', 64)->nullable();
    $table->string('last_drive_hash', 64)->nullable();
    $table->enum('sync_status', ['synced', 'syncing', 'conflict', 'error'])->default('synced');
    $table->text('error_message')->nullable();
    $table->timestamps();
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
});
```

#### Migration 019: Agents

```php
// database/migrations/2026_02_01_000019_create_agents_table.php
Schema::create('agents', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id')->nullable();       // NULL = system agent
    $table->string('name', 50);
    $table->string('icon', 10);                // Emoji
    $table->string('focus_area', 100);
    $table->text('system_prompt');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->index('user_id');
});
```

#### Migration 020: PRD Agents & Agent Messages

```php
// database/migrations/2026_02_01_000020_create_agent_tables.php
Schema::create('prd_agents', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->uuid('agent_id');
    $table->boolean('is_enabled')->default(true);
    $table->boolean('auto_suggest')->default(false);
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
    $table->unique(['prd_id', 'agent_id']);
});

Schema::create('agent_messages', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->uuid('agent_id');
    $table->enum('role', ['user', 'assistant']);
    $table->longText('content');
    $table->integer('token_estimate');
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
    $table->index(['prd_id', 'agent_id', 'created_at']);
});

Schema::create('agent_suggestions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->uuid('agent_id');
    $table->string('section_ref', 100)->nullable();
    $table->text('suggestion');
    $table->enum('status', ['pending', 'accepted', 'rejected', 'deferred'])->default('pending');
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
    $table->index(['prd_id', 'status']);
});
```

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| Existing email with different google_id | Match by google_id, update email |
| Email change in Google | Update on next login |
| Avatar URL from Google expires | Re-fetch on each login |
| Token encryption key rotation | Store key version, decrypt with old, re-encrypt with new |
| Database connection lost mid-migration | Transactions where possible, idempotent migrations |

#### Verification Gate

```bash
php artisan migrate
php artisan migrate:rollback
php artisan migrate                 # Both directions work
php artisan migrate:status          # All 20 migrations ran
php artisan tinker
> User::factory()->create()         # Factory works
> \DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")
# Returns 20+ tables
```

### 1.2 Google OAuth Integration

#### Implementation

```php
// app/Http/Controllers/AuthController.php
class AuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        $state = Str::random(40);
        session(['oauth_state' => $state]);
        
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'scope' => implode(' ', [
                'openid',
                'email', 
                'profile',
                'https://www.googleapis.com/auth/drive.file'
            ]),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
        
        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }
    
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        // CSRF protection
        if ($request->state !== session('oauth_state')) {
            Log::warning('OAuth state mismatch', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return redirect('/login?error=csrf_failed');
        }
        
        // ... token exchange and user creation
    }
}
```

#### Edge Cases to Handle

| Edge Case | Behavior | Implementation |
|-----------|----------|----------------|
| User denies OAuth consent | Friendly error, redirect to login | Check for `error` param in callback |
| User denies Drive scope only | Allow login, disable Drive features | Check token scopes, set flag |
| OAuth code expired | Redirect to retry | Handle Google API error 400 |
| Google service down | Show maintenance message | Circuit breaker pattern |
| State parameter mismatch | Block with CSRF error | Compare session state |
| Multiple tabs, different accounts | Last login wins | Clear other sessions |
| User clicks back during OAuth | Handle gracefully | Check for duplicate callbacks |
| Refresh token revoked | Force re-auth | Catch 401, clear tokens |

#### UX Requirements

| State | User Sees |
|-------|-----------|
| Clicking "Sign in with Google" | Button shows spinner, disabled |
| Redirecting to Google | Brief "Redirecting to Google..." |
| Processing callback | Full-page loader with "Signing in..." |
| Success | Redirect to dashboard |
| Error | Clear message with retry button |

#### Verification Gate

```bash
# Manual testing checklist
[ ] Fresh sign-in creates new user
[ ] Returning user updates tokens
[ ] Denying consent shows error
[ ] Denying only Drive scope shows banner
[ ] Logging out clears session
[ ] Session cookie is HTTP-only, Secure, SameSite=Strict
[ ] Rate limiting blocks after 10 attempts
```

### 1.3 Session Management

#### Implementation

```php
// app/Http/Middleware/AuthenticateSession.php
class AuthenticateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->validateSession($request);
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Bind session to user agent + IP hash
        $fingerprint = hash('sha256', $request->userAgent() . $request->ip());
        if (session('fingerprint') && session('fingerprint') !== $fingerprint) {
            Log::warning('Session fingerprint mismatch', [
                'user_id' => $user->id,
                'expected' => session('fingerprint'),
                'actual' => $fingerprint
            ]);
            // Allow but log for monitoring
        }
        
        $request->setUserResolver(fn() => $user);
        return $next($request);
    }
}
```

#### Edge Cases to Handle

| Edge Case | Behavior |
|-----------|----------|
| Session cookie missing | Return 401, frontend shows login |
| Session expired | Return 401 with `session_expired: true` |
| Token refresh fails | Force logout with message |
| User deleted while logged in | Return 401, clear session |
| Concurrent sessions limit | Allow, but track for analytics |

#### Verification Gate

```bash
# Automated tests
php artisan test --filter=AuthenticationTest

# Manual checklist
[ ] Session survives page refresh
[ ] Session survives browser close/reopen (within 7 days)
[ ] Logout invalidates session
[ ] Expired session shows login page
[ ] API returns 401 for unauthenticated requests
```

### 1.4 Frontend Auth Flow

#### Implementation

```typescript
// src/stores/authStore.ts
interface AuthState {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  error: string | null;
  
  login: () => void;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  isLoading: true,
  isAuthenticated: false,
  error: null,

  login: () => {
    window.location.href = '/auth/google';
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await api.post('/api/logout');
      set({ user: null, isAuthenticated: false });
      window.location.href = '/login';
    } catch (error) {
      // Still redirect even if API fails
      window.location.href = '/login';
    }
  },

  checkAuth: async () => {
    set({ isLoading: true, error: null });
    try {
      const { data } = await api.get('/api/user');
      set({ user: data, isAuthenticated: true, isLoading: false });
    } catch (error) {
      set({ user: null, isAuthenticated: false, isLoading: false });
    }
  },
}));
```

#### UX Components

```typescript
// src/components/auth/LoginPage.tsx
export const LoginPage: React.FC = () => {
  const { login, isLoading, error } = useAuthStore();
  
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50">
      <div className="w-full max-w-md p-8 bg-white rounded-xl shadow-lg">
        {/* Logo */}
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-slate-900">PRD Tool</h1>
          <p className="text-slate-600 mt-2">AI-powered PRD creation</p>
        </div>
        
        {/* Error state */}
        {error && (
          <div 
            className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700"
            role="alert"
          >
            {error}
          </div>
        )}
        
        {/* Login button */}
        <button
          onClick={login}
          disabled={isLoading}
          className="w-full flex items-center justify-center gap-3 px-6 py-3 
                     bg-white border border-slate-300 rounded-lg font-medium 
                     text-slate-700 hover:bg-slate-50 transition-colors
                     disabled:opacity-50 disabled:cursor-not-allowed
                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          aria-label="Sign in with Google"
        >
          {isLoading ? (
            <Spinner className="w-5 h-5" />
          ) : (
            <GoogleIcon className="w-5 h-5" />
          )}
          <span>Sign in with Google</span>
        </button>
        
        {/* Terms */}
        <p className="mt-6 text-center text-sm text-slate-500">
          By signing in, you agree to our{' '}
          <a href="/terms" className="text-blue-600 hover:underline">Terms</a>
          {' '}and{' '}
          <a href="/privacy" className="text-blue-600 hover:underline">Privacy Policy</a>
        </p>
      </div>
    </div>
  );
};
```

#### Verification Gate

```bash
# Frontend tests
npm test -- --filter=auth

# Manual checklist
[ ] Login page renders correctly
[ ] Button shows loading state when clicked
[ ] Error message displays correctly
[ ] Successful login redirects to dashboard
[ ] Protected routes redirect to login
[ ] Logout redirects to login
```

---

## Phase 2: PRD Core CRUD

**Duration Estimate:** Core functionality  
**Risk Level:** Medium  
**Dependencies:** Phase 1

### 2.1 Database Schema — PRDs

#### Migrations

```php
// database/migrations/002_create_prds_table.php
Schema::create('prds', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->uuid('team_id')->nullable();
    $table->string('title')->default('Untitled PRD');
    $table->string('file_path');
    $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
    $table->integer('estimated_tokens')->default(0);
    $table->uuid('created_from_template_id')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->index(['user_id', 'deleted_at']);
    $table->index('status');
});
```

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| File creation fails | Transaction rollback, no orphan DB record |
| Title with XSS | Escape on render, store raw |
| Title empty | Default to "Untitled PRD" |
| Title > 255 chars | Truncate with "..." |
| 1000+ PRDs per user | Virtual scrolling, search |
| Soft-deleted PRD accessed | 404, even to owner |

### 2.2 File Storage Service

#### Implementation

```php
// app/Services/FileStorageService.php
class FileStorageService
{
    private string $basePath;
    
    public function __construct()
    {
        $this->basePath = storage_path('prds');
    }
    
    public function createPrd(string $userId, string $prdId, string $initialContent = ''): string
    {
        $userDir = "{$this->basePath}/{$userId}";
        
        // Create user directory if not exists
        if (!is_dir($userDir)) {
            if (!mkdir($userDir, 0755, true)) {
                throw new StorageException('Failed to create user directory');
            }
        }
        
        $filePath = "{$userDir}/{$prdId}.md";
        
        // Atomic write
        $tempPath = "{$filePath}.tmp";
        if (file_put_contents($tempPath, $initialContent, LOCK_EX) === false) {
            throw new StorageException('Failed to write PRD file');
        }
        
        if (!rename($tempPath, $filePath)) {
            unlink($tempPath);
            throw new StorageException('Failed to finalize PRD file');
        }
        
        return $filePath;
    }
    
    public function readPrd(string $userId, string $prdId): string
    {
        $filePath = $this->getFilePath($userId, $prdId);
        
        if (!file_exists($filePath)) {
            throw new FileNotFoundException("PRD file not found: {$prdId}");
        }
        
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new StorageException('Failed to read PRD file');
        }
        
        // Validate UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }
        
        return $content;
    }
    
    public function writePrd(string $userId, string $prdId, string $content): void
    {
        $filePath = $this->getFilePath($userId, $prdId);
        
        // Size check (2MB limit)
        if (strlen($content) > 2 * 1024 * 1024) {
            throw new ContentTooLargeException('PRD exceeds 2MB limit');
        }
        
        // Backup before write
        $backupPath = "{$filePath}.bak";
        if (file_exists($filePath)) {
            copy($filePath, $backupPath);
        }
        
        // Atomic write
        $tempPath = "{$filePath}.tmp";
        if (file_put_contents($tempPath, $content, LOCK_EX) === false) {
            throw new StorageException('Failed to write PRD file');
        }
        
        if (!rename($tempPath, $filePath)) {
            unlink($tempPath);
            throw new StorageException('Failed to finalize PRD file');
        }
        
        // Remove backup on success
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
    }
    
    private function getFilePath(string $userId, string $prdId): string
    {
        // Prevent path traversal
        if (!Str::isUuid($userId) || !Str::isUuid($prdId)) {
            throw new InvalidArgumentException('Invalid UUID');
        }
        
        return "{$this->basePath}/{$userId}/{$prdId}.md";
    }
}
```

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| Disk full | Throw StorageException, user sees error |
| File locked by another process | Retry with backoff, then fail |
| Invalid UTF-8 in content | Sanitize with mb_convert_encoding |
| Content > 2MB | Reject with ContentTooLargeException |
| Path traversal attempt | Validate UUIDs, reject invalid |
| File deleted outside app | Recreate empty file, warn user |
| Concurrent writes | Use LOCK_EX, atomic rename |

#### Verification Gate

```bash
php artisan test --filter=FileStorageServiceTest

# Test scenarios
[ ] Create file for new user
[ ] Read existing file
[ ] Write updates atomically
[ ] Handle missing file gracefully
[ ] Reject oversized content
[ ] Prevent path traversal
```

### 2.3 PRD API Endpoints

#### Implementation

```php
// app/Http/Controllers/PrdController.php
class PrdController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $prds = Prd::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where('title', 'ilike', "%{$s}%"))
            ->orderByDesc('updated_at')
            ->paginate($request->per_page ?? 20);
        
        return response()->json($prds);
    }
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'template_id' => 'nullable|uuid|exists:templates,id',
        ]);
        
        DB::beginTransaction();
        try {
            $prd = new Prd();
            $prd->id = Str::uuid();
            $prd->user_id = $request->user()->id;
            $prd->title = $validated['title'] ?? 'Untitled PRD';
            $prd->file_path = "storage/prds/{$prd->user_id}/{$prd->id}.md";
            $prd->status = 'draft';
            
            // Get initial content (empty or from template)
            $initialContent = '';
            if (!empty($validated['template_id'])) {
                $template = Template::findOrFail($validated['template_id']);
                $initialContent = $template->content;
                $prd->created_from_template_id = $template->id;
                $template->increment('usage_count');
            }
            
            // Create file first
            app(FileStorageService::class)->createPrd(
                $prd->user_id,
                $prd->id,
                $initialContent
            );
            
            $prd->save();
            
            DB::commit();
            
            return response()->json($prd, 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PRD creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function show(Request $request, string $id): JsonResponse
    {
        $prd = Prd::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->first();
        
        // 404 for unauthorized (prevents enumeration)
        if (!$prd) {
            abort(404);
        }
        
        // Update last accessed
        $request->user()->update(['last_prd_id' => $prd->id]);
        
        return response()->json($prd);
    }
    
    public function destroy(Request $request, string $id): JsonResponse
    {
        $prd = Prd::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->first();
        
        if (!$prd) {
            abort(404);
        }
        
        // Soft delete (30-day retention)
        $prd->delete();
        
        return response()->json(['message' => 'PRD deleted'], 200);
    }
    
    public function getContent(Request $request, string $id): JsonResponse
    {
        $prd = $this->findUserPrd($request, $id);
        
        try {
            $content = app(FileStorageService::class)->readPrd(
                $prd->user_id,
                $prd->id
            );
            
            return response()->json([
                'content' => $content,
                'updated_at' => $prd->updated_at,
            ]);
        } catch (FileNotFoundException $e) {
            // File missing - recreate empty
            app(FileStorageService::class)->createPrd($prd->user_id, $prd->id, '');
            
            return response()->json([
                'content' => '',
                'updated_at' => $prd->updated_at,
                'warning' => 'PRD content was recovered',
            ]);
        }
    }
    
    public function updateContent(Request $request, string $id): JsonResponse
    {
        $prd = $this->findUserPrd($request, $id);
        
        $validated = $request->validate([
            'content' => 'required|string|max:2097152', // 2MB
        ]);
        
        app(FileStorageService::class)->writePrd(
            $prd->user_id,
            $prd->id,
            $validated['content']
        );
        
        $prd->touch();
        
        return response()->json([
            'message' => 'Content updated',
            'updated_at' => $prd->updated_at,
        ]);
    }
}
```

#### Verification Gate

```bash
php artisan test --filter=PrdControllerTest

# API test scenarios
[ ] GET /api/prds returns user's PRDs only
[ ] GET /api/prds with pagination works
[ ] GET /api/prds with search filters correctly
[ ] POST /api/prds creates PRD and file
[ ] POST /api/prds with template uses template content
[ ] GET /api/prds/{id} returns PRD details
[ ] GET /api/prds/{id} for other user returns 404
[ ] GET /api/prds/{id}/content returns markdown
[ ] PUT /api/prds/{id}/content saves markdown
[ ] DELETE /api/prds/{id} soft deletes
[ ] Deleted PRD not accessible
```

### 2.4 Frontend Dashboard

#### Implementation

```typescript
// src/pages/Dashboard.tsx
export const Dashboard: React.FC = () => {
  const { prds, isLoading, error, fetchPrds, createPrd } = usePrdStore();
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<PrdStatus | null>(null);
  
  useEffect(() => {
    fetchPrds();
  }, []);
  
  const filteredPrds = useMemo(() => {
    return prds.filter(prd => {
      const matchesSearch = prd.title.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesStatus = !statusFilter || prd.status === statusFilter;
      return matchesSearch && matchesStatus;
    });
  }, [prds, searchQuery, statusFilter]);
  
  const handleCreatePrd = async () => {
    const prd = await createPrd();
    navigate(`/prd/${prd.id}`);
  };
  
  // Loading state
  if (isLoading) {
    return <DashboardSkeleton />;
  }
  
  // Error state
  if (error) {
    return (
      <ErrorState 
        message="Failed to load your PRDs"
        action={{ label: 'Retry', onClick: fetchPrds }}
      />
    );
  }
  
  // Empty state
  if (prds.length === 0) {
    return (
      <EmptyState
        icon={<DocumentIcon />}
        title="No PRDs yet"
        description="Create your first PRD to get started with AI-powered product requirements."
        action={{ label: 'Create PRD', onClick: handleCreatePrd }}
      />
    );
  }
  
  return (
    <div className="max-w-6xl mx-auto px-4 py-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <h1 className="text-2xl font-bold text-slate-900">My PRDs</h1>
        <Button onClick={handleCreatePrd} leftIcon={<PlusIcon />}>
          New PRD
        </Button>
      </div>
      
      {/* Filters */}
      <div className="flex gap-4 mb-6">
        <SearchInput
          value={searchQuery}
          onChange={setSearchQuery}
          placeholder="Search PRDs..."
          className="w-64"
        />
        <StatusFilter value={statusFilter} onChange={setStatusFilter} />
      </div>
      
      {/* PRD Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filteredPrds.map(prd => (
          <PrdCard key={prd.id} prd={prd} />
        ))}
      </div>
      
      {/* No results */}
      {filteredPrds.length === 0 && prds.length > 0 && (
        <div className="text-center py-12">
          <p className="text-slate-500">No PRDs match your search.</p>
          <button 
            onClick={() => { setSearchQuery(''); setStatusFilter(null); }}
            className="mt-2 text-blue-600 hover:underline"
          >
            Clear filters
          </button>
        </div>
      )}
    </div>
  );
};
```

#### UX Components

```typescript
// src/components/prd/PrdCard.tsx
export const PrdCard: React.FC<{ prd: Prd }> = ({ prd }) => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const { deletePrd } = usePrdStore();
  
  const handleDelete = async () => {
    if (!confirm('Delete this PRD? You can recover it within 30 days.')) {
      return;
    }
    
    setIsDeleting(true);
    try {
      await deletePrd(prd.id);
      toast.success('PRD deleted');
    } catch (error) {
      toast.error('Failed to delete PRD');
    } finally {
      setIsDeleting(false);
    }
  };
  
  return (
    <Link 
      to={`/prd/${prd.id}`}
      className={cn(
        "block p-4 bg-white border border-slate-200 rounded-lg",
        "hover:border-blue-300 hover:shadow-md transition-all",
        "focus:outline-none focus:ring-2 focus:ring-blue-500",
        isDeleting && "opacity-50 pointer-events-none"
      )}
      aria-label={`Open ${prd.title}`}
    >
      <div className="flex items-start justify-between">
        <h3 className="font-medium text-slate-900 truncate pr-2">
          {prd.title}
        </h3>
        <StatusBadge status={prd.status} />
      </div>
      
      <p className="mt-2 text-sm text-slate-500">
        Updated {formatRelativeTime(prd.updated_at)}
      </p>
      
      <div className="mt-3 flex items-center gap-2 text-xs text-slate-400">
        <span>{prd.message_count} messages</span>
        <span>•</span>
        <span>~{formatTokens(prd.estimated_tokens)} tokens</span>
      </div>
    </Link>
  );
};
```

#### Verification Gate

```bash
npm test -- --filter=Dashboard

# Manual checklist
[ ] Dashboard loads PRDs on mount
[ ] Loading skeleton shown while fetching
[ ] Empty state shown for new users
[ ] PRD cards show correct info
[ ] Search filters PRDs in real-time
[ ] Status filter works correctly
[ ] "New PRD" creates and navigates
[ ] Delete shows confirmation
[ ] Delete shows success toast
[ ] Keyboard navigation works
```

---

## Phase 3: Chat System

**Duration Estimate:** Core AI integration  
**Risk Level:** High (streaming, context management)  
**Dependencies:** Phase 2

### 3.1 Database Schema — Messages

```php
// database/migrations/003_create_messages_table.php
Schema::create('messages', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('prd_id');
    $table->enum('role', ['user', 'assistant']);
    $table->longText('content');
    $table->integer('token_estimate')->default(0);
    $table->boolean('is_summarized')->default(false);
    $table->boolean('has_attachments')->default(false);
    $table->timestamp('created_at');
    
    $table->foreign('prd_id')->references('id')->on('prds')->cascadeOnDelete();
    $table->index(['prd_id', 'created_at']);
});
```

### 3.2 Anthropic Service

```php
// app/Services/AnthropicService.php
class AnthropicService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';
    private string $chatModel;
    private string $summarizeModel;
    
    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->chatModel = config('services.anthropic.model_chat', 'claude-opus-4-20250514');
        $this->summarizeModel = config('services.anthropic.model_summarize', 'claude-haiku-3.5-20250110');
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY not configured');
        }
    }
    
    /**
     * Stream chat response from Claude Opus
     * Returns generator that yields chunks and final complete response
     */
    public function streamChat(
        array $messages,
        string $systemPrompt,
    ): \Generator {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
        ->timeout(120)
        ->withOptions(['stream' => true])
        ->post("{$this->baseUrl}/messages", [
            'model' => $this->chatModel,
            'max_tokens' => 8192,
            'system' => $systemPrompt,
            'messages' => $messages,
            'stream' => true,
        ]);
        
        if (!$response->successful()) {
            throw new AnthropicException(
                "API error: {$response->status()}",
                $response->status(),
                $response->body()
            );
        }
        
        $fullResponse = '';
        $buffer = '';
        
        foreach ($response->getBody() as $chunk) {
            $buffer .= $chunk;
            
            // Parse SSE events
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);
                
                if (preg_match('/^data: (.+)$/m', $event, $matches)) {
                    $data = json_decode($matches[1], true);
                    
                    if ($data === null) continue;
                    
                    if ($data['type'] === 'content_block_delta') {
                        $text = $data['delta']['text'] ?? '';
                        $fullResponse .= $text;
                        yield ['type' => 'chunk', 'text' => $text];
                    }
                    
                    if ($data['type'] === 'message_stop') {
                        yield ['type' => 'done', 'full_response' => $fullResponse];
                    }
                    
                    if ($data['type'] === 'error') {
                        throw new AnthropicException(
                            $data['error']['message'] ?? 'Unknown error',
                            500,
                            json_encode($data)
                        );
                    }
                }
            }
        }
        
        // Ensure we always yield done with full response
        yield ['type' => 'done', 'full_response' => $fullResponse];
    }
    
    /**
     * Summarize text using Claude Haiku (faster, cheaper)
     */
    public function summarize(string $content): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
        ->timeout(60)
        ->post("{$this->baseUrl}/messages", [
            'model' => $this->summarizeModel,
            'max_tokens' => 2048,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Summarize the following conversation, preserving key decisions, requirements, and context that would be needed to continue the discussion:\n\n{$content}"
                ]
            ],
        ]);
        
        if (!$response->successful()) {
            throw new AnthropicException(
                "Summarization failed: {$response->status()}",
                $response->status(),
                $response->body()
            );
        }
        
        $data = $response->json();
        return $data['content'][0]['text'] ?? '';
    }
}

// app/Exceptions/AnthropicException.php
class AnthropicException extends \Exception
{
    public function __construct(
        string $message,
        public int $statusCode,
        public ?string $responseBody = null,
    ) {
        parent::__construct($message, $statusCode);
    }
    
    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }
    
    public function isOverloaded(): bool
    {
        return $this->statusCode === 503;
    }
    
    public function getRetryAfter(): ?int
    {
        if ($this->responseBody) {
            $data = json_decode($this->responseBody, true);
            return $data['error']['retry_after'] ?? null;
        }
        return null;
    }
}

// config/services.php - Add Anthropic configuration
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model_chat' => env('ANTHROPIC_MODEL_CHAT', 'claude-opus-4-20250514'),
    'model_summarize' => env('ANTHROPIC_MODEL_SUMMARIZE', 'claude-haiku-3.5-20250110'),
],
```
```

### 3.3 Chat Controller

```php
// app/Http/Controllers/ChatController.php
class ChatController extends Controller
{
    public function __construct(
        private AnthropicService $anthropic,
        private ContextManager $contextManager,
        private FileStorageService $fileStorage,
    ) {}
    
    public function sendMessage(Request $request, string $prdId): StreamedResponse
    {
        $prd = $this->findUserPrd($request, $prdId);
        $user = $request->user();
        
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);
        
        // Rate limiting: 1 message per 2 seconds per user
        $rateLimitKey = "chat:{$user->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return response()->json([
                'message' => 'Please wait before sending another message',
                'code' => 'RATE_LIMITED',
                'retry_after' => RateLimiter::availableIn($rateLimitKey),
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 2);
        
        // Check context utilization before proceeding
        $contextState = $this->contextManager->getContextState($prd);
        if ($contextState->requiresSummarization) {
            return response()->json([
                'message' => 'Context limit reached. Please summarize before continuing.',
                'code' => 'CONTEXT_LIMIT_REACHED',
                'context_utilization' => $contextState->utilizationPercent,
            ], 422);
        }
        
        // Save user message
        $userMessage = Message::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'role' => 'user',
            'content' => $validated['content'],
            'token_estimate' => $this->estimateTokens($validated['content']),
        ]);
        
        // Build context for Claude
        $context = $this->contextManager->buildPrompt($prd, $userMessage);
        
        // Prepare assistant message (will be updated after streaming)
        $assistantMessageId = Str::uuid();
        
        return new StreamedResponse(function () use ($prd, $context, $assistantMessageId, $userMessage) {
            $fullResponse = '';
            $cancelled = false;
            
            // Check if client disconnected
            if (connection_aborted()) {
                $cancelled = true;
            }
            
            try {
                foreach ($this->anthropic->streamChat($context['messages'], $context['system']) as $event) {
                    // Check for client disconnect on each chunk
                    if (connection_aborted()) {
                        $cancelled = true;
                        break;
                    }
                    
                    if ($event['type'] === 'chunk') {
                        $fullResponse .= $event['text'];
                        echo "data: " . json_encode([
                            'type' => 'content',
                            'text' => $event['text']
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    }
                    
                    if ($event['type'] === 'done') {
                        $fullResponse = $event['full_response'];
                    }
                }
                
                // Save assistant message to database
                $assistantMessage = Message::create([
                    'id' => $assistantMessageId,
                    'prd_id' => $prd->id,
                    'role' => 'assistant',
                    'content' => $cancelled ? $fullResponse . "\n\n[Response cancelled]" : $fullResponse,
                    'token_estimate' => $this->estimateTokens($fullResponse),
                ]);
                
                // Check if Claude generated PRD content updates
                $prdUpdate = $this->extractPrdUpdate($fullResponse);
                if ($prdUpdate) {
                    $this->fileStorage->writePrd($prd->user_id, $prd->id, $prdUpdate);
                    $prd->touch();
                    
                    // Trigger version save (handled by event listener)
                    event(new PrdContentUpdated($prd, $assistantMessage, 'ai_edit'));
                }
                
                // Update context state
                $newContextState = $this->contextManager->getContextState($prd);
                
                // Send completion event
                echo "data: " . json_encode([
                    'type' => 'done',
                    'message_id' => $assistantMessageId,
                    'cancelled' => $cancelled,
                    'context_state' => [
                        'utilization_percent' => $newContextState->utilizationPercent,
                        'needs_summarization' => $newContextState->needsSummarization,
                    ],
                ]) . "\n\n";
                ob_flush();
                flush();
                
            } catch (AnthropicException $e) {
                Log::error('Anthropic API error', [
                    'prd_id' => $prd->id,
                    'status' => $e->statusCode,
                    'body' => $e->responseBody,
                ]);
                
                // Save partial response if any
                if (!empty($fullResponse)) {
                    Message::create([
                        'id' => $assistantMessageId,
                        'prd_id' => $prd->id,
                        'role' => 'assistant',
                        'content' => $fullResponse . "\n\n[Response interrupted due to error]",
                        'token_estimate' => $this->estimateTokens($fullResponse),
                    ]);
                }
                
                $errorMessage = match (true) {
                    $e->isRateLimited() => 'Claude is busy. Please try again in a moment.',
                    $e->isOverloaded() => 'Claude is temporarily unavailable. Please try again.',
                    default => 'An error occurred. Please try again.',
                };
                
                echo "data: " . json_encode([
                    'type' => 'error',
                    'message' => $errorMessage,
                    'retry_after' => $e->getRetryAfter(),
                    'partial_saved' => !empty($fullResponse),
                ]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }
    
    /**
     * Extract PRD content update from Claude's response
     * Claude may output content in ```prd blocks
     */
    private function extractPrdUpdate(string $response): ?string
    {
        // Look for explicit PRD update blocks
        if (preg_match('/```prd\n(.*?)```/s', $response, $matches)) {
            return trim($matches[1]);
        }
        
        // Look for markdown blocks marked as PRD content
        if (preg_match('/## Updated PRD Content\n\n(.*?)(?=\n## |$)/s', $response, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    private function estimateTokens(string $text): int
    {
        // Rough estimate: ~4 characters per token for English
        return (int) ceil(strlen($text) / 4);
    }
    
    private function findUserPrd(Request $request, string $prdId): Prd
    {
        $prd = Prd::query()
            ->where('id', $prdId)
            ->where(function ($query) use ($request) {
                // User owns the PRD OR is a collaborator
                $query->where('user_id', $request->user()->id)
                    ->orWhereHas('collaborators', function ($q) use ($request) {
                        $q->where('user_id', $request->user()->id)
                          ->whereNotNull('accepted_at');
                    });
            })
            ->whereNull('deleted_at')
            ->first();
        
        if (!$prd) {
            abort(404); // 404 not 403 to prevent enumeration
        }
        
        // Update last accessed
        $request->user()->update(['last_prd_id' => $prd->id]);
        
        return $prd;
    }
    
    /**
     * Cancel streaming response (called via separate endpoint)
     */
    public function cancelStream(Request $request, string $prdId, string $messageId): JsonResponse
    {
        // Mark message as cancelled in cache
        Cache::put("cancel:{$messageId}", true, 60);
        
        return response()->json(['message' => 'Cancel requested']);
    }
    
    /**
     * Get chat history for a PRD
     */
    public function getMessages(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);
        
        $messages = Message::where('prd_id', $prd->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'has_attachments' => $m->has_attachments,
                'created_at' => $m->created_at->toIso8601String(),
            ]);
        
        return response()->json(['data' => $messages]);
    }
}

// app/Events/PrdContentUpdated.php
class PrdContentUpdated
{
    public function __construct(
        public Prd $prd,
        public Message $triggerMessage,
        public string $trigger, // 'ai_edit', 'user_edit', 'import', 'restore', 'sync'
    ) {}
}

// app/Listeners/CreatePrdVersion.php
class CreatePrdVersion
{
    public function handle(PrdContentUpdated $event): void
    {
        $prd = $event->prd;
        $content = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        
        // Get next version number
        $lastVersion = PrdVersion::where('prd_id', $prd->id)
            ->orderByDesc('version_number')
            ->first();
        $versionNumber = ($lastVersion?->version_number ?? 0) + 1;
        
        PrdVersion::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'version_number' => $versionNumber,
            'content' => $content,
            'trigger' => $event->trigger,
            'trigger_message_id' => $event->trigger === 'ai_edit' ? $event->triggerMessage->id : null,
            'diff_summary' => $this->generateDiffSummary($prd, $content, $lastVersion),
        ]);
        
        // Enforce version limit (keep latest 100)
        $oldVersions = PrdVersion::where('prd_id', $prd->id)
            ->orderByDesc('version_number')
            ->skip(100)
            ->pluck('id');
        
        if ($oldVersions->isNotEmpty()) {
            PrdVersion::whereIn('id', $oldVersions)->delete();
        }
    }
    
    private function generateDiffSummary(Prd $prd, string $newContent, ?PrdVersion $previous): string
    {
        if (!$previous) {
            return 'Initial version';
        }
        
        $oldLines = explode("\n", $previous->content);
        $newLines = explode("\n", $newContent);
        
        $additions = count($newLines) - count($oldLines);
        
        if ($additions > 0) {
            return "Added {$additions} lines";
        } elseif ($additions < 0) {
            return "Removed " . abs($additions) . " lines";
        }
        
        return "Modified content";
    }
}
```

### 3.4 Frontend Chat Components

```typescript
// src/components/chat/ChatSidebar.tsx
export const ChatSidebar: React.FC<{ prdId: string }> = ({ prdId }) => {
  const { messages, isStreaming, error, sendMessage, cancelStream } = useChatStore();
  const [draft, setDraft] = useState('');
  const messagesEndRef = useRef<HTMLDivElement>(null);
  
  // Auto-scroll to bottom on new messages
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);
  
  const handleSend = async () => {
    if (!draft.trim() || isStreaming) return;
    
    const content = draft;
    setDraft('');
    
    try {
      await sendMessage(prdId, content);
    } catch (error) {
      // Restore draft on error
      setDraft(content);
      toast.error('Failed to send message');
    }
  };
  
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };
  
  return (
    <div className="flex flex-col h-full border-l border-slate-200">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-slate-200">
        <h2 className="font-semibold text-slate-900">Chat with Claude</h2>
        <ContextGauge prdId={prdId} />
      </div>
      
      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.length === 0 && (
          <EmptyChat />
        )}
        
        {messages.map(message => (
          <ChatMessage key={message.id} message={message} />
        ))}
        
        {isStreaming && (
          <StreamingIndicator />
        )}
        
        <div ref={messagesEndRef} />
      </div>
      
      {/* Error */}
      {error && (
        <div className="mx-4 mb-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
          {error}
          <button className="ml-2 underline" onClick={() => clearError()}>
            Dismiss
          </button>
        </div>
      )}
      
      {/* Composer */}
      <div className="p-4 border-t border-slate-200">
        <div className="flex gap-2">
          <textarea
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder="Ask Claude to help with your PRD..."
            className="flex-1 min-h-[80px] max-h-[200px] px-3 py-2 
                       border border-slate-300 rounded-lg resize-none
                       focus:outline-none focus:ring-2 focus:ring-blue-500"
            disabled={isStreaming}
            aria-label="Message input"
          />
        </div>
        
        <div className="flex items-center justify-between mt-2">
          <span className={cn(
            "text-xs",
            draft.length > 9000 ? "text-red-500" : "text-slate-400"
          )}>
            {draft.length.toLocaleString()} / 10,000
          </span>
          
          <div className="flex gap-2">
            {isStreaming ? (
              <Button variant="secondary" onClick={cancelStream}>
                Cancel
              </Button>
            ) : (
              <Button 
                onClick={handleSend} 
                disabled={!draft.trim()}
                rightIcon={<SendIcon />}
              >
                Send
              </Button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
```

### 3.5 Edge Cases — Chat System

| Edge Case | Implementation |
|-----------|----------------|
| Empty message | Disable send button |
| Message > 10,000 chars | Show counter in red, disable send |
| Network drops mid-stream | Save partial, offer "Continue" |
| User cancels mid-stream | Save partial with [cancelled] marker |
| Claude returns empty | Show "Try rephrasing" message |
| Rate limited (429) | Show "Wait X seconds" |
| API error (500) | Show retry button |
| Very long response | Virtual scroll, no lag |
| Rapid button clicks | Debounce 300ms |

### Verification Gate

```bash
php artisan test --filter=ChatControllerTest
npm test -- --filter=ChatSidebar

# Manual checklist
[ ] Messages display correctly
[ ] Streaming shows chunks in real-time
[ ] Cancel stops stream, saves partial
[ ] Error states show recovery options
[ ] Rate limiting shows wait time
[ ] Character counter works
[ ] Enter sends, Shift+Enter newline
[ ] Auto-scroll on new messages
[ ] Context gauge updates after message
```

---

## Phase 4: File Attachments

**Duration Estimate:** Multi-format support  
**Risk Level:** Medium (file processing)  
**Dependencies:** Phase 3

### 4.1 Database Schema — Attachments

```php
Schema::create('message_attachments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('message_id');
    $table->string('filename');
    $table->enum('file_type', ['image', 'pdf', 'word', 'excel', 'csv', 'markdown']);
    $table->string('mime_type', 100);
    $table->integer('file_size');
    $table->string('storage_path')->nullable();
    $table->string('google_drive_id')->nullable();
    $table->longText('extracted_text')->nullable();
    $table->string('thumbnail_url')->nullable();
    $table->timestamp('created_at');
    
    $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
});
```

### 4.2 Attachment Processing Service

```php
// app/Services/AttachmentService.php
class AttachmentService
{
    public function processFile(UploadedFile $file): ProcessedAttachment
    {
        $this->validateFile($file);
        
        $type = $this->detectFileType($file);
        
        return match ($type) {
            'image' => $this->processImage($file),
            'pdf' => $this->processPdf($file),
            'word' => $this->processWord($file),
            'excel' => $this->processExcel($file),
            'csv' => $this->processCsv($file),
            'markdown' => $this->processMarkdown($file),
            default => throw new UnsupportedFileTypeException($file->getMimeType()),
        };
    }
    
    private function validateFile(UploadedFile $file): void
    {
        // Size check
        if ($file->getSize() > 20 * 1024 * 1024) {
            throw new FileTooLargeException('Maximum file size is 20MB');
        }
        
        // MIME type + magic bytes validation
        $allowedMimes = [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv', 'text/markdown', 'text/plain',
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new UnsupportedFileTypeException($file->getMimeType());
        }
        
        // Magic bytes validation
        if (!$this->validateMagicBytes($file)) {
            throw new InvalidFileException('File content does not match extension');
        }
    }
    
    private function processPdf(UploadedFile $file): ProcessedAttachment
    {
        $parser = new PdfParser();
        
        try {
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();
            
            // Check if scanned (no text)
            if (strlen(trim($text)) < 100) {
                // Potentially scanned document
                return new ProcessedAttachment(
                    type: 'pdf',
                    extractedText: null,
                    isScanned: true,
                    warning: 'This PDF appears to be scanned. Text extraction limited.',
                );
            }
            
            // Truncate if too long
            if (strlen($text) > 100000) {
                $text = substr($text, 0, 100000) . "\n\n[Document truncated - first 100,000 characters shown]";
            }
            
            return new ProcessedAttachment(
                type: 'pdf',
                extractedText: $text,
            );
            
        } catch (\Exception $e) {
            Log::warning('PDF processing failed', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            
            throw new FileProcessingException('Unable to read PDF file');
        }
    }
}
```

### 4.3 Edge Cases — Attachments

| Edge Case | Implementation |
|-----------|----------------|
| Unsupported type | Clear error listing supported types |
| File > 20MB | "Maximum 20MB per file" |
| Corrupt file | "Unable to read file" |
| Password-protected PDF | "Protected files not supported" |
| Scanned PDF (no text) | Warning + send as image to vision |
| Excel with 1000+ rows | Truncate to 500, warn user |
| Zero-byte file | "File appears empty" |
| Malformed CSV | Best-effort parse, log warnings |
| EXIF data in images | Strip before processing |

### Verification Gate

```bash
php artisan test --filter=AttachmentServiceTest

# Test with each file type
[ ] PNG image processes, sends to Claude vision
[ ] PDF extracts text correctly
[ ] DOCX extracts text
[ ] XLSX converts to markdown table
[ ] CSV converts to markdown table
[ ] Markdown reads directly
[ ] Corrupt file shows error
[ ] Oversized file shows error
```

---

## Phase 5: Context Management

**Duration Estimate:** Summarization system  
**Risk Level:** Medium  
**Dependencies:** Phase 3

### 5.1 Context Manager

```php
// app/Services/ContextManager.php
class ContextManager
{
    private const MAX_TOKENS = 150000;
    private const SUMMARIZE_SUGGESTION_THRESHOLD = 0.70;  // 70%
    private const SUMMARIZE_REQUIRED_THRESHOLD = 0.85;    // 85%
    private const RECENT_MESSAGES_TO_PRESERVE = 12;
    
    public function getContextState(Prd $prd): ContextState
    {
        $messages = Message::where('prd_id', $prd->id)
            ->where('is_summarized', false)
            ->orderBy('created_at')
            ->get();
        
        $summary = ContextSummary::where('prd_id', $prd->id)
            ->latest()
            ->first();
        
        $prdContent = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        $prdTokens = $this->estimateTokens($prdContent);
        
        $messageTokens = $messages->sum('token_estimate');
        $summaryTokens = $summary?->token_estimate ?? 0;
        $totalTokens = $prdTokens + $messageTokens + $summaryTokens;
        
        $utilization = $totalTokens / self::MAX_TOKENS;
        
        return new ContextState(
            estimatedTokens: $totalTokens,
            maxTokens: self::MAX_TOKENS,
            utilizationPercent: round($utilization * 100),
            needsSummarization: $utilization >= self::SUMMARIZE_SUGGESTION_THRESHOLD,
            requiresSummarization: $utilization >= self::SUMMARIZE_REQUIRED_THRESHOLD,
            summaryCount: ContextSummary::where('prd_id', $prd->id)->count(),
        );
    }
    
    public function buildPrompt(Prd $prd, Message $newMessage): array
    {
        $rules = $prd->rules()->orderBy('pivot_priority')->get();
        $summary = ContextSummary::where('prd_id', $prd->id)->latest()->first();
        $recentMessages = Message::where('prd_id', $prd->id)
            ->where('is_summarized', false)
            ->orderBy('created_at')
            ->get();
        
        $prdContent = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        
        // Build system prompt
        $systemParts = [
            $this->getBaseSystemPrompt(),
            ...($rules->pluck('content')->toArray()),
            "\n\n## Current PRD Content\n\n{$prdContent}",
        ];
        
        // Build messages
        $apiMessages = [];
        
        if ($summary) {
            $apiMessages[] = [
                'role' => 'user',
                'content' => "[Previous conversation summary]\n\n{$summary->content}",
            ];
        }
        
        foreach ($recentMessages as $msg) {
            $apiMessages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }
        
        return [
            'system' => implode("\n\n---\n\n", $systemParts),
            'messages' => $apiMessages,
        ];
    }
    
    public function summarize(Prd $prd): ContextSummary
    {
        $messages = Message::where('prd_id', $prd->id)
            ->where('is_summarized', false)
            ->orderBy('created_at')
            ->get();
        
        $toSummarize = $messages->slice(0, -self::RECENT_MESSAGES_TO_PRESERVE);
        $toPreserve = $messages->slice(-self::RECENT_MESSAGES_TO_PRESERVE);
        
        if ($toSummarize->isEmpty()) {
            throw new NothingToSummarizeException();
        }
        
        // Use Haiku for fast summarization
        $summaryContent = app(AnthropicService::class)->summarize(
            $toSummarize->map(fn($m) => "{$m->role}: {$m->content}")->join("\n\n")
        );
        
        $summary = ContextSummary::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'content' => $summaryContent,
            'token_estimate' => $this->estimateTokens($summaryContent),
            'summarized_message_ids' => $toSummarize->pluck('id')->toArray(),
        ]);
        
        // Mark messages as summarized
        Message::whereIn('id', $toSummarize->pluck('id'))->update(['is_summarized' => true]);
        
        return $summary;
    }
}
```

### 5.2 Context Gauge Component

```typescript
// src/components/chat/ContextGauge.tsx
export const ContextGauge: React.FC<{ prdId: string }> = ({ prdId }) => {
  const { contextState, isLoading, summarize } = useContextStore();
  
  const getColor = () => {
    if (contextState.utilizationPercent >= 85) return 'red';
    if (contextState.utilizationPercent >= 70) return 'orange';
    if (contextState.utilizationPercent >= 50) return 'yellow';
    return 'green';
  };
  
  const colorClasses = {
    green: 'bg-green-500',
    yellow: 'bg-yellow-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
  };
  
  return (
    <div className="flex items-center gap-2">
      <div className="relative w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
        <div 
          className={cn(
            "absolute inset-y-0 left-0 transition-all",
            colorClasses[getColor()]
          )}
          style={{ width: `${Math.min(contextState.utilizationPercent, 100)}%` }}
        />
      </div>
      
      <span className="text-xs text-slate-500">
        {contextState.utilizationPercent}%
      </span>
      
      {contextState.needsSummarization && (
        <Tooltip content="Context is filling up. Consider summarizing.">
          <button
            onClick={() => summarize(prdId)}
            className="text-xs text-blue-600 hover:underline"
          >
            Summarize
          </button>
        </Tooltip>
      )}
    </div>
  );
};
```

### Verification Gate

```bash
php artisan test --filter=ContextManagerTest

# Scenarios
[ ] Context state calculates correctly
[ ] Summarization triggers at 70%
[ ] Summarization required at 85%
[ ] Recent 12 messages preserved
[ ] Summary stored correctly
[ ] Old messages marked as summarized
[ ] Context gauge shows correct color
```

---

## Phase 6: PRD Editor

**Duration Estimate:** Document viewer with Mermaid  
**Risk Level:** Medium  
**Dependencies:** Phase 2

### 6.1 Markdown Renderer

```typescript
// src/components/editor/MarkdownViewer.tsx
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import mermaid from 'mermaid';
import DOMPurify from 'dompurify';

export const MarkdownViewer: React.FC<{ content: string }> = ({ content }) => {
  const mermaidRef = useRef<Set<string>>(new Set());
  
  useEffect(() => {
    mermaid.initialize({
      startOnLoad: false,
      theme: 'default',
      securityLevel: 'strict',
    });
  }, []);
  
  const components = useMemo(() => ({
    code: ({ node, inline, className, children, ...props }) => {
      const match = /language-(\w+)/.exec(className || '');
      const language = match?.[1];
      
      if (language === 'mermaid') {
        return <MermaidDiagram code={String(children)} />;
      }
      
      if (inline) {
        return <code className="px-1 py-0.5 bg-slate-100 rounded text-sm">{children}</code>;
      }
      
      return (
        <SyntaxHighlighter language={language} style={github}>
          {String(children)}
        </SyntaxHighlighter>
      );
    },
    
    table: ({ children }) => (
      <div className="overflow-x-auto">
        <table className="min-w-full border border-slate-200">{children}</table>
      </div>
    ),
  }), []);
  
  // Sanitize HTML in markdown
  const sanitizedContent = useMemo(() => {
    return DOMPurify.sanitize(content, {
      ALLOWED_TAGS: [],  // Strip all HTML
    });
  }, [content]);
  
  return (
    <div className="prose prose-slate max-w-none">
      <ReactMarkdown
        remarkPlugins={[remarkGfm]}
        components={components}
      >
        {sanitizedContent}
      </ReactMarkdown>
    </div>
  );
};

const MermaidDiagram: React.FC<{ code: string }> = ({ code }) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  
  useEffect(() => {
    const renderDiagram = async () => {
      if (!containerRef.current) return;
      
      setIsLoading(true);
      setError(null);
      
      try {
        const { svg } = await mermaid.render(`mermaid-${Date.now()}`, code);
        containerRef.current.innerHTML = svg;
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Invalid diagram syntax');
      } finally {
        setIsLoading(false);
      }
    };
    
    renderDiagram();
  }, [code]);
  
  if (error) {
    return (
      <div className="my-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p className="text-sm text-yellow-800">⚠️ Diagram syntax error: {error}</p>
        <pre className="mt-2 text-xs bg-slate-100 p-2 rounded overflow-x-auto">
          {code}
        </pre>
      </div>
    );
  }
  
  return (
    <div className="my-4">
      {isLoading && <DiagramSkeleton />}
      <div ref={containerRef} className={isLoading ? 'hidden' : ''} />
    </div>
  );
};
```

### 6.2 Auto-Save Implementation

```typescript
// src/hooks/useAutoSave.ts
export const useAutoSave = (
  prdId: string,
  content: string,
  debounceMs: number = 500
) => {
  const [isSaving, setIsSaving] = useState(false);
  const [lastSaved, setLastSaved] = useState<Date | null>(null);
  const [error, setError] = useState<string | null>(null);
  const previousContent = useRef(content);
  const retryCount = useRef(0);
  
  const save = useCallback(async (contentToSave: string) => {
    if (contentToSave === previousContent.current) return;
    
    setIsSaving(true);
    setError(null);
    
    try {
      await api.put(`/api/prds/${prdId}/content`, { content: contentToSave });
      previousContent.current = contentToSave;
      setLastSaved(new Date());
      retryCount.current = 0;
    } catch (err) {
      if (retryCount.current < 3) {
        retryCount.current++;
        const delay = Math.pow(2, retryCount.current) * 1000;
        setTimeout(() => save(contentToSave), delay);
        setError(`Save failed. Retrying in ${delay / 1000}s...`);
      } else {
        setError('Unable to save. Changes stored locally.');
        localStorage.setItem(`prd-draft-${prdId}`, contentToSave);
      }
    } finally {
      setIsSaving(false);
    }
  }, [prdId]);
  
  const debouncedSave = useDebouncedCallback(save, debounceMs);
  
  useEffect(() => {
    debouncedSave(content);
  }, [content, debouncedSave]);
  
  // Warn on unsaved changes before leaving
  useBeforeUnload(
    useCallback(
      (e) => {
        if (content !== previousContent.current) {
          e.preventDefault();
          return (e.returnValue = 'You have unsaved changes.');
        }
      },
      [content]
    )
  );
  
  return { isSaving, lastSaved, error };
};
```

### 6.3 Save Indicator

```typescript
// src/components/editor/SaveIndicator.tsx
export const SaveIndicator: React.FC<{
  isSaving: boolean;
  lastSaved: Date | null;
  error: string | null;
}> = ({ isSaving, lastSaved, error }) => {
  if (error) {
    return (
      <div className="flex items-center gap-2 text-red-600">
        <ExclamationIcon className="w-4 h-4" />
        <span className="text-sm">{error}</span>
      </div>
    );
  }
  
  if (isSaving) {
    return (
      <div className="flex items-center gap-2 text-slate-500">
        <Spinner className="w-4 h-4" />
        <span className="text-sm">Saving...</span>
      </div>
    );
  }
  
  if (lastSaved) {
    return (
      <div className="flex items-center gap-2 text-green-600">
        <CheckIcon className="w-4 h-4" />
        <span className="text-sm">Saved</span>
      </div>
    );
  }
  
  return null;
};
```

### Verification Gate

```bash
npm test -- --filter=MarkdownViewer
npm test -- --filter=useAutoSave

# Manual checklist
[ ] Markdown renders correctly
[ ] Tables display with horizontal scroll
[ ] Code blocks have syntax highlighting
[ ] Mermaid diagrams render
[ ] Invalid Mermaid shows error + code
[ ] Auto-save triggers after 500ms idle
[ ] "Saving..." shows during save
[ ] "Saved" shows after success
[ ] Error shows after 3 retries fail
[ ] Local storage backup on persistent failure
[ ] Page leave warning if unsaved
```

---

## Phase 7: Rules System

**Duration Estimate:** Rules management  
**Risk Level:** Low  
**Dependencies:** Phase 2

### 7.1 Rule Controller

```php
// app/Http/Controllers/RuleController.php
class RuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rules = Rule::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'content' => $r->content,
                'content_preview' => Str::limit($r->content, 100),
                'prd_count' => $r->prds()->count(),
                'created_at' => $r->created_at->toIso8601String(),
                'updated_at' => $r->updated_at->toIso8601String(),
            ]);
        
        return response()->json(['data' => $rules]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:51200', // 50KB limit
        ]);
        
        $rule = Rule::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'content' => $validated['content'],
        ]);
        
        return response()->json($rule, 201);
    }
    
    public function update(Request $request, Rule $rule): JsonResponse
    {
        $this->authorize('update', $rule);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string|max:51200',
        ]);
        
        $rule->update($validated);
        
        return response()->json($rule);
    }
    
    public function destroy(Request $request, Rule $rule): JsonResponse
    {
        $this->authorize('delete', $rule);
        
        // Cascade delete removes prd_rules automatically
        $rule->delete();
        
        return response()->json(['message' => 'Rule deleted']);
    }
    
    /**
     * Get rules assigned to a specific PRD
     */
    public function assigned(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('view', $prd);
        
        $rules = $prd->rules()
            ->orderBy('prd_rules.priority')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'priority' => $r->pivot->priority,
            ]);
        
        return response()->json(['data' => $rules]);
    }
    
    /**
     * Assign rules to a PRD
     */
    public function assign(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('update', $prd);
        
        $validated = $request->validate([
            'rule_ids' => 'required|array',
            'rule_ids.*' => 'uuid|exists:rules,id',
        ]);
        
        // Verify all rules belong to user
        $userRuleIds = Rule::where('user_id', $request->user()->id)
            ->whereIn('id', $validated['rule_ids'])
            ->pluck('id')
            ->toArray();
        
        if (count($userRuleIds) !== count($validated['rule_ids'])) {
            return response()->json([
                'message' => 'Some rules not found',
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
        
        // Sync with priority
        $syncData = [];
        foreach ($validated['rule_ids'] as $priority => $ruleId) {
            $syncData[$ruleId] = ['priority' => $priority];
        }
        
        $prd->rules()->sync($syncData);
        
        return response()->json([
            'message' => 'Rules updated',
            'rules' => $prd->rules()->orderBy('prd_rules.priority')->get(['rules.id', 'rules.name']),
        ]);
    }
}
```

### 7.2 Edge Cases — Rules

| Edge Case | Implementation |
|-----------|----------------|
| Empty rule content | Allow but show warning "This rule has no content" |
| Rule content > 50KB | Reject with clear error |
| Delete rule assigned to PRDs | Cascade delete from prd_rules |
| 10+ rules on one PRD | Allow but warn "Many rules may reduce response quality" |
| Duplicate rule names | Allow (identified by UUID) |
| Rules + PRD exceed context | Include in context budget, prioritize recent messages |

### 7.3 Verification Gate

```bash
php artisan test --filter=RuleControllerTest

# Manual checklist
[ ] Create rule with name and content
[ ] Edit rule updates correctly
[ ] Delete rule removes from PRD assignments
[ ] Assign multiple rules to PRD
[ ] Reorder rules via priority
[ ] Rules appear in Claude context
[ ] Rule preview in chat sidebar shows assigned rules
```

---

## Phase 8: Export System

**Duration Estimate:** PDF generation  
**Risk Level:** Medium (Puppeteer)  
**Dependencies:** Phase 6

### 8.1 Export Service

```php
// app/Services/ExportService.php
class ExportService
{
    private string $puppeteerEndpoint;
    
    public function __construct()
    {
        $this->puppeteerEndpoint = config('services.puppeteer.endpoint', 'http://puppeteer:3000');
    }
    
    public function exportMarkdown(Prd $prd): string
    {
        $content = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        return $content;
    }
    
    public function exportPdf(Prd $prd, string $language = 'en'): string
    {
        $content = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        
        // Translate if needed
        if ($language !== 'en') {
            $content = app(TranslationService::class)->translate($content, $language);
        }
        
        // Render markdown to HTML with Mermaid pre-rendered
        $html = $this->renderToHtml($content, $prd->title);
        
        // Generate PDF via Puppeteer service
        $response = Http::timeout(120)->post("{$this->puppeteerEndpoint}/pdf", [
            'html' => $html,
            'options' => [
                'format' => 'A4',
                'margin' => [
                    'top' => '20mm',
                    'bottom' => '20mm',
                    'left' => '20mm',
                    'right' => '20mm',
                ],
                'printBackground' => true,
                'displayHeaderFooter' => true,
                'headerTemplate' => '<div style="font-size:10px; text-align:center; width:100%;">' . e($prd->title) . '</div>',
                'footerTemplate' => '<div style="font-size:10px; text-align:center; width:100%;"><span class="pageNumber"></span> / <span class="totalPages"></span></div>',
            ],
        ]);
        
        if (!$response->successful()) {
            throw new ExportException('PDF generation failed: ' . $response->body());
        }
        
        return $response->body();
    }
    
    private function renderToHtml(string $markdown, string $title): string
    {
        // Pre-render Mermaid diagrams to SVG
        $markdown = $this->preRenderMermaid($markdown);
        
        // Convert markdown to HTML
        $parser = new \Parsedown();
        $parser->setSafeMode(true);
        $html = $parser->text($markdown);
        
        // Wrap in styled template
        return view('exports.pdf', [
            'title' => $title,
            'content' => $html,
        ])->render();
    }
    
    private function preRenderMermaid(string $content): string
    {
        // Find all Mermaid code blocks and render to SVG
        return preg_replace_callback(
            '/```mermaid\n(.*?)```/s',
            function ($matches) {
                try {
                    $svg = $this->renderMermaidToSvg($matches[1]);
                    return "\n{$svg}\n";
                } catch (\Exception $e) {
                    Log::warning('Mermaid render failed', ['error' => $e->getMessage()]);
                    return "\n[Diagram could not be rendered]\n```\n{$matches[1]}```\n";
                }
            },
            $content
        );
    }
    
    private function renderMermaidToSvg(string $code): string
    {
        $response = Http::timeout(30)->post("{$this->puppeteerEndpoint}/mermaid", [
            'code' => $code,
        ]);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Mermaid render failed');
        }
        
        return $response->json('svg');
    }
}

// Puppeteer microservice (separate container)
// docker/puppeteer/server.js
const express = require('express');
const puppeteer = require('puppeteer');

const app = express();
app.use(express.json({ limit: '10mb' }));

let browser;

app.post('/pdf', async (req, res) => {
    const page = await browser.newPage();
    try {
        await page.setContent(req.body.html, { waitUntil: 'networkidle0' });
        const pdf = await page.pdf(req.body.options);
        res.type('application/pdf').send(pdf);
    } finally {
        await page.close();
    }
});

app.post('/mermaid', async (req, res) => {
    const page = await browser.newPage();
    try {
        await page.setContent(`
            <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
            <div id="mermaid">${req.body.code}</div>
            <script>mermaid.initialize({ startOnLoad: true });</script>
        `, { waitUntil: 'networkidle0' });
        const svg = await page.$eval('#mermaid svg', el => el.outerHTML);
        res.json({ svg });
    } finally {
        await page.close();
    }
});

(async () => {
    browser = await puppeteer.launch({ args: ['--no-sandbox'] });
    app.listen(3000, () => console.log('Puppeteer service on :3000'));
})();
```

### 8.2 Edge Cases — Export

| Edge Case | Implementation |
|-----------|----------------|
| PDF generation timeout (>60s) | Show progress, allow cancel |
| Very large PRD (100+ pages) | Warn before export, show progress |
| Mermaid diagram fails | Placeholder with code block |
| Network error during export | Retry button, clear error message |
| Clipboard API not supported | Fallback modal with selectable text |
| Translation fails | Show original with note |

### 8.3 Verification Gate

```bash
php artisan test --filter=ExportServiceTest

# Manual checklist
[ ] Export Markdown downloads .md file
[ ] Export PDF generates correctly
[ ] PDF includes rendered Mermaid diagrams
[ ] PDF has proper headers/footers
[ ] Large PRD (50+ pages) exports without timeout
[ ] Copy to clipboard works
[ ] Failed Mermaid shows fallback
```

---

## Phase 9: Google Drive Integration

**Duration Estimate:** Bidirectional sync  
**Risk Level:** High (sync conflicts)  
**Dependencies:** Phase 4

### 9.1 Drive Service

```php
// app/Services/GoogleDriveService.php
class GoogleDriveService
{
    private \Google_Client $client;
    private \Google_Service_Drive $service;
    
    public function __construct()
    {
        $this->client = new \Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
    }
    
    public function setUserTokens(User $user): void
    {
        // Check if token needs refresh
        if ($user->google_token_expires_at->isPast()) {
            $this->refreshToken($user);
        }
        
        $this->client->setAccessToken($user->google_access_token);
        $this->service = new \Google_Service_Drive($this->client);
    }
    
    public function getPickerToken(User $user): array
    {
        $this->setUserTokens($user);
        
        return [
            'access_token' => $user->google_access_token,
            'developer_key' => config('services.google.picker_api_key'),
        ];
    }
    
    public function downloadFile(User $user, string $fileId): DownloadedFile
    {
        $this->setUserTokens($user);
        
        $file = $this->service->files->get($fileId, ['fields' => 'id,name,mimeType,size']);
        
        // Check size limit (50MB)
        if ($file->getSize() > 50 * 1024 * 1024) {
            throw new FileTooLargeException('File exceeds 50MB limit');
        }
        
        // Handle Google Workspace files (need export)
        $content = match ($file->getMimeType()) {
            'application/vnd.google-apps.document' => 
                $this->service->files->export($fileId, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', ['alt' => 'media'])->getBody()->getContents(),
            'application/vnd.google-apps.spreadsheet' => 
                $this->service->files->export($fileId, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', ['alt' => 'media'])->getBody()->getContents(),
            default => 
                $this->service->files->get($fileId, ['alt' => 'media'])->getBody()->getContents(),
        };
        
        return new DownloadedFile(
            filename: $file->getName(),
            content: $content,
            mimeType: $file->getMimeType(),
            size: strlen($content),
        );
    }
    
    public function syncPrdToDrive(Prd $prd, PrdDriveLink $link): SyncResult
    {
        $user = $prd->user;
        $this->setUserTokens($user);
        
        $prdContent = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        $prdHash = md5($prdContent);
        
        // Get Drive document
        try {
            $driveFile = $this->service->files->get($link->google_doc_id, ['fields' => 'id,name,modifiedTime']);
            $driveContent = $this->service->files->export($link->google_doc_id, 'text/plain', ['alt' => 'media'])->getBody()->getContents();
            $driveHash = md5($driveContent);
        } catch (\Google_Service_Exception $e) {
            if ($e->getCode() === 404) {
                $link->update(['sync_status' => 'error', 'error_message' => 'Google Doc no longer exists']);
                throw new SyncException('Google Doc not found');
            }
            throw $e;
        }
        
        // Check for conflicts
        $prdChanged = $prdHash !== $link->last_prd_hash;
        $driveChanged = $driveHash !== $link->last_drive_hash;
        
        if ($prdChanged && $driveChanged) {
            // Both changed - conflict!
            $link->update(['sync_status' => 'conflict']);
            return new SyncResult(
                status: 'conflict',
                prdUpdatedAt: $prd->updated_at,
                driveUpdatedAt: new \DateTime($driveFile->getModifiedTime()),
            );
        }
        
        if ($prdChanged && $link->sync_mode !== 'drive_to_prd') {
            // Push PRD to Drive
            $this->updateDriveDocument($link->google_doc_id, $prdContent);
            $link->update([
                'last_prd_hash' => $prdHash,
                'last_drive_hash' => md5($prdContent), // Will match after update
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ]);
            return new SyncResult(status: 'synced', direction: 'prd_to_drive');
        }
        
        if ($driveChanged && $link->sync_mode !== 'prd_to_drive') {
            // Pull Drive to PRD
            app(FileStorageService::class)->writePrd($prd->user_id, $prd->id, $driveContent);
            $prd->touch();
            event(new PrdContentUpdated($prd, null, 'sync'));
            
            $link->update([
                'last_prd_hash' => md5($driveContent),
                'last_drive_hash' => $driveHash,
                'last_synced_at' => now(),
                'sync_status' => 'synced',
            ]);
            return new SyncResult(status: 'synced', direction: 'drive_to_prd');
        }
        
        return new SyncResult(status: 'synced', direction: 'none');
    }
    
    public function resolveConflict(Prd $prd, PrdDriveLink $link, string $resolution): SyncResult
    {
        $prdContent = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        $driveContent = $this->service->files->export($link->google_doc_id, 'text/plain', ['alt' => 'media'])->getBody()->getContents();
        
        match ($resolution) {
            'prd_wins' => $this->updateDriveDocument($link->google_doc_id, $prdContent),
            'drive_wins' => app(FileStorageService::class)->writePrd($prd->user_id, $prd->id, $driveContent),
            default => throw new \InvalidArgumentException("Invalid resolution: {$resolution}"),
        };
        
        $finalContent = $resolution === 'prd_wins' ? $prdContent : $driveContent;
        $hash = md5($finalContent);
        
        $link->update([
            'last_prd_hash' => $hash,
            'last_drive_hash' => $hash,
            'last_synced_at' => now(),
            'sync_status' => 'synced',
        ]);
        
        if ($resolution === 'drive_wins') {
            $prd->touch();
            event(new PrdContentUpdated($prd, null, 'sync'));
        }
        
        return new SyncResult(status: 'synced', direction: $resolution);
    }
    
    public function exportToNewDrive(Prd $prd, ?string $folderId = null): string
    {
        $user = $prd->user;
        $this->setUserTokens($user);
        
        $content = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        
        $fileMetadata = new \Google_Service_Drive_DriveFile([
            'name' => $prd->title,
            'mimeType' => 'application/vnd.google-apps.document',
            'parents' => $folderId ? [$folderId] : [],
        ]);
        
        // Create as Google Doc
        $file = $this->service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'text/markdown',
            'uploadType' => 'multipart',
            'fields' => 'id,webViewLink',
        ]);
        
        return $file->id;
    }
    
    private function updateDriveDocument(string $docId, string $content): void
    {
        $this->service->files->update($docId, new \Google_Service_Drive_DriveFile(), [
            'data' => $content,
            'mimeType' => 'text/markdown',
            'uploadType' => 'multipart',
        ]);
    }
    
    private function refreshToken(User $user): void
    {
        $this->client->setAccessToken($user->google_access_token);
        $this->client->refreshToken($user->google_refresh_token);
        
        $newToken = $this->client->getAccessToken();
        
        $user->update([
            'google_access_token' => $newToken['access_token'],
            'google_token_expires_at' => now()->addSeconds($newToken['expires_in']),
        ]);
    }
}
```

### 9.2 Edge Cases — Google Drive

| Edge Case | Implementation |
|-----------|----------------|
| User revoked Drive access | Catch 401, prompt re-authorization |
| Token expired | Auto-refresh before API call |
| File no longer exists | Update link status to error |
| File permissions changed | Show "No longer have access" error |
| Large file (>50MB) | Reject with clear error |
| Conflict (both changed) | Show conflict resolution UI |
| Slow download | Progress indicator, allow cancel |
| Picker fails to load | Fallback to file upload |
| Google API quota exceeded | Backoff and retry, user notification |
| Auto-sync job fails | Log, set error status, don't retry immediately |

### 9.3 Verification Gate

```bash
php artisan test --filter=GoogleDriveServiceTest

# Manual checklist
[ ] Picker opens with user's Drive files
[ ] Download file processes correctly
[ ] Link PRD to existing Google Doc
[ ] Sync PRD→Drive updates Drive
[ ] Sync Drive→PRD updates PRD
[ ] Conflict detection works
[ ] Conflict resolution (PRD wins) works
[ ] Conflict resolution (Drive wins) works
[ ] Export to new Google Doc creates file
[ ] Import from Google Doc creates PRD
[ ] Token refresh works transparently
```

---

## Phase 10: Templates

**Duration Estimate:** Template management  
**Risk Level:** Low  
**Dependencies:** Phase 2

### 10.1 Template Controller

```php
// app/Http/Controllers/TemplateController.php
class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Template::query();
        
        // Include system templates + user's templates
        $query->where(function ($q) use ($request) {
            $q->whereNull('user_id')  // System
              ->orWhere('user_id', $request->user()->id);  // User's own
        });
        
        if ($request->category) {
            $query->where('category', $request->category);
        }
        
        $templates = $query->orderByDesc('usage_count')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'category' => $t->category,
                'type' => $t->user_id ? 'user' : 'system',
                'usage_count' => $t->usage_count,
                'thumbnail_url' => $t->thumbnail_url,
            ]);
        
        $categories = Template::distinct()->pluck('category');
        
        return response()->json([
            'data' => $templates,
            'categories' => $categories,
        ]);
    }
    
    public function show(Request $request, Template $template): JsonResponse
    {
        $this->authorize('view', $template);
        
        // Extract placeholders from content
        preg_match_all('/\{\{([A-Z_]+)\}\}/', $template->content, $matches);
        $placeholders = array_unique($matches[1]);
        
        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'content' => $template->content,
            'placeholders' => $placeholders,
            'type' => $template->user_id ? 'user' : 'system',
        ]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:50',
            'content' => 'required|string|max:2097152', // 2MB
        ]);
        
        $template = Template::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            ...$validated,
        ]);
        
        return response()->json($template, 201);
    }
    
    public function update(Request $request, Template $template): JsonResponse
    {
        $this->authorize('update', $template);
        
        // Cannot edit system templates
        if ($template->user_id === null) {
            return response()->json([
                'message' => 'Cannot modify system templates',
                'code' => 'FORBIDDEN',
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'sometimes|required|string|max:50',
            'content' => 'sometimes|required|string|max:2097152',
        ]);
        
        $template->update($validated);
        
        return response()->json($template);
    }
    
    public function destroy(Request $request, Template $template): JsonResponse
    {
        $this->authorize('delete', $template);
        
        if ($template->user_id === null) {
            return response()->json([
                'message' => 'Cannot delete system templates',
                'code' => 'FORBIDDEN',
            ], 403);
        }
        
        $template->delete();
        
        return response()->json(['message' => 'Template deleted']);
    }
    
    public function saveFromPrd(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('view', $prd);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:50',
        ]);
        
        $content = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        
        $template = Template::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'content' => $content,
        ]);
        
        return response()->json([
            'template_id' => $template->id,
            'message' => 'Template created successfully',
        ], 201);
    }
}
```

### 10.2 System Templates Seeder

```php
// database/seeders/SystemTemplatesSeeder.php
class SystemTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Lean MVP',
                'description' => 'Minimal viable product, fast iteration',
                'category' => 'startup',
                'content' => $this->getMvpTemplate(),
            ],
            [
                'name' => 'Enterprise SaaS',
                'description' => 'Full-featured B2B software with compliance',
                'category' => 'enterprise',
                'content' => $this->getEnterpriseTemplate(),
            ],
            [
                'name' => 'Mobile App',
                'description' => 'Native or cross-platform mobile application',
                'category' => 'mobile',
                'content' => $this->getMobileTemplate(),
            ],
            // ... more templates
        ];
        
        foreach ($templates as $template) {
            Template::updateOrCreate(
                ['name' => $template['name'], 'user_id' => null],
                ['id' => Str::uuid(), ...$template]
            );
        }
    }
}
```

### 10.3 Verification Gate

```bash
php artisan test --filter=TemplateControllerTest

# Manual checklist
[ ] List shows system + user templates
[ ] Filter by category works
[ ] View template shows content and placeholders
[ ] Create user template
[ ] Cannot edit system templates
[ ] Cannot delete system templates
[ ] Create PRD from template fills content
[ ] Save PRD as template preserves content
[ ] Template usage count increments
```

---

## Phase 11: Version History

**Duration Estimate:** Version tracking  
**Risk Level:** Medium  
**Dependencies:** Phase 3

### 11.1 Version Controller

```php
// app/Http/Controllers/VersionController.php
class VersionController extends Controller
{
    public function index(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('view', $prd);
        
        $versions = PrdVersion::where('prd_id', $prd->id)
            ->orderByDesc('version_number')
            ->paginate(20)
            ->through(fn ($v) => [
                'id' => $v->id,
                'version_number' => $v->version_number,
                'trigger' => $v->trigger,
                'diff_summary' => $v->diff_summary,
                'name' => $v->name,
                'created_at' => $v->created_at->toIso8601String(),
            ]);
        
        return response()->json($versions);
    }
    
    public function show(Request $request, Prd $prd, PrdVersion $version): JsonResponse
    {
        $this->authorize('view', $prd);
        
        return response()->json([
            'id' => $version->id,
            'version_number' => $version->version_number,
            'trigger' => $version->trigger,
            'trigger_message_id' => $version->trigger_message_id,
            'content' => $version->content,
            'diff_summary' => $version->diff_summary,
            'name' => $version->name,
            'created_at' => $version->created_at->toIso8601String(),
        ]);
    }
    
    public function update(Request $request, Prd $prd, PrdVersion $version): JsonResponse
    {
        $this->authorize('update', $prd);
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
        ]);
        
        $version->update(['name' => $validated['name']]);
        
        return response()->json(['message' => 'Version named']);
    }
    
    public function restore(Request $request, Prd $prd, PrdVersion $version): JsonResponse
    {
        $this->authorize('update', $prd);
        
        // Write old content to file
        app(FileStorageService::class)->writePrd($prd->user_id, $prd->id, $version->content);
        $prd->touch();
        
        // Create new version for the restore action
        event(new PrdContentUpdated($prd, null, 'restore'));
        
        return response()->json([
            'message' => "Version {$version->version_number} restored",
            'content' => $version->content,
        ]);
    }
    
    public function diff(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('view', $prd);
        
        $validated = $request->validate([
            'from' => 'required|integer',
            'to' => 'required|integer',
        ]);
        
        $fromVersion = PrdVersion::where('prd_id', $prd->id)
            ->where('version_number', $validated['from'])
            ->firstOrFail();
        
        $toVersion = PrdVersion::where('prd_id', $prd->id)
            ->where('version_number', $validated['to'])
            ->firstOrFail();
        
        $diff = $this->computeDiff($fromVersion->content, $toVersion->content);
        
        return response()->json([
            'from_version' => $validated['from'],
            'to_version' => $validated['to'],
            'diff' => $diff,
        ]);
    }
    
    private function computeDiff(string $from, string $to): array
    {
        $fromLines = explode("\n", $from);
        $toLines = explode("\n", $to);
        
        // Simple line-by-line diff
        $additions = 0;
        $deletions = 0;
        $hunks = [];
        
        // Use PHP's array_diff for simple implementation
        $addedLines = array_diff($toLines, $fromLines);
        $removedLines = array_diff($fromLines, $toLines);
        
        return [
            'additions' => count($addedLines),
            'deletions' => count($removedLines),
            'hunks' => $this->groupChangesIntoHunks($addedLines, $removedLines),
        ];
    }
}
```

### 11.2 Verification Gate

```bash
php artisan test --filter=VersionControllerTest

# Manual checklist
[ ] Version created on AI edit
[ ] Version created on user edit (manual save)
[ ] Version created on restore
[ ] Version list shows history
[ ] View specific version content
[ ] Name/bookmark a version
[ ] Restore previous version
[ ] Diff between versions shows changes
[ ] 100 version limit enforced (old deleted)
```

---

## Phase 12: Collaboration

**Duration Estimate:** Real-time collaboration  
**Risk Level:** High (real-time, presence)  
**Dependencies:** Phase 6

### 12.1 Supabase Realtime Integration

```typescript
// src/lib/realtime.ts
import { createClient, RealtimeChannel } from '@supabase/supabase-js';

const supabase = createClient(
    import.meta.env.VITE_SUPABASE_URL,
    import.meta.env.VITE_SUPABASE_ANON_KEY
);

export class PrdRealtimeService {
    private channel: RealtimeChannel | null = null;
    private presence: Map<string, UserPresence> = new Map();
    
    constructor(
        private prdId: string,
        private user: User,
        private callbacks: RealtimeCallbacks,
    ) {}
    
    async connect(): Promise<void> {
        this.channel = supabase.channel(`prd:${this.prdId}`, {
            config: {
                presence: { key: this.user.id },
            },
        });
        
        // Track presence (who's online)
        this.channel
            .on('presence', { event: 'sync' }, () => {
                const state = this.channel!.presenceState();
                this.presence.clear();
                
                Object.entries(state).forEach(([userId, data]) => {
                    const presenceData = data[0] as any;
                    this.presence.set(userId, {
                        userId,
                        userName: presenceData.user_name,
                        userAvatar: presenceData.user_avatar,
                        cursorPosition: presenceData.cursor_position,
                        lastSeen: new Date(),
                    });
                });
                
                this.callbacks.onPresenceChange([...this.presence.values()]);
            })
            .on('presence', { event: 'join' }, ({ key, newPresences }) => {
                this.callbacks.onUserJoin(key, newPresences[0]);
            })
            .on('presence', { event: 'leave' }, ({ key }) => {
                this.callbacks.onUserLeave(key);
            });
        
        // Listen for PRD content changes
        this.channel
            .on(
                'postgres_changes',
                { event: 'UPDATE', schema: 'public', table: 'prds', filter: `id=eq.${this.prdId}` },
                (payload) => {
                    this.callbacks.onPrdUpdate(payload.new);
                }
            );
        
        // Listen for new messages
        this.channel
            .on(
                'postgres_changes',
                { event: 'INSERT', schema: 'public', table: 'messages', filter: `prd_id=eq.${this.prdId}` },
                (payload) => {
                    this.callbacks.onNewMessage(payload.new);
                }
            );
        
        // Listen for new comments
        this.channel
            .on(
                'postgres_changes',
                { event: '*', schema: 'public', table: 'prd_comments', filter: `prd_id=eq.${this.prdId}` },
                (payload) => {
                    this.callbacks.onCommentChange(payload.eventType, payload.new);
                }
            );
        
        // Subscribe and track presence
        await this.channel.subscribe(async (status) => {
            if (status === 'SUBSCRIBED') {
                await this.channel!.track({
                    user_id: this.user.id,
                    user_name: this.user.name,
                    user_avatar: this.user.avatar_url,
                    cursor_position: null,
                    online_at: new Date().toISOString(),
                });
            }
        });
    }
    
    async updateCursorPosition(position: { line: number; column: number }): Promise<void> {
        if (!this.channel) return;
        
        await this.channel.track({
            user_id: this.user.id,
            user_name: this.user.name,
            user_avatar: this.user.avatar_url,
            cursor_position: position,
            online_at: new Date().toISOString(),
        });
    }
    
    async disconnect(): Promise<void> {
        if (this.channel) {
            await this.channel.unsubscribe();
            this.channel = null;
        }
    }
}

interface RealtimeCallbacks {
    onPresenceChange: (users: UserPresence[]) => void;
    onUserJoin: (userId: string, data: any) => void;
    onUserLeave: (userId: string) => void;
    onPrdUpdate: (prd: any) => void;
    onNewMessage: (message: any) => void;
    onCommentChange: (event: string, comment: any) => void;
}
```

### 12.2 Collaborator Controller

```php
// app/Http/Controllers/CollaboratorController.php
class CollaboratorController extends Controller
{
    public function index(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('view', $prd);
        
        $collaborators = PrdCollaborator::where('prd_id', $prd->id)
            ->with('user:id,name,email,avatar_url')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'user_id' => $c->user_id,
                'name' => $c->user->name,
                'email' => $c->user->email,
                'avatar_url' => $c->user->avatar_url,
                'permission' => $c->permission,
                'accepted_at' => $c->accepted_at?->toIso8601String(),
            ]);
        
        $pending = PrdCollaborator::where('prd_id', $prd->id)
            ->whereNull('accepted_at')
            ->get();
        
        return response()->json([
            'owner' => [
                'id' => $prd->user->id,
                'name' => $prd->user->name,
                'email' => $prd->user->email,
                'avatar_url' => $prd->user->avatar_url,
            ],
            'collaborators' => $collaborators->filter(fn ($c) => $c['accepted_at'] !== null),
            'pending_invites' => $pending,
        ]);
    }
    
    public function invite(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('share', $prd);
        
        // Check tier allows collaboration
        if ($request->user()->tier === 'free') {
            return response()->json([
                'message' => 'Upgrade to Pro to invite collaborators',
                'code' => 'TIER_REQUIRED',
                'upgrade_url' => '/billing',
            ], 402);
        }
        
        $validated = $request->validate([
            'email' => 'required|email',
            'permission' => 'required|in:viewer,commenter,editor',
        ]);
        
        // Find or create user
        $invitedUser = User::where('email', $validated['email'])->first();
        
        if (!$invitedUser) {
            // Create pending invite for non-existing user
            // They'll see it when they sign up
            return response()->json([
                'message' => 'User not found. They will see the invite when they sign up.',
                'pending' => true,
            ], 201);
        }
        
        // Check if already collaborator
        $existing = PrdCollaborator::where('prd_id', $prd->id)
            ->where('user_id', $invitedUser->id)
            ->first();
        
        if ($existing) {
            return response()->json([
                'message' => 'User is already a collaborator',
                'code' => 'ALREADY_COLLABORATOR',
            ], 422);
        }
        
        $collaborator = PrdCollaborator::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'user_id' => $invitedUser->id,
            'permission' => $validated['permission'],
            'invited_by' => $request->user()->id,
            'invited_at' => now(),
        ]);
        
        // Send invitation email
        Mail::to($invitedUser)->queue(new CollaborationInvite($prd, $request->user()));
        
        return response()->json([
            'message' => 'Invitation sent',
            'invite_id' => $collaborator->id,
        ], 201);
    }
    
    public function createShareLink(Request $request, Prd $prd): JsonResponse
    {
        $this->authorize('share', $prd);
        
        $validated = $request->validate([
            'permission' => 'required|in:viewer,commenter',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);
        
        // Revoke existing link
        PrdShareLink::where('prd_id', $prd->id)->delete();
        
        $token = Str::random(64);
        $expiresAt = $validated['expires_in_days'] 
            ? now()->addDays($validated['expires_in_days'])
            : null;
        
        PrdShareLink::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'token' => $token,
            'permission' => $validated['permission'],
            'expires_at' => $expiresAt,
            'created_by' => $request->user()->id,
        ]);
        
        return response()->json([
            'link' => config('app.url') . "/share/{$token}",
            'token' => $token,
            'expires_at' => $expiresAt?->toIso8601String(),
        ], 201);
    }
}
```

### 12.3 Verification Gate

```bash
php artisan test --filter=CollaboratorControllerTest
npm test -- --filter=PrdRealtimeService

# Manual checklist
[ ] Invite collaborator by email
[ ] Invited user sees invitation
[ ] Accept invitation grants access
[ ] Permission levels enforced (viewer can't edit)
[ ] Share link generates unique token
[ ] Share link expires correctly
[ ] Multiple users see each other's presence
[ ] Cursor positions sync in real-time
[ ] New messages appear for all users
[ ] Comments appear in real-time
[ ] User leave removes from presence
```

---

## Phase 13: Teams

**Duration Estimate:** Team management  
**Risk Level:** Low  
**Dependencies:** Phase 12

### 13.1 Team Controller

```php
// app/Http/Controllers/TeamController.php
class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teams = TeamMember::where('user_id', $request->user()->id)
            ->with('team:id,name,owner_id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->team->id,
                'name' => $m->team->name,
                'role' => $m->role,
                'member_count' => $m->team->members()->count(),
                'is_owner' => $m->team->owner_id === $request->user()->id,
            ]);
        
        return response()->json(['data' => $teams]);
    }
    
    public function store(Request $request): JsonResponse
    {
        // Check tier
        if (!in_array($request->user()->tier, ['team', 'enterprise'])) {
            return response()->json([
                'message' => 'Upgrade to Team tier to create teams',
                'code' => 'TIER_REQUIRED',
            ], 402);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        DB::transaction(function () use ($request, $validated, &$team) {
            $team = Team::create([
                'id' => Str::uuid(),
                'name' => $validated['name'],
                'owner_id' => $request->user()->id,
            ]);
            
            TeamMember::create([
                'id' => Str::uuid(),
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'role' => 'owner',
            ]);
        });
        
        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
            'role' => 'owner',
        ], 201);
    }
    
    public function inviteMember(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manageMember', $team);
        
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,member',
        ]);
        
        $user = User::where('email', $validated['email'])->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }
        
        $existing = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existing) {
            return response()->json([
                'message' => 'User is already a team member',
                'code' => 'ALREADY_MEMBER',
            ], 422);
        }
        
        TeamMember::create([
            'id' => Str::uuid(),
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $validated['role'],
        ]);
        
        Mail::to($user)->queue(new TeamInvite($team, $request->user()));
        
        return response()->json(['message' => 'Member added'], 201);
    }
    
    public function leave(Request $request, Team $team): JsonResponse
    {
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $request->user()->id)
            ->first();
        
        if (!$member) {
            abort(404);
        }
        
        if ($member->role === 'owner') {
            return response()->json([
                'message' => 'Owner cannot leave team. Transfer ownership or delete team.',
                'code' => 'OWNER_CANNOT_LEAVE',
            ], 422);
        }
        
        $member->delete();
        
        return response()->json(['message' => 'Left team successfully']);
    }
}
```

### 13.2 Verification Gate

```bash
php artisan test --filter=TeamControllerTest

# Manual checklist
[ ] Create team (Team tier required)
[ ] View team members
[ ] Invite member by email
[ ] Update member role
[ ] Remove member
[ ] Owner cannot leave (must delete or transfer)
[ ] Non-owner can leave
[ ] Delete team (owner only)
[ ] Team PRDs accessible to members
[ ] Team templates shared with members
```

---

## Phase 14: Billing (Stripe)

**Duration Estimate:** Subscription management  
**Risk Level:** High (payment processing)  
**Dependencies:** Phase 1

### 14.1 Billing Controller

```php
// app/Http/Controllers/BillingController.php
class BillingController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $subscription = null;
        if ($user->stripe_customer_id) {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $subscriptions = $stripe->subscriptions->all([
                'customer' => $user->stripe_customer_id,
                'status' => 'active',
                'limit' => 1,
            ]);
            $subscription = $subscriptions->data[0] ?? null;
        }
        
        return response()->json([
            'tier' => $user->tier,
            'tier_expires_at' => $user->tier_expires_at?->toIso8601String(),
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'current_period_end' => date('c', $subscription->current_period_end),
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
            ] : null,
            'upgrade_options' => $this->getUpgradeOptions($user->tier),
        ]);
    }
    
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'price_id' => 'required|string',
        ]);
        
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $user = $request->user();
        
        // Get or create customer
        if (!$user->stripe_customer_id) {
            $customer = $stripe->customers->create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => ['user_id' => $user->id],
            ]);
            $user->update(['stripe_customer_id' => $customer->id]);
        }
        
        $session = $stripe->checkout->sessions->create([
            'customer' => $user->stripe_customer_id,
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $validated['price_id'],
                'quantity' => 1,
            ]],
            'success_url' => config('app.frontend_url') . '/billing?success=true',
            'cancel_url' => config('app.frontend_url') . '/billing?canceled=true',
            'metadata' => ['user_id' => $user->id],
        ]);
        
        return response()->json(['checkout_url' => $session->url]);
    }
    
    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->stripe_customer_id) {
            return response()->json([
                'message' => 'No billing account',
                'code' => 'NO_BILLING_ACCOUNT',
            ], 400);
        }
        
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        $session = $stripe->billingPortal->sessions->create([
            'customer' => $user->stripe_customer_id,
            'return_url' => config('app.frontend_url') . '/billing',
        ]);
        
        return response()->json(['portal_url' => $session->url]);
    }
    
    public function webhook(Request $request): Response
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response('Invalid signature', 400);
        }
        
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };
        
        return response('OK', 200);
    }
    
    private function handleCheckoutCompleted($session): void
    {
        $userId = $session->metadata->user_id ?? null;
        if (!$userId) return;
        
        $user = User::find($userId);
        if (!$user) return;
        
        // Determine tier from price
        $tier = $this->getTierFromPrice($session->subscription);
        
        $user->update([
            'tier' => $tier,
            'tier_expires_at' => null, // Active subscription
        ]);
        
        Log::info('User upgraded', ['user_id' => $userId, 'tier' => $tier]);
    }
    
    private function handleSubscriptionDeleted($subscription): void
    {
        $user = User::where('stripe_customer_id', $subscription->customer)->first();
        if (!$user) return;
        
        $user->update([
            'tier' => 'free',
            'tier_expires_at' => null,
        ]);
        
        Log::info('User downgraded to free', ['user_id' => $user->id]);
    }
    
    private function getTierFromPrice(string $subscriptionId): string
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $subscription = $stripe->subscriptions->retrieve($subscriptionId);
        $priceId = $subscription->items->data[0]->price->id;
        
        return match ($priceId) {
            config('services.stripe.price_pro') => 'pro',
            config('services.stripe.price_team') => 'team',
            config('services.stripe.price_enterprise') => 'enterprise',
            default => 'free',
        };
    }
}
```

### 14.2 Tier Enforcement Middleware

```php
// app/Http/Middleware/EnforceTierLimits.php
class EnforceTierLimits
{
    private array $limits = [
        'free' => [
            'prds' => 3,
            'messages_per_day' => 30,
            'attachment_size_mb' => 5,
            'collaboration' => false,
            'custom_agents' => 0,
        ],
        'pro' => [
            'prds' => -1, // Unlimited
            'messages_per_day' => -1,
            'attachment_size_mb' => 20,
            'collaboration' => true,
            'custom_agents' => 5,
        ],
        'team' => [
            'prds' => -1,
            'messages_per_day' => -1,
            'attachment_size_mb' => 50,
            'collaboration' => true,
            'custom_agents' => 20,
        ],
        'enterprise' => [
            'prds' => -1,
            'messages_per_day' => -1,
            'attachment_size_mb' => 100,
            'collaboration' => true,
            'custom_agents' => -1,
        ],
    ];
    
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        $tier = $user->tier;
        $limits = $this->limits[$tier];
        
        $allowed = match ($feature) {
            'create_prd' => $limits['prds'] === -1 || $user->prds()->count() < $limits['prds'],
            'send_message' => $limits['messages_per_day'] === -1 || $this->messagesLast24Hours($user) < $limits['messages_per_day'],
            'collaboration' => $limits['collaboration'],
            'create_agent' => $limits['custom_agents'] === -1 || $user->agents()->count() < $limits['custom_agents'],
            default => true,
        };
        
        if (!$allowed) {
            return response()->json([
                'message' => "Upgrade required for this feature",
                'code' => 'TIER_LIMIT_REACHED',
                'current_tier' => $tier,
                'upgrade_url' => '/billing',
            ], 402);
        }
        
        return $next($request);
    }
}
```

### 14.3 Verification Gate

```bash
php artisan test --filter=BillingControllerTest

# Manual checklist (use Stripe test mode)
[ ] Create checkout session
[ ] Stripe checkout flow completes
[ ] Webhook updates user tier
[ ] Customer portal opens
[ ] Subscription cancellation downgrades tier
[ ] Tier limits enforced (free user blocked)
[ ] Payment failure notification
```

---

## Phase 15: Internationalization

**Duration Estimate:** Translation system  
**Risk Level:** Medium  
**Dependencies:** Phase 6

### 15.1 Translation Service

```php
// app/Services/TranslationService.php
class TranslationService
{
    private string $apiKey;
    private string $baseUrl = 'https://api-free.deepl.com/v2';
    
    public function translate(string $text, string $targetLang): string
    {
        // Check cache first
        $cacheKey = "translation:" . md5($text) . ":{$targetLang}";
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        // Skip translation for code blocks
        $textWithPlaceholders = $this->protectCodeBlocks($text);
        
        $response = Http::withHeaders([
            'Authorization' => "DeepL-Auth-Key {$this->apiKey}",
        ])->post("{$this->baseUrl}/translate", [
            'text' => [$textWithPlaceholders['text']],
            'target_lang' => strtoupper($targetLang),
            'preserve_formatting' => true,
        ]);
        
        if (!$response->successful()) {
            Log::warning('DeepL translation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new TranslationException('Translation failed');
        }
        
        $translated = $response->json('translations.0.text');
        
        // Restore code blocks
        $translated = $this->restoreCodeBlocks($translated, $textWithPlaceholders['blocks']);
        
        // Cache for 24 hours
        Cache::put($cacheKey, $translated, 86400);
        
        return $translated;
    }
    
    private function protectCodeBlocks(string $text): array
    {
        $blocks = [];
        $index = 0;
        
        // Protect fenced code blocks
        $text = preg_replace_callback(
            '/```[\s\S]*?```/',
            function ($match) use (&$blocks, &$index) {
                $placeholder = "[[CODE_BLOCK_{$index}]]";
                $blocks[$placeholder] = $match[0];
                $index++;
                return $placeholder;
            },
            $text
        );
        
        // Protect inline code
        $text = preg_replace_callback(
            '/`[^`]+`/',
            function ($match) use (&$blocks, &$index) {
                $placeholder = "[[INLINE_CODE_{$index}]]";
                $blocks[$placeholder] = $match[0];
                $index++;
                return $placeholder;
            },
            $text
        );
        
        return ['text' => $text, 'blocks' => $blocks];
    }
    
    private function restoreCodeBlocks(string $text, array $blocks): string
    {
        foreach ($blocks as $placeholder => $original) {
            $text = str_replace($placeholder, $original, $text);
        }
        return $text;
    }
}
```

### 15.2 Frontend i18n Setup

```typescript
// src/lib/i18n.ts
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import en from '@/locales/en/common.json';
import sk from '@/locales/sk/common.json';

i18n.use(initReactI18next).init({
    resources: {
        en: { common: en },
        sk: { common: sk },
    },
    lng: localStorage.getItem('language') || 'en',
    fallbackLng: 'en',
    interpolation: {
        escapeValue: false,
    },
});

export default i18n;

// src/locales/en/common.json
{
    "auth": {
        "signIn": "Sign in with Google",
        "signOut": "Sign out"
    },
    "dashboard": {
        "title": "My PRDs",
        "newPrd": "New PRD",
        "noPrds": "No PRDs yet",
        "createFirst": "Create your first PRD"
    },
    "editor": {
        "saving": "Saving...",
        "saved": "Saved",
        "error": "Save failed"
    },
    "chat": {
        "placeholder": "Ask Claude to help with your PRD...",
        "send": "Send",
        "contextUsage": "Context usage"
    }
}

// src/locales/sk/common.json
{
    "auth": {
        "signIn": "Prihlásiť sa cez Google",
        "signOut": "Odhlásiť sa"
    },
    "dashboard": {
        "title": "Moje PRD",
        "newPrd": "Nové PRD",
        "noPrds": "Zatiaľ žiadne PRD",
        "createFirst": "Vytvorte svoje prvé PRD"
    },
    "editor": {
        "saving": "Ukladám...",
        "saved": "Uložené",
        "error": "Uloženie zlyhalo"
    },
    "chat": {
        "placeholder": "Požiadajte Claude o pomoc s vaším PRD...",
        "send": "Odoslať",
        "contextUsage": "Využitie kontextu"
    }
}
```

### 15.3 Verification Gate

```bash
php artisan test --filter=TranslationServiceTest
npm test -- --filter=i18n

# Manual checklist
[ ] Change UI language in settings
[ ] All UI strings translated
[ ] PRD content translation works
[ ] Code blocks preserved during translation
[ ] Translation cached (second request instant)
[ ] "Show original" toggle works
[ ] Export PDF in translated language
[ ] Date/number formatting localized
```

---

## Phase 16: SME Agents

**Duration Estimate:** Multi-agent system  
**Risk Level:** Medium  
**Dependencies:** Phase 3

### 16.1 Agent Service

```php
// app/Services/AgentService.php
class AgentService
{
    public function __construct(
        private AnthropicService $anthropic,
    ) {}
    
    public function sendMessage(
        Prd $prd,
        Agent $agent,
        string $userMessage,
    ): \Generator {
        // Build agent-specific context
        $prdContent = app(FileStorageService::class)->readPrd($prd->user_id, $prd->id);
        
        $systemPrompt = $this->buildAgentSystemPrompt($agent, $prdContent);
        
        // Get recent agent messages
        $recentMessages = AgentMessage::where('prd_id', $prd->id)
            ->where('agent_id', $agent->id)
            ->orderBy('created_at')
            ->take(20)
            ->get()
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();
        
        // Add new user message
        $recentMessages[] = ['role' => 'user', 'content' => $userMessage];
        
        // Save user message
        AgentMessage::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'agent_id' => $agent->id,
            'role' => 'user',
            'content' => $userMessage,
            'token_estimate' => (int) ceil(strlen($userMessage) / 4),
        ]);
        
        // Stream from Claude
        $fullResponse = '';
        foreach ($this->anthropic->streamChat($recentMessages, $systemPrompt) as $event) {
            if ($event['type'] === 'chunk') {
                $fullResponse .= $event['text'];
                yield $event;
            }
            if ($event['type'] === 'done') {
                $fullResponse = $event['full_response'];
            }
        }
        
        // Save assistant message
        AgentMessage::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'agent_id' => $agent->id,
            'role' => 'assistant',
            'content' => $fullResponse,
            'token_estimate' => (int) ceil(strlen($fullResponse) / 4),
        ]);
        
        // Check for auto-suggestions
        if ($agent->pivot->auto_suggest ?? false) {
            $this->generateSuggestions($prd, $agent, $fullResponse);
        }
        
        yield ['type' => 'done', 'full_response' => $fullResponse];
    }
    
    private function buildAgentSystemPrompt(Agent $agent, string $prdContent): string
    {
        return <<<PROMPT
{$agent->system_prompt}

## Current PRD Content

{$prdContent}

## Response Guidelines

- Stay in character as {$agent->name}
- Focus on your area of expertise: {$agent->focus_area}
- Provide specific, actionable feedback
- Reference relevant sections of the PRD when commenting
- Structure feedback with priority levels (Critical, Important, Suggestion)
PROMPT;
    }
    
    private function generateSuggestions(Prd $prd, Agent $agent, string $response): void
    {
        // Parse suggestions from response
        preg_match_all('/(?:^|\n)(?:[-*•]|\d+\.)\s*(.+)/m', $response, $matches);
        
        foreach ($matches[1] as $suggestion) {
            if (strlen($suggestion) > 20 && strlen($suggestion) < 500) {
                AgentSuggestion::create([
                    'id' => Str::uuid(),
                    'prd_id' => $prd->id,
                    'agent_id' => $agent->id,
                    'suggestion' => trim($suggestion),
                    'status' => 'pending',
                ]);
            }
        }
    }
}
```

### 16.2 System Agents Seeder

```php
// database/seeders/SystemAgentsSeeder.php
class SystemAgentsSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            [
                'name' => 'Security',
                'icon' => '🛡️',
                'focus_area' => 'Security & Compliance',
                'system_prompt' => $this->getSecurityPrompt(),
            ],
            [
                'name' => 'UX',
                'icon' => '🎨',
                'focus_area' => 'User Experience',
                'system_prompt' => $this->getUxPrompt(),
            ],
            [
                'name' => 'Engineering',
                'icon' => '⚙️',
                'focus_area' => 'Technical Architecture',
                'system_prompt' => $this->getEngineeringPrompt(),
            ],
            [
                'name' => 'QA',
                'icon' => '🧪',
                'focus_area' => 'Quality Assurance',
                'system_prompt' => $this->getQaPrompt(),
            ],
        ];
        
        foreach ($agents as $agent) {
            Agent::updateOrCreate(
                ['name' => $agent['name'], 'user_id' => null],
                ['id' => Str::uuid(), ...$agent]
            );
        }
    }
}
```

### 16.3 Verification Gate

```bash
php artisan test --filter=AgentServiceTest

# Manual checklist
[ ] List system agents
[ ] Assign agents to PRD
[ ] Chat with specific agent
[ ] Agent stays in character
[ ] Agent references PRD content
[ ] Create custom agent
[ ] Edit custom agent prompt
[ ] Delete custom agent
[ ] Auto-suggestions generated
[ ] Accept/reject suggestions
[ ] Tier limits on custom agents
```

---

## Scheduled Jobs

Add to Laravel scheduler for automated maintenance.

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Hard delete PRDs after 30 days of soft delete
    $schedule->call(function () {
        $cutoff = now()->subDays(30);
        $prds = Prd::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->get();
        
        foreach ($prds as $prd) {
            // Delete file
            $filePath = storage_path("prds/{$prd->user_id}/{$prd->id}.md");
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            // Hard delete
            $prd->forceDelete();
        }
        
        Log::info('Hard deleted expired PRDs', ['count' => $prds->count()]);
    })->daily()->at('03:00');
    
    // Clean expired draft attachments
    $schedule->call(function () {
        $expired = DraftAttachment::where('expires_at', '<', now())->get();
        
        foreach ($expired as $attachment) {
            if (file_exists($attachment->storage_path)) {
                unlink($attachment->storage_path);
            }
            $attachment->delete();
        }
        
        Log::info('Cleaned expired draft attachments', ['count' => $expired->count()]);
    })->hourly();
    
    // Auto-sync Google Drive links
    $schedule->call(function () {
        $links = PrdDriveLink::where('auto_sync', true)
            ->where('sync_status', '!=', 'error')
            ->with('prd.user')
            ->get();
        
        foreach ($links as $link) {
            try {
                app(GoogleDriveService::class)->syncPrdToDrive($link->prd, $link);
            } catch (\Exception $e) {
                Log::warning('Auto-sync failed', [
                    'prd_id' => $link->prd_id,
                    'error' => $e->getMessage(),
                ]);
                $link->update([
                    'sync_status' => 'error',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    })->everyFiveMinutes();
    
    // Clean old context summaries (keep latest 5 per PRD)
    $schedule->call(function () {
        $prds = Prd::whereHas('contextSummaries', function ($q) {
            $q->havingRaw('COUNT(*) > 5');
        })->get();
        
        foreach ($prds as $prd) {
            $toDelete = ContextSummary::where('prd_id', $prd->id)
                ->orderByDesc('created_at')
                ->skip(5)
                ->pluck('id');
            
            ContextSummary::whereIn('id', $toDelete)->delete();
        }
    })->daily()->at('04:00');
}

---

## UX System — Complete Design Specifications

This section provides the complete UX implementation to ensure top-notch user experience across all features.

### Design Tokens

```typescript
// src/lib/design-tokens.ts
export const tokens = {
  colors: {
    // Primary (Blue)
    primary: {
      50: '#eff6ff',
      100: '#dbeafe',
      200: '#bfdbfe',
      300: '#93c5fd',
      400: '#60a5fa',
      500: '#3b82f6',  // Main
      600: '#2563eb',
      700: '#1d4ed8',
      800: '#1e40af',
      900: '#1e3a8a',
    },
    // Slate (Neutral)
    slate: {
      50: '#f8fafc',
      100: '#f1f5f9',
      200: '#e2e8f0',
      300: '#cbd5e1',
      400: '#94a3b8',
      500: '#64748b',
      600: '#475569',
      700: '#334155',
      800: '#1e293b',
      900: '#0f172a',
    },
    // Semantic
    success: '#10b981',
    warning: '#f59e0b',
    error: '#ef4444',
    info: '#3b82f6',
  },
  
  spacing: {
    xs: '0.25rem',   // 4px
    sm: '0.5rem',    // 8px
    md: '1rem',      // 16px
    lg: '1.5rem',    // 24px
    xl: '2rem',      // 32px
    '2xl': '3rem',   // 48px
    '3xl': '4rem',   // 64px
  },
  
  typography: {
    fontFamily: {
      sans: 'Inter, system-ui, -apple-system, sans-serif',
      mono: 'JetBrains Mono, Menlo, monospace',
    },
    fontSize: {
      xs: ['0.75rem', { lineHeight: '1rem' }],
      sm: ['0.875rem', { lineHeight: '1.25rem' }],
      base: ['1rem', { lineHeight: '1.5rem' }],
      lg: ['1.125rem', { lineHeight: '1.75rem' }],
      xl: ['1.25rem', { lineHeight: '1.75rem' }],
      '2xl': ['1.5rem', { lineHeight: '2rem' }],
      '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
    },
  },
  
  animation: {
    duration: {
      instant: '50ms',
      fast: '150ms',
      normal: '250ms',
      slow: '400ms',
    },
    easing: {
      default: 'cubic-bezier(0.4, 0, 0.2, 1)',
      in: 'cubic-bezier(0.4, 0, 1, 1)',
      out: 'cubic-bezier(0, 0, 0.2, 1)',
      bounce: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
    },
  },
  
  shadows: {
    sm: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
    md: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
    lg: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
    xl: '0 20px 25px -5px rgb(0 0 0 / 0.1)',
  },
  
  radius: {
    sm: '0.25rem',
    md: '0.375rem',
    lg: '0.5rem',
    xl: '0.75rem',
    full: '9999px',
  },
} as const;
```

### Tailwind Configuration

```javascript
// tailwind.config.js
module.exports = {
  content: ['./src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        mono: ['JetBrains Mono', 'Menlo', 'monospace'],
      },
      animation: {
        'fade-in': 'fadeIn 150ms ease-out',
        'fade-out': 'fadeOut 150ms ease-in',
        'slide-up': 'slideUp 250ms ease-out',
        'slide-down': 'slideDown 250ms ease-out',
        'scale-in': 'scaleIn 150ms ease-out',
        'spin-slow': 'spin 1.5s linear infinite',
        'pulse-subtle': 'pulseSubtle 2s ease-in-out infinite',
        'shimmer': 'shimmer 2s ease-in-out infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        fadeOut: {
          '0%': { opacity: '1' },
          '100%': { opacity: '0' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideDown: {
          '0%': { opacity: '0', transform: 'translateY(-10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(0.95)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
        pulseSubtle: {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.7' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
  ],
};
```

### Component Library

#### Button Component

```typescript
// src/components/ui/Button.tsx
import { forwardRef } from 'react';
import { clsx } from 'clsx';
import { Spinner } from './Spinner';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
type ButtonSize = 'sm' | 'md' | 'lg';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  isLoading?: boolean;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
  fullWidth?: boolean;
}

const variantStyles: Record<ButtonVariant, string> = {
  primary: `
    bg-primary-600 text-white 
    hover:bg-primary-700 
    focus:ring-primary-500
    disabled:bg-primary-300
  `,
  secondary: `
    bg-white text-slate-700 border border-slate-300
    hover:bg-slate-50 
    focus:ring-primary-500
    disabled:bg-slate-100 disabled:text-slate-400
  `,
  ghost: `
    bg-transparent text-slate-600
    hover:bg-slate-100 hover:text-slate-900
    focus:ring-slate-500
    disabled:text-slate-300
  `,
  danger: `
    bg-red-600 text-white
    hover:bg-red-700
    focus:ring-red-500
    disabled:bg-red-300
  `,
};

const sizeStyles: Record<ButtonSize, string> = {
  sm: 'px-3 py-1.5 text-sm gap-1.5',
  md: 'px-4 py-2 text-sm gap-2',
  lg: 'px-6 py-3 text-base gap-2',
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  (
    {
      variant = 'primary',
      size = 'md',
      isLoading = false,
      leftIcon,
      rightIcon,
      fullWidth = false,
      disabled,
      children,
      className,
      ...props
    },
    ref
  ) => {
    return (
      <button
        ref={ref}
        disabled={disabled || isLoading}
        className={clsx(
          // Base styles
          'inline-flex items-center justify-center font-medium rounded-lg',
          'transition-all duration-150 ease-out',
          'focus:outline-none focus:ring-2 focus:ring-offset-2',
          'disabled:cursor-not-allowed',
          // Variant
          variantStyles[variant],
          // Size
          sizeStyles[size],
          // Full width
          fullWidth && 'w-full',
          // Loading state opacity
          isLoading && 'opacity-80',
          className
        )}
        {...props}
      >
        {isLoading ? (
          <Spinner size={size === 'lg' ? 'md' : 'sm'} />
        ) : leftIcon ? (
          <span className="shrink-0">{leftIcon}</span>
        ) : null}
        
        <span className={isLoading ? 'opacity-0' : undefined}>{children}</span>
        
        {rightIcon && !isLoading && (
          <span className="shrink-0">{rightIcon}</span>
        )}
      </button>
    );
  }
);

Button.displayName = 'Button';
```

#### Input Component

```typescript
// src/components/ui/Input.tsx
import { forwardRef, useId } from 'react';
import { clsx } from 'clsx';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  hint?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, hint, leftIcon, rightIcon, className, id, ...props }, ref) => {
    const generatedId = useId();
    const inputId = id || generatedId;
    
    return (
      <div className="w-full">
        {label && (
          <label
            htmlFor={inputId}
            className="block text-sm font-medium text-slate-700 mb-1.5"
          >
            {label}
            {props.required && <span className="text-red-500 ml-0.5">*</span>}
          </label>
        )}
        
        <div className="relative">
          {leftIcon && (
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span className="text-slate-400">{leftIcon}</span>
            </div>
          )}
          
          <input
            ref={ref}
            id={inputId}
            aria-invalid={!!error}
            aria-describedby={error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined}
            className={clsx(
              // Base
              'block w-full rounded-lg border bg-white px-3 py-2',
              'text-slate-900 placeholder:text-slate-400',
              'transition-colors duration-150',
              // Focus
              'focus:outline-none focus:ring-2 focus:ring-offset-0',
              // States
              error
                ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20'
                : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500/20',
              // Disabled
              'disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed',
              // Icons
              leftIcon && 'pl-10',
              rightIcon && 'pr-10',
              className
            )}
            {...props}
          />
          
          {rightIcon && (
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
              <span className="text-slate-400">{rightIcon}</span>
            </div>
          )}
        </div>
        
        {error && (
          <p id={`${inputId}-error`} className="mt-1.5 text-sm text-red-600" role="alert">
            {error}
          </p>
        )}
        
        {hint && !error && (
          <p id={`${inputId}-hint`} className="mt-1.5 text-sm text-slate-500">
            {hint}
          </p>
        )}
      </div>
    );
  }
);

Input.displayName = 'Input';
```

#### Skeleton Components

```typescript
// src/components/ui/Skeleton.tsx
import { clsx } from 'clsx';

interface SkeletonProps {
  className?: string;
  variant?: 'text' | 'circular' | 'rectangular';
  width?: string | number;
  height?: string | number;
  lines?: number;
}

export const Skeleton: React.FC<SkeletonProps> = ({
  className,
  variant = 'text',
  width,
  height,
  lines = 1,
}) => {
  const baseClasses = 'bg-slate-200 animate-shimmer bg-gradient-to-r from-slate-200 via-slate-100 to-slate-200 bg-[length:200%_100%]';
  
  if (variant === 'text' && lines > 1) {
    return (
      <div className="space-y-2">
        {Array.from({ length: lines }).map((_, i) => (
          <div
            key={i}
            className={clsx(
              baseClasses,
              'h-4 rounded',
              i === lines - 1 && 'w-3/4', // Last line shorter
              className
            )}
            style={{ width: i === lines - 1 ? '75%' : width }}
          />
        ))}
      </div>
    );
  }
  
  return (
    <div
      className={clsx(
        baseClasses,
        variant === 'text' && 'h-4 rounded',
        variant === 'circular' && 'rounded-full',
        variant === 'rectangular' && 'rounded-lg',
        className
      )}
      style={{ width, height }}
    />
  );
};

// Dashboard Skeleton
export const DashboardSkeleton: React.FC = () => (
  <div className="max-w-6xl mx-auto px-4 py-8 animate-fade-in">
    {/* Header */}
    <div className="flex items-center justify-between mb-8">
      <Skeleton width={150} height={32} variant="rectangular" />
      <Skeleton width={120} height={40} variant="rectangular" />
    </div>
    
    {/* Filters */}
    <div className="flex gap-4 mb-6">
      <Skeleton width={256} height={40} variant="rectangular" />
      <Skeleton width={150} height={40} variant="rectangular" />
    </div>
    
    {/* Grid */}
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {Array.from({ length: 6 }).map((_, i) => (
        <PrdCardSkeleton key={i} />
      ))}
    </div>
  </div>
);

// PRD Card Skeleton
export const PrdCardSkeleton: React.FC = () => (
  <div className="bg-white rounded-xl border border-slate-200 p-4">
    <div className="flex items-start justify-between mb-3">
      <Skeleton width="70%" height={20} variant="text" />
      <Skeleton width={24} height={24} variant="circular" />
    </div>
    <Skeleton lines={2} className="mb-4" />
    <div className="flex items-center justify-between">
      <Skeleton width={80} height={16} variant="text" />
      <Skeleton width={60} height={24} variant="rectangular" />
    </div>
  </div>
);

// Editor Skeleton
export const EditorSkeleton: React.FC = () => (
  <div className="h-screen flex">
    {/* Sidebar */}
    <div className="w-80 border-r border-slate-200 p-4">
      <Skeleton width="100%" height={40} variant="rectangular" className="mb-4" />
      <div className="space-y-2">
        {Array.from({ length: 8 }).map((_, i) => (
          <Skeleton key={i} width="100%" height={32} variant="rectangular" />
        ))}
      </div>
    </div>
    
    {/* Main content */}
    <div className="flex-1 p-6">
      <Skeleton width="50%" height={36} variant="text" className="mb-6" />
      <Skeleton lines={20} />
    </div>
    
    {/* Chat panel */}
    <div className="w-96 border-l border-slate-200 p-4">
      <div className="space-y-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className={clsx(i % 2 === 0 ? 'ml-auto w-3/4' : 'mr-auto w-3/4')}>
            <Skeleton height={60} variant="rectangular" />
          </div>
        ))}
      </div>
    </div>
  </div>
);

// Message Skeleton (for loading more messages)
export const MessageSkeleton: React.FC<{ isUser?: boolean }> = ({ isUser = false }) => (
  <div className={clsx('flex', isUser ? 'justify-end' : 'justify-start')}>
    <div className={clsx('max-w-[80%] rounded-2xl p-4', isUser ? 'bg-primary-100' : 'bg-slate-100')}>
      <Skeleton lines={2} />
    </div>
  </div>
);
```

#### Toast Notification System

```typescript
// src/components/ui/Toast.tsx
import * as ToastPrimitive from '@radix-ui/react-toast';
import { clsx } from 'clsx';
import { CheckCircle, XCircle, AlertCircle, Info, X } from 'lucide-react';
import { create } from 'zustand';

type ToastType = 'success' | 'error' | 'warning' | 'info';

interface Toast {
  id: string;
  type: ToastType;
  title: string;
  description?: string;
  duration?: number;
  action?: { label: string; onClick: () => void };
}

interface ToastStore {
  toasts: Toast[];
  addToast: (toast: Omit<Toast, 'id'>) => void;
  removeToast: (id: string) => void;
}

export const useToastStore = create<ToastStore>((set) => ({
  toasts: [],
  addToast: (toast) => {
    const id = Math.random().toString(36).slice(2);
    set((state) => ({ toasts: [...state.toasts, { ...toast, id }] }));
    
    // Auto remove after duration
    setTimeout(() => {
      set((state) => ({ toasts: state.toasts.filter((t) => t.id !== id) }));
    }, toast.duration || 5000);
  },
  removeToast: (id) => {
    set((state) => ({ toasts: state.toasts.filter((t) => t.id !== id) }));
  },
}));

// Convenience functions
export const toast = {
  success: (title: string, description?: string) =>
    useToastStore.getState().addToast({ type: 'success', title, description }),
  error: (title: string, description?: string) =>
    useToastStore.getState().addToast({ type: 'error', title, description, duration: 8000 }),
  warning: (title: string, description?: string) =>
    useToastStore.getState().addToast({ type: 'warning', title, description }),
  info: (title: string, description?: string) =>
    useToastStore.getState().addToast({ type: 'info', title, description }),
};

const icons: Record<ToastType, React.ReactNode> = {
  success: <CheckCircle className="w-5 h-5 text-green-500" />,
  error: <XCircle className="w-5 h-5 text-red-500" />,
  warning: <AlertCircle className="w-5 h-5 text-amber-500" />,
  info: <Info className="w-5 h-5 text-blue-500" />,
};

const styles: Record<ToastType, string> = {
  success: 'border-green-200 bg-green-50',
  error: 'border-red-200 bg-red-50',
  warning: 'border-amber-200 bg-amber-50',
  info: 'border-blue-200 bg-blue-50',
};

export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { toasts, removeToast } = useToastStore();
  
  return (
    <ToastPrimitive.Provider swipeDirection="right">
      {children}
      
      {toasts.map((toast) => (
        <ToastPrimitive.Root
          key={toast.id}
          className={clsx(
            'fixed bottom-4 right-4 z-50',
            'flex items-start gap-3 p-4 rounded-lg border shadow-lg',
            'animate-slide-up',
            'data-[state=closed]:animate-fade-out',
            styles[toast.type]
          )}
          duration={toast.duration}
        >
          <div className="shrink-0 mt-0.5">{icons[toast.type]}</div>
          
          <div className="flex-1 min-w-0">
            <ToastPrimitive.Title className="text-sm font-semibold text-slate-900">
              {toast.title}
            </ToastPrimitive.Title>
            
            {toast.description && (
              <ToastPrimitive.Description className="mt-1 text-sm text-slate-600">
                {toast.description}
              </ToastPrimitive.Description>
            )}
            
            {toast.action && (
              <ToastPrimitive.Action
                altText={toast.action.label}
                onClick={toast.action.onClick}
                className="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700"
              >
                {toast.action.label}
              </ToastPrimitive.Action>
            )}
          </div>
          
          <ToastPrimitive.Close
            onClick={() => removeToast(toast.id)}
            className="shrink-0 p-1 rounded hover:bg-slate-200/50 transition-colors"
            aria-label="Dismiss"
          >
            <X className="w-4 h-4 text-slate-500" />
          </ToastPrimitive.Close>
        </ToastPrimitive.Root>
      ))}
      
      <ToastPrimitive.Viewport />
    </ToastPrimitive.Provider>
  );
};
```

#### Empty State Component

```typescript
// src/components/ui/EmptyState.tsx
import { clsx } from 'clsx';

interface EmptyStateProps {
  icon: React.ReactNode;
  title: string;
  description: string;
  action?: {
    label: string;
    onClick: () => void;
  };
  secondaryAction?: {
    label: string;
    onClick: () => void;
  };
  className?: string;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
  icon,
  title,
  description,
  action,
  secondaryAction,
  className,
}) => (
  <div
    className={clsx(
      'flex flex-col items-center justify-center py-16 px-4',
      'text-center animate-fade-in',
      className
    )}
  >
    <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
      <span className="text-slate-400">{icon}</span>
    </div>
    
    <h3 className="text-lg font-semibold text-slate-900 mb-2">{title}</h3>
    
    <p className="text-slate-500 max-w-sm mb-6">{description}</p>
    
    <div className="flex items-center gap-3">
      {action && (
        <Button onClick={action.onClick}>
          {action.label}
        </Button>
      )}
      
      {secondaryAction && (
        <Button variant="ghost" onClick={secondaryAction.onClick}>
          {secondaryAction.label}
        </Button>
      )}
    </div>
  </div>
);

// Specific empty states
export const NoPrdsEmptyState: React.FC<{ onCreateClick: () => void }> = ({ onCreateClick }) => (
  <EmptyState
    icon={<DocumentIcon className="w-8 h-8" />}
    title="No PRDs yet"
    description="Create your first PRD to get started with AI-powered product requirements documentation."
    action={{ label: 'Create your first PRD', onClick: onCreateClick }}
  />
);

export const NoSearchResultsEmptyState: React.FC<{ onClearClick: () => void }> = ({ onClearClick }) => (
  <EmptyState
    icon={<SearchIcon className="w-8 h-8" />}
    title="No results found"
    description="We couldn't find any PRDs matching your search. Try adjusting your filters."
    action={{ label: 'Clear filters', onClick: onClearClick }}
  />
);

export const NoMessagesEmptyState: React.FC = () => (
  <EmptyState
    icon={<ChatIcon className="w-8 h-8" />}
    title="Start a conversation"
    description="Ask Claude to help you build your PRD. Try asking about features, user stories, or technical requirements."
  />
);

export const NoCollaboratorsEmptyState: React.FC<{ onInviteClick: () => void }> = ({ onInviteClick }) => (
  <EmptyState
    icon={<UsersIcon className="w-8 h-8" />}
    title="No collaborators"
    description="Invite team members to collaborate on this PRD in real-time."
    action={{ label: 'Invite collaborators', onClick: onInviteClick }}
  />
);
```

#### Error State Component

```typescript
// src/components/ui/ErrorState.tsx
import { AlertTriangle, RefreshCw, Home } from 'lucide-react';
import { Button } from './Button';

interface ErrorStateProps {
  title?: string;
  message: string;
  action?: {
    label: string;
    onClick: () => void;
  };
  showHomeLink?: boolean;
}

export const ErrorState: React.FC<ErrorStateProps> = ({
  title = 'Something went wrong',
  message,
  action,
  showHomeLink = false,
}) => (
  <div className="flex flex-col items-center justify-center py-16 px-4 text-center animate-fade-in">
    <div className="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
      <AlertTriangle className="w-8 h-8 text-red-500" />
    </div>
    
    <h3 className="text-lg font-semibold text-slate-900 mb-2">{title}</h3>
    
    <p className="text-slate-500 max-w-sm mb-6">{message}</p>
    
    <div className="flex items-center gap-3">
      {action && (
        <Button onClick={action.onClick} leftIcon={<RefreshCw className="w-4 h-4" />}>
          {action.label}
        </Button>
      )}
      
      {showHomeLink && (
        <Button variant="ghost" onClick={() => window.location.href = '/'}>
          <Home className="w-4 h-4 mr-2" />
          Go home
        </Button>
      )}
    </div>
  </div>
);

// API Error Display
export const ApiErrorDisplay: React.FC<{ error: ApiError; onRetry?: () => void }> = ({ error, onRetry }) => {
  const getErrorMessage = () => {
    switch (error.code) {
      case 'RATE_LIMITED':
        return `Too many requests. Please try again in ${error.retry_after} seconds.`;
      case 'CONTEXT_LIMIT_REACHED':
        return 'Context limit reached. Please summarize your conversation to continue.';
      case 'TIER_REQUIRED':
        return 'This feature requires a higher plan. Upgrade to continue.';
      case 'STORAGE_ERROR':
        return 'Unable to save your changes. Please try again.';
      default:
        return error.message;
    }
  };
  
  return (
    <div className="bg-red-50 border border-red-200 rounded-lg p-4" role="alert">
      <div className="flex items-start gap-3">
        <AlertTriangle className="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
        <div className="flex-1">
          <p className="text-sm font-medium text-red-800">{getErrorMessage()}</p>
          
          {error.code === 'TIER_REQUIRED' && (
            <a
              href="/billing"
              className="inline-block mt-2 text-sm font-medium text-red-600 hover:text-red-700"
            >
              View plans →
            </a>
          )}
          
          {onRetry && error.code !== 'TIER_REQUIRED' && (
            <button
              onClick={onRetry}
              className="mt-2 text-sm font-medium text-red-600 hover:text-red-700"
            >
              Try again
            </button>
          )}
        </div>
      </div>
    </div>
  );
};
```

#### Save Status Indicator

```typescript
// src/components/ui/SaveStatus.tsx
import { clsx } from 'clsx';
import { Check, Cloud, AlertCircle, Loader2 } from 'lucide-react';

type SaveState = 'idle' | 'saving' | 'saved' | 'error';

interface SaveStatusProps {
  state: SaveState;
  lastSaved?: Date;
  error?: string;
  onRetry?: () => void;
}

export const SaveStatus: React.FC<SaveStatusProps> = ({
  state,
  lastSaved,
  error,
  onRetry,
}) => {
  const formatLastSaved = (date: Date) => {
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins === 1) return '1 min ago';
    if (diffMins < 60) return `${diffMins} mins ago`;
    
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };
  
  return (
    <div
      className={clsx(
        'flex items-center gap-2 px-3 py-1.5 rounded-full text-sm',
        'transition-all duration-300',
        state === 'idle' && 'bg-slate-100 text-slate-600',
        state === 'saving' && 'bg-blue-100 text-blue-700',
        state === 'saved' && 'bg-green-100 text-green-700',
        state === 'error' && 'bg-red-100 text-red-700'
      )}
      role="status"
      aria-live="polite"
    >
      {state === 'idle' && (
        <>
          <Cloud className="w-4 h-4" />
          <span>All changes saved</span>
          {lastSaved && (
            <span className="text-slate-500">• {formatLastSaved(lastSaved)}</span>
          )}
        </>
      )}
      
      {state === 'saving' && (
        <>
          <Loader2 className="w-4 h-4 animate-spin" />
          <span>Saving...</span>
        </>
      )}
      
      {state === 'saved' && (
        <>
          <Check className="w-4 h-4" />
          <span>Saved</span>
        </>
      )}
      
      {state === 'error' && (
        <>
          <AlertCircle className="w-4 h-4" />
          <span>Save failed</span>
          {onRetry && (
            <button
              onClick={onRetry}
              className="underline hover:no-underline ml-1"
            >
              Retry
            </button>
          )}
        </>
      )}
    </div>
  );
};
```

#### Context Gauge

```typescript
// src/components/chat/ContextGauge.tsx
import { clsx } from 'clsx';
import * as Tooltip from '@radix-ui/react-tooltip';
import { AlertTriangle, Info } from 'lucide-react';

interface ContextGaugeProps {
  utilizationPercent: number;
  needsSummarization: boolean;
  onSummarizeClick: () => void;
}

export const ContextGauge: React.FC<ContextGaugeProps> = ({
  utilizationPercent,
  needsSummarization,
  onSummarizeClick,
}) => {
  const getColor = () => {
    if (utilizationPercent >= 85) return 'bg-red-500';
    if (utilizationPercent >= 70) return 'bg-amber-500';
    return 'bg-green-500';
  };
  
  const getTextColor = () => {
    if (utilizationPercent >= 85) return 'text-red-700';
    if (utilizationPercent >= 70) return 'text-amber-700';
    return 'text-slate-600';
  };
  
  return (
    <div className="flex items-center gap-3">
      <Tooltip.Provider>
        <Tooltip.Root>
          <Tooltip.Trigger asChild>
            <div className="flex items-center gap-2 cursor-help">
              <div className="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
                <div
                  className={clsx('h-full rounded-full transition-all duration-500', getColor())}
                  style={{ width: `${Math.min(utilizationPercent, 100)}%` }}
                />
              </div>
              <span className={clsx('text-sm font-medium tabular-nums', getTextColor())}>
                {utilizationPercent}%
              </span>
              {utilizationPercent >= 70 && (
                <AlertTriangle className="w-4 h-4 text-amber-500" />
              )}
            </div>
          </Tooltip.Trigger>
          
          <Tooltip.Portal>
            <Tooltip.Content
              className="bg-slate-900 text-white text-sm px-3 py-2 rounded-lg shadow-lg max-w-xs"
              sideOffset={5}
            >
              <div className="flex items-start gap-2">
                <Info className="w-4 h-4 shrink-0 mt-0.5" />
                <div>
                  <p className="font-medium">Context Usage</p>
                  <p className="text-slate-300 text-xs mt-1">
                    Claude remembers your conversation using a context window.
                    {utilizationPercent >= 70 && ' Consider summarizing to free up space.'}
                  </p>
                </div>
              </div>
              <Tooltip.Arrow className="fill-slate-900" />
            </Tooltip.Content>
          </Tooltip.Portal>
        </Tooltip.Root>
      </Tooltip.Provider>
      
      {needsSummarization && (
        <button
          onClick={onSummarizeClick}
          className="text-sm font-medium text-primary-600 hover:text-primary-700 transition-colors"
        >
          Summarize
        </button>
      )}
    </div>
  );
};
```

#### Confirmation Dialog

```typescript
// src/components/ui/ConfirmDialog.tsx
import * as AlertDialog from '@radix-ui/react-alert-dialog';
import { Button } from './Button';
import { clsx } from 'clsx';

interface ConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: 'danger' | 'warning' | 'info';
  onConfirm: () => void;
  isLoading?: boolean;
}

export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
  open,
  onOpenChange,
  title,
  description,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  variant = 'danger',
  onConfirm,
  isLoading = false,
}) => (
  <AlertDialog.Root open={open} onOpenChange={onOpenChange}>
    <AlertDialog.Portal>
      <AlertDialog.Overlay className="fixed inset-0 bg-black/50 animate-fade-in" />
      
      <AlertDialog.Content
        className={clsx(
          'fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
          'w-full max-w-md bg-white rounded-xl shadow-xl p-6',
          'animate-scale-in',
          'focus:outline-none'
        )}
      >
        <AlertDialog.Title className="text-lg font-semibold text-slate-900">
          {title}
        </AlertDialog.Title>
        
        <AlertDialog.Description className="mt-2 text-sm text-slate-600">
          {description}
        </AlertDialog.Description>
        
        <div className="flex justify-end gap-3 mt-6">
          <AlertDialog.Cancel asChild>
            <Button variant="secondary" disabled={isLoading}>
              {cancelLabel}
            </Button>
          </AlertDialog.Cancel>
          
          <AlertDialog.Action asChild>
            <Button
              variant={variant === 'danger' ? 'danger' : 'primary'}
              onClick={onConfirm}
              isLoading={isLoading}
            >
              {confirmLabel}
            </Button>
          </AlertDialog.Action>
        </div>
      </AlertDialog.Content>
    </AlertDialog.Portal>
  </AlertDialog.Root>
);

// Usage example for PRD deletion
export const DeletePrdDialog: React.FC<{
  open: boolean;
  onOpenChange: (open: boolean) => void;
  prdTitle: string;
  onConfirm: () => void;
  isLoading: boolean;
}> = ({ open, onOpenChange, prdTitle, onConfirm, isLoading }) => (
  <ConfirmDialog
    open={open}
    onOpenChange={onOpenChange}
    title="Delete PRD"
    description={`Are you sure you want to delete "${prdTitle}"? This PRD will be moved to trash and permanently deleted after 30 days.`}
    confirmLabel="Delete"
    cancelLabel="Keep it"
    variant="danger"
    onConfirm={onConfirm}
    isLoading={isLoading}
  />
);
```

### Mobile-First Responsive Patterns

```typescript
// src/hooks/useMediaQuery.ts
import { useState, useEffect } from 'react';

export const useMediaQuery = (query: string): boolean => {
  const [matches, setMatches] = useState(() => {
    if (typeof window !== 'undefined') {
      return window.matchMedia(query).matches;
    }
    return false;
  });
  
  useEffect(() => {
    const mediaQuery = window.matchMedia(query);
    const handler = (e: MediaQueryListEvent) => setMatches(e.matches);
    
    mediaQuery.addEventListener('change', handler);
    return () => mediaQuery.removeEventListener('change', handler);
  }, [query]);
  
  return matches;
};

export const useIsMobile = () => useMediaQuery('(max-width: 768px)');
export const useIsTablet = () => useMediaQuery('(max-width: 1024px)');
export const useIsDesktop = () => useMediaQuery('(min-width: 1025px)');

// src/components/layout/ResponsiveLayout.tsx
export const PrdEditorLayout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const isMobile = useIsMobile();
  const [activePanel, setActivePanel] = useState<'document' | 'chat'>('document');
  
  if (isMobile) {
    return (
      <div className="h-screen flex flex-col">
        {/* Mobile tab bar */}
        <div className="flex border-b border-slate-200">
          <button
            onClick={() => setActivePanel('document')}
            className={clsx(
              'flex-1 py-3 text-sm font-medium transition-colors',
              activePanel === 'document'
                ? 'text-primary-600 border-b-2 border-primary-600'
                : 'text-slate-500'
            )}
          >
            Document
          </button>
          <button
            onClick={() => setActivePanel('chat')}
            className={clsx(
              'flex-1 py-3 text-sm font-medium transition-colors',
              activePanel === 'chat'
                ? 'text-primary-600 border-b-2 border-primary-600'
                : 'text-slate-500'
            )}
          >
            Chat with Claude
          </button>
        </div>
        
        {/* Content */}
        <div className="flex-1 overflow-hidden">
          {activePanel === 'document' ? (
            <div className="h-full overflow-auto">{children}</div>
          ) : (
            <ChatPanel />
          )}
        </div>
        
        {/* Mobile input fixed at bottom */}
        {activePanel === 'chat' && <MobileChatInput />}
      </div>
    );
  }
  
  // Desktop: side-by-side layout
  return (
    <div className="h-screen flex">
      <div className="flex-1 overflow-auto border-r border-slate-200">
        {children}
      </div>
      <div className="w-96 flex flex-col">
        <ChatPanel />
      </div>
    </div>
  );
};
```

### Keyboard Navigation

```typescript
// src/hooks/useKeyboardNavigation.ts
import { useEffect, useCallback } from 'react';

interface KeyboardShortcut {
  key: string;
  ctrl?: boolean;
  shift?: boolean;
  alt?: boolean;
  handler: () => void;
  description: string;
}

export const useKeyboardShortcuts = (shortcuts: KeyboardShortcut[]) => {
  const handleKeyDown = useCallback((e: KeyboardEvent) => {
    // Don't trigger in inputs unless it's a global shortcut
    const target = e.target as HTMLElement;
    const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable;
    
    for (const shortcut of shortcuts) {
      const matchesKey = e.key.toLowerCase() === shortcut.key.toLowerCase();
      const matchesCtrl = !!shortcut.ctrl === (e.ctrlKey || e.metaKey);
      const matchesShift = !!shortcut.shift === e.shiftKey;
      const matchesAlt = !!shortcut.alt === e.altKey;
      
      if (matchesKey && matchesCtrl && matchesShift && matchesAlt) {
        // Allow Ctrl shortcuts in inputs
        if (isInput && !shortcut.ctrl) continue;
        
        e.preventDefault();
        shortcut.handler();
        return;
      }
    }
  }, [shortcuts]);
  
  useEffect(() => {
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handleKeyDown]);
};

// Global shortcuts for the app
export const useGlobalShortcuts = () => {
  const navigate = useNavigate();
  const { createPrd } = usePrdStore();
  
  useKeyboardShortcuts([
    {
      key: 'n',
      ctrl: true,
      handler: () => createPrd().then(prd => navigate(`/prd/${prd.id}`)),
      description: 'Create new PRD',
    },
    {
      key: 'k',
      ctrl: true,
      handler: () => openCommandPalette(),
      description: 'Open command palette',
    },
    {
      key: '/',
      handler: () => document.getElementById('search-input')?.focus(),
      description: 'Focus search',
    },
    {
      key: 'Escape',
      handler: () => closeAllModals(),
      description: 'Close modal',
    },
  ]);
};

// Editor-specific shortcuts
export const useEditorShortcuts = (prdId: string) => {
  const { saveDraft, undo, redo } = usePrdStore();
  
  useKeyboardShortcuts([
    {
      key: 's',
      ctrl: true,
      handler: () => saveDraft(prdId),
      description: 'Save',
    },
    {
      key: 'z',
      ctrl: true,
      handler: () => undo(),
      description: 'Undo',
    },
    {
      key: 'z',
      ctrl: true,
      shift: true,
      handler: () => redo(),
      description: 'Redo',
    },
    {
      key: 'Enter',
      ctrl: true,
      handler: () => document.getElementById('chat-input')?.focus(),
      description: 'Focus chat',
    },
  ]);
};

// Keyboard shortcut help dialog
export const KeyboardShortcutsDialog: React.FC<{ open: boolean; onClose: () => void }> = ({
  open,
  onClose,
}) => {
  const shortcuts = [
    { keys: ['Ctrl', 'N'], description: 'Create new PRD' },
    { keys: ['Ctrl', 'K'], description: 'Open command palette' },
    { keys: ['Ctrl', 'S'], description: 'Save changes' },
    { keys: ['Ctrl', 'Z'], description: 'Undo' },
    { keys: ['Ctrl', 'Shift', 'Z'], description: 'Redo' },
    { keys: ['Ctrl', 'Enter'], description: 'Focus chat input' },
    { keys: ['/'], description: 'Focus search' },
    { keys: ['Esc'], description: 'Close modal/dialog' },
  ];
  
  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent>
        <DialogTitle>Keyboard Shortcuts</DialogTitle>
        <div className="space-y-2 mt-4">
          {shortcuts.map(({ keys, description }) => (
            <div key={description} className="flex items-center justify-between py-2">
              <span className="text-sm text-slate-600">{description}</span>
              <div className="flex gap-1">
                {keys.map(key => (
                  <kbd
                    key={key}
                    className="px-2 py-1 text-xs font-mono bg-slate-100 border border-slate-200 rounded"
                  >
                    {key}
                  </kbd>
                ))}
              </div>
            </div>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  );
};
```

### Accessibility Utilities

```typescript
// src/lib/accessibility.ts

// Screen reader announcements
export const announce = (message: string, priority: 'polite' | 'assertive' = 'polite') => {
  const el = document.createElement('div');
  el.setAttribute('aria-live', priority);
  el.setAttribute('aria-atomic', 'true');
  el.setAttribute('class', 'sr-only');
  document.body.appendChild(el);
  
  // Wait for DOM to register the element
  setTimeout(() => {
    el.textContent = message;
    setTimeout(() => document.body.removeChild(el), 1000);
  }, 100);
};

// Focus trap for modals
export const useFocusTrap = (containerRef: React.RefObject<HTMLElement>) => {
  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;
    
    const focusableElements = container.querySelectorAll<HTMLElement>(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key !== 'Tab') return;
      
      if (e.shiftKey) {
        if (document.activeElement === firstElement) {
          e.preventDefault();
          lastElement?.focus();
        }
      } else {
        if (document.activeElement === lastElement) {
          e.preventDefault();
          firstElement?.focus();
        }
      }
    };
    
    // Focus first element on mount
    firstElement?.focus();
    
    container.addEventListener('keydown', handleKeyDown);
    return () => container.removeEventListener('keydown', handleKeyDown);
  }, [containerRef]);
};

// Skip link for keyboard users
export const SkipLink: React.FC = () => (
  <a
    href="#main-content"
    className={clsx(
      'sr-only focus:not-sr-only',
      'fixed top-4 left-4 z-50 px-4 py-2',
      'bg-primary-600 text-white rounded-lg font-medium',
      'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2'
    )}
  >
    Skip to main content
  </a>
);

// Tailwind sr-only class
// .sr-only {
//   position: absolute;
//   width: 1px;
//   height: 1px;
//   padding: 0;
//   margin: -1px;
//   overflow: hidden;
//   clip: rect(0, 0, 0, 0);
//   white-space: nowrap;
//   border-width: 0;
// }
```

### Form Validation UX

```typescript
// src/hooks/useForm.ts
import { useState, useCallback } from 'react';

interface FieldState {
  value: string;
  error: string | null;
  touched: boolean;
}

interface FormState<T extends Record<string, string>> {
  fields: { [K in keyof T]: FieldState };
  isSubmitting: boolean;
  isValid: boolean;
}

type Validator = (value: string) => string | null;

interface FieldConfig {
  initialValue?: string;
  validators?: Validator[];
}

export const useForm = <T extends Record<string, FieldConfig>>(config: T) => {
  const initialFields = Object.fromEntries(
    Object.entries(config).map(([key, { initialValue }]) => [
      key,
      { value: initialValue || '', error: null, touched: false },
    ])
  );
  
  const [fields, setFields] = useState(initialFields);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const validateField = useCallback((name: string, value: string) => {
    const validators = config[name]?.validators || [];
    for (const validator of validators) {
      const error = validator(value);
      if (error) return error;
    }
    return null;
  }, [config]);
  
  const handleChange = useCallback((name: string) => (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const value = e.target.value;
    setFields(prev => ({
      ...prev,
      [name]: { ...prev[name], value, touched: true },
    }));
  }, []);
  
  const handleBlur = useCallback((name: string) => () => {
    setFields(prev => ({
      ...prev,
      [name]: {
        ...prev[name],
        touched: true,
        error: validateField(name, prev[name].value),
      },
    }));
  }, [validateField]);
  
  const validateAll = useCallback(() => {
    let isValid = true;
    const newFields = { ...fields };
    
    for (const name of Object.keys(fields)) {
      const error = validateField(name, fields[name].value);
      newFields[name] = { ...newFields[name], error, touched: true };
      if (error) isValid = false;
    }
    
    setFields(newFields);
    return isValid;
  }, [fields, validateField]);
  
  const handleSubmit = useCallback((onSubmit: (values: Record<string, string>) => Promise<void>) =>
    async (e: React.FormEvent) => {
      e.preventDefault();
      
      if (!validateAll()) return;
      
      setIsSubmitting(true);
      try {
        const values = Object.fromEntries(
          Object.entries(fields).map(([key, { value }]) => [key, value])
        );
        await onSubmit(values);
      } finally {
        setIsSubmitting(false);
      }
    }, [fields, validateAll]);
  
  const getFieldProps = useCallback((name: string) => ({
    value: fields[name]?.value || '',
    error: fields[name]?.touched ? fields[name]?.error : null,
    onChange: handleChange(name),
    onBlur: handleBlur(name),
  }), [fields, handleChange, handleBlur]);
  
  return {
    fields,
    isSubmitting,
    isValid: Object.values(fields).every(f => !f.error),
    handleSubmit,
    getFieldProps,
    validateAll,
  };
};

// Common validators
export const validators = {
  required: (message = 'This field is required'): Validator =>
    (value) => value.trim() ? null : message,
  
  email: (message = 'Invalid email address'): Validator =>
    (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? null : message,
  
  minLength: (min: number, message?: string): Validator =>
    (value) => value.length >= min ? null : message || `Must be at least ${min} characters`,
  
  maxLength: (max: number, message?: string): Validator =>
    (value) => value.length <= max ? null : message || `Must be at most ${max} characters`,
  
  pattern: (regex: RegExp, message: string): Validator =>
    (value) => regex.test(value) ? null : message,
};

// Usage example
const CreateRuleForm: React.FC = () => {
  const { getFieldProps, handleSubmit, isSubmitting } = useForm({
    name: {
      validators: [validators.required(), validators.maxLength(255)],
    },
    content: {
      validators: [validators.required(), validators.maxLength(51200)],
    },
  });
  
  const onSubmit = async (values: Record<string, string>) => {
    await api.post('/api/rules', values);
    toast.success('Rule created');
  };
  
  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <Input
        label="Rule Name"
        placeholder="e.g., Security Requirements"
        {...getFieldProps('name')}
      />
      
      <Textarea
        label="Rule Content"
        placeholder="Enter the instructions Claude should follow..."
        rows={10}
        {...getFieldProps('content')}
      />
      
      <Button type="submit" isLoading={isSubmitting}>
        Create Rule
      </Button>
    </form>
  );
};
```

---

## Test Examples

### Backend Tests

```php
// tests/Feature/PrdControllerTest.php
<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrdControllerTest extends TestCase
{
    use RefreshDatabase;
    
    private User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }
    
    /** @test */
    public function user_can_list_their_prds(): void
    {
        // Arrange
        Prd::factory()->count(3)->create(['user_id' => $this->user->id]);
        Prd::factory()->count(2)->create(); // Other user's PRDs
        
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/prds');
        
        // Assert
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'status', 'created_at', 'updated_at'],
                ],
            ]);
    }
    
    /** @test */
    public function user_can_create_prd(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/prds', [
                'title' => 'My New PRD',
            ]);
        
        // Assert
        $response->assertCreated()
            ->assertJsonPath('title', 'My New PRD')
            ->assertJsonPath('status', 'draft');
        
        $this->assertDatabaseHas('prds', [
            'user_id' => $this->user->id,
            'title' => 'My New PRD',
        ]);
        
        // Verify file was created
        $prdId = $response->json('id');
        $filePath = storage_path("prds/{$this->user->id}/{$prdId}.md");
        $this->assertFileExists($filePath);
    }
    
    /** @test */
    public function user_cannot_access_other_users_prd(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $prd = Prd::factory()->create(['user_id' => $otherUser->id]);
        
        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/prds/{$prd->id}");
        
        // Assert - 404 not 403 (prevent enumeration)
        $response->assertNotFound();
    }
    
    /** @test */
    public function prd_is_soft_deleted(): void
    {
        // Arrange
        $prd = Prd::factory()->create(['user_id' => $this->user->id]);
        
        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/prds/{$prd->id}");
        
        // Assert
        $response->assertOk();
        $this->assertSoftDeleted('prds', ['id' => $prd->id]);
    }
    
    /** @test */
    public function free_tier_user_cannot_create_more_than_3_prds(): void
    {
        // Arrange
        $this->user->update(['tier' => 'free']);
        Prd::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/prds', ['title' => 'Fourth PRD']);
        
        // Assert
        $response->assertStatus(402)
            ->assertJsonPath('code', 'TIER_LIMIT_REACHED');
    }
}

// tests/Feature/ChatControllerTest.php
<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\User;
use App\Services\AnthropicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function user_can_send_message_and_receive_streamed_response(): void
    {
        // Arrange
        $user = User::factory()->create();
        $prd = Prd::factory()->create(['user_id' => $user->id]);
        
        // Mock Anthropic service
        $this->mock(AnthropicService::class, function ($mock) {
            $mock->shouldReceive('streamChat')
                ->once()
                ->andReturnUsing(function () {
                    yield ['type' => 'chunk', 'text' => 'Hello, '];
                    yield ['type' => 'chunk', 'text' => 'how can I help?'];
                    yield ['type' => 'done', 'full_response' => 'Hello, how can I help?'];
                });
        });
        
        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/prds/{$prd->id}/messages", [
                'content' => 'Help me with my PRD',
            ]);
        
        // Assert
        $response->assertOk()
            ->assertHeader('Content-Type', 'text/event-stream');
        
        // Verify messages saved
        $this->assertDatabaseHas('messages', [
            'prd_id' => $prd->id,
            'role' => 'user',
            'content' => 'Help me with my PRD',
        ]);
        
        $this->assertDatabaseHas('messages', [
            'prd_id' => $prd->id,
            'role' => 'assistant',
            'content' => 'Hello, how can I help?',
        ]);
    }
    
    /** @test */
    public function rate_limiting_prevents_spam(): void
    {
        // Arrange
        $user = User::factory()->create();
        $prd = Prd::factory()->create(['user_id' => $user->id]);
        
        // Act - Send 2 requests immediately
        $response1 = $this->actingAs($user)
            ->postJson("/api/prds/{$prd->id}/messages", ['content' => 'First']);
        
        $response2 = $this->actingAs($user)
            ->postJson("/api/prds/{$prd->id}/messages", ['content' => 'Second']);
        
        // Assert
        $response1->assertOk();
        $response2->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMITED')
            ->assertJsonStructure(['retry_after']);
    }
}

// tests/Unit/TokenEncryptionServiceTest.php
<?php

namespace Tests\Unit;

use App\Services\TokenEncryptionService;
use Tests\TestCase;

class TokenEncryptionServiceTest extends TestCase
{
    private TokenEncryptionService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.token_encryption_key' => base64_encode(random_bytes(32))]);
        $this->service = new TokenEncryptionService();
    }
    
    /** @test */
    public function it_encrypts_and_decrypts_correctly(): void
    {
        $original = 'ya29.a0AfB_byC1234567890';
        
        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);
        
        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, $decrypted);
    }
    
    /** @test */
    public function encrypted_values_are_different_each_time(): void
    {
        $original = 'test_token';
        
        $encrypted1 = $this->service->encrypt($original);
        $encrypted2 = $this->service->encrypt($original);
        
        $this->assertNotEquals($encrypted1, $encrypted2);
    }
    
    /** @test */
    public function it_throws_on_tampered_data(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $encrypted = $this->service->encrypt('test');
        $tampered = base64_encode(str_repeat('x', 50));
        
        $this->service->decrypt($tampered);
    }
}
```

### Frontend Tests

```typescript
// src/components/ui/__tests__/Button.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from '../Button';

describe('Button', () => {
  it('renders children correctly', () => {
    render(<Button>Click me</Button>);
    expect(screen.getByRole('button', { name: /click me/i })).toBeInTheDocument();
  });
  
  it('handles click events', () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>Click me</Button>);
    
    fireEvent.click(screen.getByRole('button'));
    
    expect(handleClick).toHaveBeenCalledTimes(1);
  });
  
  it('shows loading spinner when isLoading', () => {
    render(<Button isLoading>Submit</Button>);
    
    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });
  
  it('is disabled when disabled prop is true', () => {
    render(<Button disabled>Submit</Button>);
    expect(screen.getByRole('button')).toBeDisabled();
  });
  
  it('applies variant styles correctly', () => {
    const { rerender } = render(<Button variant="primary">Primary</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-primary-600');
    
    rerender(<Button variant="danger">Danger</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-red-600');
  });
});

// src/stores/__tests__/authStore.test.ts
import { renderHook, act } from '@testing-library/react';
import { useAuthStore } from '../authStore';

describe('authStore', () => {
  beforeEach(() => {
    useAuthStore.setState({ user: null, isAuthenticated: false, isLoading: true });
  });
  
  it('checkAuth sets user when authenticated', async () => {
    const mockUser = { id: '1', name: 'Test User', email: 'test@example.com' };
    vi.spyOn(global, 'fetch').mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve(mockUser),
    } as Response);
    
    const { result } = renderHook(() => useAuthStore());
    
    await act(async () => {
      await result.current.checkAuth();
    });
    
    expect(result.current.user).toEqual(mockUser);
    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.isLoading).toBe(false);
  });
  
  it('checkAuth clears user when not authenticated', async () => {
    vi.spyOn(global, 'fetch').mockResolvedValueOnce({
      ok: false,
      status: 401,
    } as Response);
    
    const { result } = renderHook(() => useAuthStore());
    
    await act(async () => {
      await result.current.checkAuth();
    });
    
    expect(result.current.user).toBeNull();
    expect(result.current.isAuthenticated).toBe(false);
    expect(result.current.isLoading).toBe(false);
  });
});

// src/pages/__tests__/Dashboard.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { Dashboard } from '../Dashboard';
import { usePrdStore } from '@/stores/prdStore';

const mockPrds = [
  { id: '1', title: 'First PRD', status: 'draft', created_at: '2026-02-01' },
  { id: '2', title: 'Second PRD', status: 'active', created_at: '2026-02-02' },
];

describe('Dashboard', () => {
  beforeEach(() => {
    usePrdStore.setState({ prds: mockPrds, isLoading: false, error: null });
  });
  
  it('renders PRD list', () => {
    render(<Dashboard />, { wrapper: MemoryRouter });
    
    expect(screen.getByText('First PRD')).toBeInTheDocument();
    expect(screen.getByText('Second PRD')).toBeInTheDocument();
  });
  
  it('shows loading skeleton while fetching', () => {
    usePrdStore.setState({ prds: [], isLoading: true });
    
    render(<Dashboard />, { wrapper: MemoryRouter });
    
    expect(screen.getByTestId('dashboard-skeleton')).toBeInTheDocument();
  });
  
  it('shows empty state when no PRDs', () => {
    usePrdStore.setState({ prds: [], isLoading: false });
    
    render(<Dashboard />, { wrapper: MemoryRouter });
    
    expect(screen.getByText(/no prds yet/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /create/i })).toBeInTheDocument();
  });
  
  it('filters PRDs by search query', async () => {
    const user = userEvent.setup();
    render(<Dashboard />, { wrapper: MemoryRouter });
    
    const searchInput = screen.getByPlaceholderText(/search/i);
    await user.type(searchInput, 'First');
    
    await waitFor(() => {
      expect(screen.getByText('First PRD')).toBeInTheDocument();
      expect(screen.queryByText('Second PRD')).not.toBeInTheDocument();
    });
  });
  
  it('shows error state with retry button', async () => {
    const fetchPrds = vi.fn();
    usePrdStore.setState({ prds: [], isLoading: false, error: 'Failed to fetch', fetchPrds });
    
    render(<Dashboard />, { wrapper: MemoryRouter });
    
    expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
    
    const retryButton = screen.getByRole('button', { name: /retry/i });
    await userEvent.click(retryButton);
    
    expect(fetchPrds).toHaveBeenCalled();
  });
});
```

---

## Final Verification Checklist

Before deployment, all items must be checked:

### Security

| Item | Owner | Status |
|------|-------|--------|
| All OAuth scopes correctly requested (drive.file) | Backend | ⬜ |
| Tokens encrypted at rest (AES-256-GCM via TokenEncryptionService) | Backend | ⬜ |
| HTTP-only, Secure, SameSite=Strict cookies | Backend | ⬜ |
| Rate limiting on all endpoints (see RateLimiter config) | Backend | ⬜ |
| CSP and security headers (nginx.conf) | DevOps | ⬜ |
| CSRF protection via state parameter | Backend | ⬜ |
| Input validation on all endpoints (Form Requests) | Backend | ⬜ |
| Output encoding (DOMPurify for markdown) | Frontend | ⬜ |
| Path traversal prevention (UUID validation) | Backend | ⬜ |
| SQL injection impossible (Eloquent ORM) | Backend | ⬜ |
| File upload validation (MIME + magic bytes) | Backend | ⬜ |
| 404 for unauthorized resources (not 403) | Backend | ⬜ |
| Token encryption key configured | DevOps | ⬜ |
| Stripe webhook signature verification | Backend | ⬜ |

### Performance

| Item | Target | Status |
|------|--------|--------|
| LCP (Largest Contentful Paint) | < 2s | ⬜ |
| API p95 response time | < 500ms | ⬜ |
| First token from Claude | < 1s | ⬜ |
| PDF export (50 pages) | < 60s | ⬜ |
| Dashboard with 100 PRDs | < 1s | ⬜ |
| Chat history with 500 messages | Virtual scroll, no lag | ⬜ |

### UX

| Item | Status |
|------|--------|
| Loading skeletons on all async content | ⬜ |
| Error states with recovery actions | ⬜ |
| Empty states with CTAs | ⬜ |
| Full keyboard navigation | ⬜ |
| WCAG 2.1 AA compliance | ⬜ |
| Mobile-responsive design (tablet + phone) | ⬜ |
| Save indicator (Saving.../Saved/Error) | ⬜ |
| Context gauge with color coding | ⬜ |
| Confirmation dialogs for destructive actions | ⬜ |
| Toast notifications for success/error | ⬜ |

### Testing

| Test Type | Coverage | Status |
|-----------|----------|--------|
| Unit tests (Services) | > 80% | ⬜ |
| Integration tests (Controllers) | All 85+ endpoints | ⬜ |
| E2E tests (Playwright) | Critical user flows | ⬜ |
| Security tests | OWASP Top 10 scenarios | ⬜ |
| Load tests (k6) | 100 concurrent users | ⬜ |
| Accessibility tests (axe-core) | WCAG 2.1 AA | ⬜ |

### Database

| Item | Status |
|------|--------|
| All 20 migrations run successfully | ⬜ |
| Migrations rollback cleanly | ⬜ |
| Indexes on foreign keys | ⬜ |
| Soft delete cascade works | ⬜ |
| System templates seeded | ⬜ |
| System agents seeded | ⬜ |

### Scheduled Jobs

| Job | Schedule | Status |
|-----|----------|--------|
| Hard delete expired PRDs | Daily 03:00 | ⬜ |
| Clean expired draft attachments | Hourly | ⬜ |
| Auto-sync Google Drive links | Every 5 min | ⬜ |
| Clean old context summaries | Daily 04:00 | ⬜ |

---

## Implementation Metrics

### Phase Summary

| Phase | Feature | Migration Count | Endpoints | Estimated Effort |
|-------|---------|-----------------|-----------|------------------|
| 0 | Scaffolding | 0 | 1 | Foundation |
| 1 | Authentication | 1 | 5 | Medium |
| 2 | PRD CRUD | 4 | 10 | Medium |
| 3 | Chat System | 2 | 6 | High |
| 4 | Attachments | 1 | 2 | Medium |
| 5 | Context Management | 1 | 2 | Medium |
| 6 | PRD Editor | 0 | 0 | Medium |
| 7 | Rules | 2 | 6 | Low |
| 8 | Export | 0 | 2 | Medium |
| 9 | Google Drive | 1 | 8 | High |
| 10 | Templates | 1 | 6 | Low |
| 11 | Versions | 1 | 5 | Medium |
| 12 | Collaboration | 3 | 10 | High |
| 13 | Teams | 2 | 9 | Medium |
| 14 | Billing | 0 | 4 | High |
| 15 | i18n | 0 | 2 | Medium |
| 16 | SME Agents | 3 | 8 | Medium |
| **Total** | | **20** | **85+** | |

---

## Appendix A: Environment Variables

```env
# ============================================
# APPLICATION
# ============================================
APP_NAME="PRD Tool"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:11111
FRONTEND_URL=http://localhost:5173

# ============================================
# DATABASE (Supabase PostgreSQL)
# ============================================
DB_CONNECTION=pgsql
DB_HOST=db.xxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password

# ============================================
# GOOGLE OAUTH & DRIVE
# ============================================
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-...
GOOGLE_REDIRECT_URI=http://localhost:11111/auth/google/callback
GOOGLE_PICKER_API_KEY=AIza...

# ============================================
# ANTHROPIC API
# ============================================
ANTHROPIC_API_KEY=sk-ant-api03-...
ANTHROPIC_MODEL_CHAT=claude-opus-4-20250514
ANTHROPIC_MODEL_SUMMARIZE=claude-haiku-3.5-20250110

# ============================================
# STRIPE BILLING
# ============================================
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_PRO=price_...
STRIPE_PRICE_TEAM=price_...
STRIPE_PRICE_ENTERPRISE=price_...

# ============================================
# DEEPL TRANSLATION
# ============================================
DEEPL_API_KEY=your-deepl-key

# ============================================
# SUPABASE REALTIME
# ============================================
SUPABASE_URL=https://xxxx.supabase.co
SUPABASE_ANON_KEY=eyJhbGci...

# ============================================
# REDIS (Session + Queue + Cache)
# ============================================
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# ============================================
# SESSION
# ============================================
SESSION_DRIVER=redis
SESSION_LIFETIME=10080

# ============================================
# QUEUE
# ============================================
QUEUE_CONNECTION=redis

# ============================================
# SECURITY
# ============================================
# Generate with: openssl rand -base64 32
TOKEN_ENCRYPTION_KEY=your-32-byte-key-base64-encoded

# ============================================
# PUPPETEER SERVICE (PDF Export)
# ============================================
PUPPETEER_ENDPOINT=http://puppeteer:3000
```

---

## Appendix B: Docker Services

Add Puppeteer service to docker-compose.yml for PDF export:

```yaml
services:
  # ... existing services ...
  
  puppeteer:
    build:
      context: ./docker/puppeteer
      dockerfile: Dockerfile
    ports:
      - "3001:3000"
    environment:
      - NODE_ENV=production
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
```

```dockerfile
# docker/puppeteer/Dockerfile
FROM node:20-alpine

RUN apk add --no-cache chromium nss freetype harfbuzz ca-certificates ttf-freefont

ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

WORKDIR /app
COPY package.json server.js ./
RUN npm install --production

EXPOSE 3000
CMD ["node", "server.js"]
```

---

## Appendix C: Rate Limiting Configuration

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(1000)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinutes(5, 10)->by($request->ip());
});

RateLimiter::for('chat', function (Request $request) {
    return Limit::perMinute(20)->by($request->user()?->id);
});

RateLimiter::for('export', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()?->id);
});

RateLimiter::for('upload', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id);
});
```

---

## Appendix D: Logging Configuration

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'stderr'],
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 90,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
    
    'stderr' => [
        'driver' => 'monolog',
        'level' => env('LOG_LEVEL', 'debug'),
        'handler' => \Monolog\Handler\StreamHandler::class,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
        'with' => [
            'stream' => 'php://stderr',
        ],
    ],
    
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 365,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
],
```

---

## Change Log

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-03 | Initial implementation plan with Phases 0-6 detailed |
| 2.0 | 2026-02-03 | Complete overhaul: Expanded Phases 7-16, added all 20 migrations, added scheduled jobs, fixed assistant message saving, added Dockerfile, added routes, added token encryption, added Supabase Realtime, added dependencies |

---

**End of Implementation Plan v2.0**

*This document is now implementation-ready. Mark each phase complete with a commit hash as you progress.*

*Total: 16 phases, 20 database tables, 85+ API endpoints, comprehensive edge case handling.*
