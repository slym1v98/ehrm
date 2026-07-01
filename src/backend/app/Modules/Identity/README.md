# Identity Module

Phase 1 Identity & Access module for iHRM.

## Scope

- Sanctum bearer-token authentication
- User account management
- Role management
- Seed-owned permission catalog
- Role-permission assignment
- User-role assignment
- Data-scope assignment foundation
- `permission:{code}` middleware

## Seed data

```bash
php artisan migrate:fresh --seed
```

Defaults:

- Admin email: `admin@ihrm.local`
- Admin password: `password`
- Role: `SUPER_ADMIN`
- Scope: `all_company`

Override defaults:

```env
IHRM_ADMIN_EMAIL=admin@ihrm.local
IHRM_ADMIN_PASSWORD=password
```

## Main endpoints

```text
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me
POST   /api/v1/auth/change-password

GET    /api/v1/users
POST   /api/v1/users
GET    /api/v1/users/{id}
PATCH  /api/v1/users/{id}
POST   /api/v1/users/{id}/disable
POST   /api/v1/users/{id}/reactivate
POST   /api/v1/users/{id}/roles
DELETE /api/v1/users/{id}/roles/{roleId}

GET    /api/v1/roles
POST   /api/v1/roles
GET    /api/v1/roles/{id}
PATCH  /api/v1/roles/{id}
POST   /api/v1/roles/{id}/activate
POST   /api/v1/roles/{id}/deactivate
POST   /api/v1/roles/{id}/permissions
DELETE /api/v1/roles/{id}/permissions/{code}

GET    /api/v1/permissions
```

## Testing

```bash
php artisan test tests/Unit/Modules/Identity tests/Feature/Modules/Identity
php artisan test
```

## Notes

- Permissions are version-controlled seed data. Runtime API is read-only.
- Token payload contains identity only. Permissions/data scopes resolve server-side per request.
- Branch/department IDs in data scopes are UUID references without DB FKs until Organization module exists.
- Domain events are emitted by aggregates and dispatched by repositories; Audit listener lands in later sub-project.
