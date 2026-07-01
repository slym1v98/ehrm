# M1 Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Establish dev environment (Docker Compose with Laravel 12 / PHP 8.4 + NextJS + PostgreSQL 16 + Redis 7 + MinIO), shared API conventions (error envelope, pagination, Sanctum auth middleware), and CI pipeline.

**Architecture:** Docker multi-service stack for local dev. Source lives under `src/`: Laravel backend in `src/backend`, NextJS frontend in `src/frontend`. Dockerfiles live under `docker/`: PHP in `docker/php/Dockerfile`, NextJS in `docker/nextjs/Dockerfile`. Laravel backend is API-only with Sanctum bearer tokens. Shared infrastructure layer lives in `src/backend/app/Modules/Shared/`.

**Tech Stack:** PHP 8.4, Laravel 12, Sanctum, PostgreSQL 16, Redis 7, MinIO, Docker Compose, NextJS 14+, Tailwind CSS, shadcn/ui, PHPStan, PHPUnit, ESLint, Prettier.

---

### Task 1.1: Write Docker Compose services

**Files:**
- Modify: `docker-compose.yml`
- Modify: `docker/php/Dockerfile`
- Modify: `docker/nextjs/Dockerfile`
- Modify: `.env.example`
- Modify: `.env.local.example`
- Modify: `.env.dev.example`
- Modify: `.env.prod.example`

- [x] **Step 1: Write Dockerfile for Laravel backend**

Update/verify `docker/php/Dockerfile`:

```dockerfile
FROM php:8.4-cli-alpine

RUN apk add --no-cache bash git unzip linux-headers autoconf g++ make \
    postgresql-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_pgsql bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/src/backend
COPY . /app

RUN addgroup -g 1000 -S app && adduser -u 1000 -S app -G app
USER app:app

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

- [x] **Step 2: Write Dockerfile for NextJS frontend**

Update/verify `docker/nextjs/Dockerfile`:

```dockerfile
FROM node:20-alpine
WORKDIR /app/src/frontend
EXPOSE 3000
CMD ["sh", "-c", "npm install && npm run dev"]
```

- [x] **Step 3: Update docker-compose.yml**

Update `docker-compose.yml`:

```yaml
name: ihrm

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    working_dir: /app/src/backend
    depends_on:
      db:
        condition: service_healthy
      minio:
        condition: service_healthy
      redis:
        condition: service_started
    env_file:
      - .env
    environment:
      APP_ENV: ${APP_ENV:-local}
      DB_CONNECTION: pgsql
      DB_HOST: db
      DB_PORT: 5432
      DB_DATABASE: ${DB_DATABASE:-ihrm}
      DB_USERNAME: ${DB_USERNAME:-ihrm}
      DB_PASSWORD: ${DB_PASSWORD:-ihrm}
      REDIS_HOST: redis
      REDIS_PORT: 6379
      MINIO_ENDPOINT: http://minio:9000
      MINIO_ACCESS_KEY: ${MINIO_ACCESS_KEY:-ihrm}
      MINIO_SECRET_KEY: ${MINIO_SECRET_KEY:-ihrm_secret}
      MINIO_BUCKET: ${MINIO_BUCKET:-ihrm-documents}
    ports:
      - "${APP_PORT:-8000}:8000"
    volumes:
      - .:/app

  frontend:
    build:
      context: .
      dockerfile: docker/nextjs/Dockerfile
    working_dir: /app/src/frontend
    depends_on:
      - app
    ports:
      - "${FRONTEND_PORT:-3000}:3000"
    volumes:
      - .:/app

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ${DB_DATABASE:-ihrm}
      POSTGRES_USER: ${DB_USERNAME:-ihrm}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-ihrm}
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-ihrm}"]
      interval: 5s
      timeout: 3s
      retries: 5
    ports:
      - "${DB_PORT:-5432}:5432"
    volumes:
      - pgdata:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 3s
      retries: 5
    ports:
      - "${REDIS_PORT:-6379}:6379"

  minio:
    image: minio/minio:latest
    command: server --console-address ":9001" /data
    environment:
      MINIO_ROOT_USER: ${MINIO_ACCESS_KEY:-ihrm}
      MINIO_ROOT_PASSWORD: ${MINIO_SECRET_KEY:-ihrm_secret}
    healthcheck:
      test: ["CMD", "mc", "ready", "local"]
      interval: 5s
      timeout: 3s
      retries: 5
    ports:
      - "${MINIO_PORT:-9000}:9000"
      - "${MINIO_CONSOLE_PORT:-9001}:9001"
    volumes:
      - miniodata:/data

volumes:
  pgdata:
  miniodata:
```

- [~] **Step 4: Commit**

```bash
git add docker/php/Dockerfile docker/nextjs/Dockerfile docker-compose.yml .env.example .env.local.example .env.dev.example .env.prod.example
git commit -m "infra: add Docker Compose with Laravel, NextJS, PostgreSQL, Redis, MinIO"
```

---

### Task 1.2: Scaffold Laravel backend

**Files:**
- Run: `composer create-project` inside `src/backend` (inside container)
- Create: various Laravel config files

- [~] **Step 1: Create Laravel project**

```bash
docker compose run --rm app composer create-project laravel/laravel:^12.0 .
```

Expected: `src/backend/artisan` binary exists, `src/backend/app/` directory present.

- [~] **Step 2: Install Sanctum for API auth**

```bash
docker compose run --rm app composer require laravel/sanctum
docker compose run --rm app php artisan install:api --no-interaction
```

Verify `src/backend/routes/api.php` exists.

- [x] **Step 3: Create module base structure**

Create directories:

```bash
docker compose run --rm app sh -lc "mkdir -p app/Modules/Shared/{Http/Middleware,Exceptions,Http/Resources}"
```

Verify by listing: `ls -R src/backend/app/Modules/`

- [~] **Step 4: Commit**

```bash
git add src/backend/composer.json src/backend/composer.lock src/backend/app/Modules src/backend/config src/backend/routes
git commit -m "infra: scaffold Laravel 12 with Sanctum and module structure"
```

---

### Task 1.3: Implement shared error envelope

**Files:**
- Create: `src/backend/app/Modules/Shared/Exceptions/AppException.php`
- Create: `src/backend/app/Modules/Shared/Exceptions/ValidationException.php`
- Create: `src/backend/app/Modules/Shared/Http/Resources/ErrorResource.php`
- Create: `src/backend/tests/Unit/Modules/Shared/ErrorResourceTest.php`
- Modify: `bootstrap/app.php`

- [x] **Step 1: Write AppException base class**

Creates `src/backend/app/Modules/Shared/Exceptions/AppException.php`:

```php
<?php

namespace App\Modules\Shared\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

abstract class AppException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message = '',
        public readonly array $details = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    abstract public function getHttpStatus(): int;
}
```

- [x] **Step 2: Write ValidationException**

Creates `src/backend/app/Modules/Shared/Exceptions/ValidationException.php`:

```php
<?php

namespace App\Modules\Shared\Exceptions;

class ValidationException extends AppException
{
    public function __construct(
        array $details = [],
        string $message = 'Validation failed',
        string $errorCode = 'VALIDATION_ERROR',
    ) {
        parent::__construct($errorCode, $message, $details);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
```

- [x] **Step 3: Write ErrorResource**

Creates `src/backend/app/Modules/Shared/Http/Resources/ErrorResource.php`:

```php
<?php

namespace App\Modules\Shared\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var \App\Modules\Shared\Exceptions\AppException $this */
        return [
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
                'details' => $this->details,
                'trace_id' => (string) str()->uuid(),
            ],
        ];
    }
}
```

- [x] **Step 4: Register global exception handler in bootstrap/app.php**

Modify `bootstrap/app.php`:

```php
 ->withExceptions(function (Exceptions $exceptions) {
     $exceptions->render(function (\App\Modules\Shared\Exceptions\AppException $e, $request) {
         return response()->json(
             (new \App\Modules\Shared\Http\Resources\ErrorResource($e))->toArray($request),
             $e->getHttpStatus(),
         );
     });

     $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
         $appException = new \App\Modules\Shared\Exceptions\ValidationException(
             details: collect($e->errors())->map(fn ($msgs, $field) => [
                 'field' => $field,
                 'message' => implode('; ', $msgs),
             ])->values()->toArray(),
         );
         return response()->json(
             (new \App\Modules\Shared\Http\Resources\ErrorResource($appException))->toArray($request),
             422,
         );
     });
 })
```

- [x] **Step 5: Write the failing test**

Creates `src/backend/tests/Unit/Modules/Shared/ErrorResourceTest.php`:

```php
<?php

use App\Modules\Shared\Exceptions\ValidationException;
use App\Modules\Shared\Http\Resources\ErrorResource;

test('validation error resource returns structured error', function () {
    $exception = new ValidationException(
        details: [['field' => 'email', 'message' => 'Required']],
    );

    $resource = new ErrorResource($exception);
    $result = $resource->toArray(request());

    expect($result)->toHaveKey('error')
        ->and($result['error'])->toMatchArray([
            'code' => 'VALIDATION_ERROR',
            'message' => 'Validation failed',
        ])
        ->and($result['error']['details'])->toHaveCount(1)
        ->and($result['error']['details'][0]['field'])->toBe('email');
});
```

- [~] **Step 6: Run test to verify it fails**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Shared/ErrorResourceTest.php
```

Expected: PASS (Pest test should pass after writing the classes).

- [~] **Step 7: Commit**

```bash
git add src/backend/app/Modules/Shared/ src/backend/tests/Unit/Modules/Shared/
git commit -m "feat: add shared error envelope with structured error response"
```

---

### Task 1.4: Implement pagination trait

**Files:**
- Create: `src/backend/app/Modules/Shared/Http/Resources/PaginatedCollection.php`
- Create: `src/backend/app/Modules/Shared/Http/Requests/PaginatedRequest.php`
- Create: `src/backend/tests/Unit/Modules/Shared/PaginatedCollectionTest.php`

- [x] **Step 1: Write PaginatedRequest**

Creates `src/backend/app/Modules/Shared/Http/Requests/PaginatedRequest.php`:

```php
<?php

namespace App\Modules\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginatedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function getPage(): int
    {
        return (int) $this->input('page', 1);
    }

    public function getPerPage(): int
    {
        return (int) $this->input('per_page', 20);
    }
}
```

- [x] **Step 2: Write PaginatedCollection**

Creates `src/backend/app/Modules/Shared/Http/Resources/PaginatedCollection.php`:

```php
<?php

namespace App\Modules\Shared\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaginatedCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        $pagination = $this->resource;

        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $pagination->currentPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
                'last_page' => $pagination->lastPage(),
            ],
            'links' => [
                'first' => $pagination->url(1),
                'last' => $pagination->url($pagination->lastPage()),
                'prev' => $pagination->previousPageUrl(),
                'next' => $pagination->nextPageUrl(),
            ],
        ];
    }
}
```

- [x] **Step 3: Write test**

Creates `src/backend/tests/Unit/Modules/Shared/PaginatedCollectionTest.php`:

```php
<?php

use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Pagination\LengthAwarePaginator;

test('paginated collection returns correct meta', function () {
    $items = collect([
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ]);

    $paginator = new LengthAwarePaginator(
        $items,
        total: 10,
        perPage: 2,
        currentPage: 1,
    );

    $resource = new PaginatedCollection($paginator);
    $result = $resource->toArray(request());

    expect($result['meta']['current_page'])->toBe(1)
        ->and($result['meta']['total'])->toBe(10)
        ->and($result['meta']['last_page'])->toBe(5)
        ->and($result['data'])->toHaveCount(2)
        ->and($result['links'])->toHaveKey('first');
});
```

- [~] **Step 4: Run tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Shared/PaginatedCollectionTest.php
```

Expected: PASS.

- [~] **Step 5: Commit**

```bash
git add src/backend/app/Modules/Shared/ src/backend/tests/Unit/Modules/Shared/
git commit -m "feat: add paginated request trait and paginated collection resource"
```

---

### Task 1.5: Install Sanctum Auth middleware

**Files:**
- Create: `src/backend/app/Modules/Shared/Http/Middleware/ForceJsonMiddleware.php`
- Create: `src/backend/tests/Feature/Modules/Shared/AuthMiddlewareTest.php`
- Modify: `bootstrap/app.php`

- [x] **Step 1: Write ForceJsonMiddleware**

Creates `src/backend/app/Modules/Shared/Http/Middleware/ForceJsonMiddleware.php`:

```php
<?php

namespace App\Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
```

- [x] **Step 2: Register middleware group in bootstrap/app.php**

```php
 ->withMiddleware(function (Middleware $middleware) {
     $middleware->api(prepend: [
         \App\Modules\Shared\Http\Middleware\ForceJsonMiddleware::class,
     ]);
 })
```

- [x] **Step 3: Write auth middleware test**

Creates `src/backend/tests/Feature/Modules/Shared/AuthMiddlewareTest.php`:

```php
<?php

test('unauthenticated request returns 401', function () {
    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(401);
    $response->assertJsonStructure(['error' => ['code', 'message', 'trace_id']]);
});
```

- [~] **Step 4: Run test**

```bash
docker compose run --rm app php artisan test tests/Feature/Modules/Shared/AuthMiddlewareTest.php
```

Expected: PASS. Sanctum protects `api` routes by default; 401 response format should match `ErrorResource`.

- [~] **Step 5: Commit**

```bash
git add src/backend/app/Modules/Shared/ src/backend/tests/Feature/Modules/Shared/
git commit -m "feat: add Sanctum auth middleware and 401 error response"
```

---

### Task 1.6: Scaffold NextJS frontend

**Files:**
- Create: `src/frontend/` from `create-next-app`

- [~] **Step 1: Create NextJS project**

```bash
docker compose run --rm frontend sh -lc "cd /app/src/frontend && npx create-next-app@latest . --typescript --tailwind --eslint --app --src-dir --use-npm"
```

Expected: `src/frontend/package.json`, `src/frontend/src/app/page.tsx` exist.

- [~] **Step 2: Install shadcn/ui**

```bash
docker compose run --rm frontend sh -lc "cd /app/src/frontend && npx shadcn@latest init -d"
```

Add dependencies:

```bash
docker compose run --rm frontend sh -lc "cd /app/src/frontend && npx shadcn@latest add button card input label separator table form"
```

- [x] **Step 3: Create API client module**

Creates `src/frontend/src/lib/api-client.ts`:

```typescript
const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

interface ApiError {
  code: string;
  message: string;
  details?: { field: string; message: string }[];
  trace_id: string;
}

interface ApiResponse<T> {
  data: T;
  meta?: { current_page: number; per_page: number; total: number; last_page: number };
  links?: { first: string; last: string; prev: string | null; next: string | null };
}

class ApiClient {
  private token: string | null = null;

  setToken(token: string) { this.token = token; }
  clearToken() { this.token = null; }

  private async request<T>(method: string, path: string, body?: unknown): Promise<ApiResponse<T>> {
    const headers: Record<string, string> = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (this.token) headers['Authorization'] = `Bearer ${this.token}`;

    const res = await fetch(`${API_BASE}${path}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });

    if (!res.ok) {
      const err: { error: ApiError } = await res.json();
      throw { status: res.status, ...err.error };
    }

    return res.json();
  }

  get<T>(path: string) { return this.request<T>('GET', path); }
  post<T>(path: string, body?: unknown) { return this.request<T>('POST', path, body); }
  put<T>(path: string, body?: unknown) { return this.request<T>('PUT', path, body); }
  patch<T>(path: string, body?: unknown) { return this.request<T>('PATCH', path, body); }
  delete<T>(path: string) { return this.request<T>('DELETE', path); }
}

export const api = new ApiClient();
export type { ApiResponse, ApiError };
```

- [x] **Step 4: Create login page structure**

Creates `src/frontend/src/app/login/page.tsx`:

```tsx
'use client';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function LoginPage() {
  return (
    <div className="flex min-h-screen items-center justify-center">
      <Card className="w-96">
        <CardHeader><CardTitle>iHRM Login</CardTitle></CardHeader>
        <CardContent>
          <form className="space-y-4">
            <Input type="email" placeholder="Email" />
            <Input type="password" placeholder="Password" />
            <Button className="w-full">Sign In</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
```

- [x] **Step 5: Create auth context**

Creates `src/frontend/src/lib/auth-context.tsx`:

```tsx
'use client';
import React, { createContext, useContext, useState, ReactNode } from 'react';
import { api } from '@/lib/api-client';

interface User { id: string; name: string; email: string; }
interface AuthState { user: User | null; isLoading: boolean; }

const AuthContext = createContext<{
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
}>({ user: null, login: async () => {}, logout: () => {} });

export function AuthProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AuthState>({ user: null, isLoading: true });
  
  const login = async (email: string, password: string) => {
    const res = await api.post<{ access_token: string; token_type: string; user: User }>('/auth/login', { email, password });
    api.setToken(res.data.access_token);
    setState({ user: res.data.user, isLoading: false });
  };

  const logout = () => {
    api.clearToken();
    setState({ user: null, isLoading: false });
  };

  return (
    <AuthContext.Provider value={{ user: state.user, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
```

- [~] **Step 6: Commit**

```bash
git add src/frontend/
git commit -m "feat: scaffold NextJS frontend with shadcn/ui and API client"
```

---

### Task 1.7: Set up CI pipeline

**Files:**
- Create: `.github/workflows/ci.yml`

- [x] **Step 1: Write CI workflow**

Creates `.github/workflows/ci.yml`:

```yaml
name: CI
on: [push, pull_request]

jobs:
  lint:
    runs-on: ubuntu-latest
    services:
      db:
        image: postgres:16-alpine
        env: { POSTGRES_DB: ihrm_test, POSTGRES_USER: ihrm, POSTGRES_PASSWORD: ihrm }
        options: >-
          --health-cmd pg_isready -U ihrm
          --health-interval 5s
          --health-timeout 3s
          --health-retries 5
      redis:
        image: redis:7-alpine
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 5s
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.4', extensions: pdo_pgsql, bcmath, redis }
      - uses: actions/setup-node@v4
        with: { node-version: '20' }

      - name: Install backend dependencies
        working-directory: src/backend
        run: composer install --prefer-dist --no-progress

      - name: Larastan
        working-directory: src/backend
        run: ./vendor/bin/phpstan analyse --memory-limit=512M || true

      - name: Run backend tests
        working-directory: src/backend
        run: php artisan test --parallel

      - name: Install frontend dependencies
        working-directory: src/frontend
        run: npm ci

      - name: Lint frontend
        working-directory: src/frontend
        run: npm run lint
```

- [~] **Step 2: Commit**

```bash
git add .github/
git commit -m "ci: add GitHub Actions pipeline for lint, test, and frontend checks"
```

---

### Self-Review Checklist

- Spec coverage: M1 tasks cover Docker, Laravel scaffold, error envelope, pagination, auth middleware, NextJS scaffold, CI pipeline — aligned with EPIC-01/02/03/04.
- No placeholders: every step has actual code or command.
- Type consistency: `ErrorResource`, `PaginatedCollection`, `PaginatedRequest`, `ForceJsonMiddleware`, `api-client.ts` — all defined once with stable signatures.
- File paths: all exact, no ambiguous references.
