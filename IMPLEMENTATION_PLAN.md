# PRD Tool — Implementation Plan

**Version:** 1.0  
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
├── .env.example               # Environment template
├── backend/                   # Laravel 11 application
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   └── tests/
├── frontend/                  # React 18 + TypeScript
│   ├── src/
│   ├── public/
│   └── tests/
├── docs/                      # Additional documentation
└── scripts/                   # Utility scripts
```

---

## Phase 0: Project Scaffolding

**Duration Estimate:** Foundation setup  
**Risk Level:** Low  
**Dependencies:** None

### 0.1 Docker Environment

Create the development environment with Docker Compose.

#### Implementation

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
      - ./storage/prds:/var/www/html/storage/prds
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
    depends_on:
      - redis

  node:
    image: node:20-alpine
    working_dir: /app
    volumes:
      - ./frontend:/app
    ports:
      - "5173:5173"
    command: sh -c "npm install && npm run dev -- --host"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
```

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| Docker not installed | Clear error in README with install link |
| Port 11111 already in use | Document how to change port |
| Volume permissions on Linux | Add user mapping in Dockerfile |
| Slow first build | Document expected time (~5 min) |

#### Verification Gate

```bash
# All must pass before proceeding
docker-compose up -d
curl http://localhost:11111/health  # Returns {"status":"ok"}
curl http://localhost:5173          # Returns Vite dev page
docker-compose logs --tail=50 app   # No errors
```

### 0.2 Laravel Installation

#### Implementation Steps

1. Create Laravel 11 project in `backend/`
2. Configure Supabase PostgreSQL connection
3. Set up basic health endpoint
4. Configure CORS for local development

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
php artisan route:list              # Shows /health route
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

### 1.1 Database Schema — Users

#### Migrations

```php
// database/migrations/001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('google_id')->unique();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('avatar_url')->nullable();
    $table->text('google_access_token');       // Encrypted
    $table->text('google_refresh_token');      // Encrypted
    $table->timestamp('google_token_expires_at');
    $table->uuid('last_prd_id')->nullable();
    $table->string('preferred_language', 10)->default('en');
    $table->enum('tier', ['free', 'pro', 'team', 'enterprise'])->default('free');
    $table->timestamp('tier_expires_at')->nullable();
    $table->string('stripe_customer_id')->nullable();
    $table->timestamps();
    
    $table->index('google_id');
    $table->index('email');
});
```

#### Edge Cases to Handle

| Edge Case | Implementation |
|-----------|----------------|
| Existing email with different google_id | Match by google_id, update email |
| Email change in Google | Update on next login |
| Avatar URL from Google expires | Re-fetch on each login |

#### Verification Gate

```bash
php artisan migrate
php artisan migrate:rollback
php artisan migrate                 # Both directions work
php artisan tinker
> User::factory()->create()         # Factory works
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
    
    public function streamChat(
        array $messages,
        string $systemPrompt,
        callable $onChunk,
        ?callable $onError = null
    ): StreamedResponse {
        
        return new StreamedResponse(function () use ($messages, $systemPrompt, $onChunk, $onError) {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/messages", [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 8192,
                'system' => $systemPrompt,
                'messages' => $messages,
                'stream' => true,
            ]);
            
            if (!$response->successful()) {
                $onError?.($response->status(), $response->body());
                return;
            }
            
            $buffer = '';
            foreach ($response->getBody() as $chunk) {
                $buffer .= $chunk;
                
                // Parse SSE events
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $event = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);
                    
                    if (preg_match('/^data: (.+)$/m', $event, $matches)) {
                        $data = json_decode($matches[1], true);
                        
                        if ($data['type'] === 'content_block_delta') {
                            $text = $data['delta']['text'] ?? '';
                            $onChunk($text);
                            echo "data: " . json_encode(['type' => 'content', 'text' => $text]) . "\n\n";
                            ob_flush();
                            flush();
                        }
                    }
                }
            }
            
            echo "data: " . json_encode(['type' => 'done']) . "\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
```

### 3.3 Chat Controller

```php
// app/Http/Controllers/ChatController.php
class ChatController extends Controller
{
    public function sendMessage(Request $request, string $prdId): StreamedResponse
    {
        $prd = $this->findUserPrd($request, $prdId);
        
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);
        
        // Rate limiting: 1 message per 2 seconds
        $rateLimitKey = "chat:{$request->user()->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return response()->json([
                'message' => 'Please wait before sending another message',
                'retry_after' => RateLimiter::availableIn($rateLimitKey),
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 2);
        
        // Save user message
        $userMessage = Message::create([
            'id' => Str::uuid(),
            'prd_id' => $prd->id,
            'role' => 'user',
            'content' => $validated['content'],
            'token_estimate' => $this->estimateTokens($validated['content']),
        ]);
        
        // Build context
        $contextManager = app(ContextManager::class);
        $context = $contextManager->buildPrompt($prd, $userMessage);
        
        // Stream response
        $fullResponse = '';
        
        return app(AnthropicService::class)->streamChat(
            $context['messages'],
            $context['system'],
            function (string $chunk) use (&$fullResponse) {
                $fullResponse .= $chunk;
            },
            function (int $status, string $body) {
                Log::error('Anthropic API error', [
                    'status' => $status,
                    'body' => $body,
                ]);
            }
        );
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

## Phases 7-15: Summary

Due to length, subsequent phases are summarized. Each follows the same pattern:

### Phase 7: Rules System
- Create/Edit/Delete rules
- Assign rules to PRDs
- Rules in context building
- **Test Gate:** Rules CRUD, assignment, context inclusion

### Phase 8: Export System
- Markdown download
- PDF generation with Puppeteer
- Mermaid diagrams in PDF
- **Test Gate:** All export formats, large document handling

### Phase 9: Google Drive Integration
- OAuth scope handling
- Picker API integration
- File download and processing
- Bidirectional sync
- **Test Gate:** Drive picker, file download, sync conflicts

### Phase 10: Templates
- System templates
- User templates CRUD
- Create PRD from template
- Save PRD as template
- **Test Gate:** Template CRUD, PRD creation from template

### Phase 11: Version History
- Auto-save versions after AI edits
- Version listing
- Version diff
- Restore version
- Named versions (bookmarks)
- **Test Gate:** Version creation, diff, restore

### Phase 12: Collaboration
- Collaborator invites
- Permission levels (viewer/commenter/editor)
- Share links
- Comments and replies
- Real-time presence
- **Test Gate:** Invites, permissions, comments, presence

### Phase 13: Teams
- Team creation
- Member management
- Team PRDs
- Team templates
- **Test Gate:** Team CRUD, member roles, team resources

### Phase 14: Billing (Stripe)
- Checkout session creation
- Customer portal
- Webhook handling
- Tier enforcement
- **Test Gate:** Checkout, subscription, tier limits

### Phase 15: Internationalization
- UI translation (en, sk)
- PRD content translation
- Translation caching
- **Test Gate:** Language switching, translation API, cache

### Phase 16: SME Agents
- Agent definitions
- Agent chat routing
- Multi-agent UI
- Custom agent creation
- **Test Gate:** Agent CRUD, agent conversations, suggestions

---

## Final Verification Checklist

Before deployment, all items must be checked:

### Security

| Item | Status |
|------|--------|
| All OAuth scopes correctly requested | ⬜ |
| Tokens encrypted at rest (AES-256-GCM) | ⬜ |
| HTTP-only, Secure, SameSite cookies | ⬜ |
| Rate limiting on all endpoints | ⬜ |
| CSP and security headers configured | ⬜ |
| CSRF protection active | ⬜ |
| Input validation on all endpoints | ⬜ |
| Output encoding (DOMPurify) | ⬜ |
| Path traversal prevention | ⬜ |
| SQL injection impossible (Eloquent) | ⬜ |
| File upload validation (MIME + magic) | ⬜ |

### Performance

| Item | Target | Status |
|------|--------|--------|
| LCP | < 2s | ⬜ |
| API p95 response | < 500ms | ⬜ |
| First token from Claude | < 1s | ⬜ |
| PDF export (50 pages) | < 60s | ⬜ |

### UX

| Item | Status |
|------|--------|
| Loading skeletons on all async content | ⬜ |
| Error states with recovery actions | ⬜ |
| Empty states with CTAs | ⬜ |
| Full keyboard navigation | ⬜ |
| WCAG 2.1 AA compliance | ⬜ |
| Mobile-responsive design | ⬜ |

### Testing

| Test Type | Coverage | Status |
|-----------|----------|--------|
| Unit tests | > 80% | ⬜ |
| Integration tests | All API endpoints | ⬜ |
| E2E tests | Critical paths | ⬜ |
| Security tests | OWASP scenarios | ⬜ |
| Load tests | 100 concurrent users | ⬜ |

---

## Appendix: Environment Variables

```env
# Application
APP_NAME="PRD Tool"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:11111

# Database (Supabase)
DB_CONNECTION=pgsql
DB_HOST=db.xxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password

# Google OAuth
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:11111/auth/google/callback
GOOGLE_PICKER_API_KEY=your-picker-api-key

# Anthropic
ANTHROPIC_API_KEY=sk-ant-...

# Stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# DeepL Translation
DEEPL_API_KEY=your-deepl-key

# Real-time
SUPABASE_URL=https://xxxx.supabase.co
SUPABASE_ANON_KEY=your-anon-key
REALTIME_ENABLED=true

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=10080  # 7 days

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

**End of Implementation Plan**

*This document should be updated as implementation progresses. Mark each phase complete with a commit hash.*
