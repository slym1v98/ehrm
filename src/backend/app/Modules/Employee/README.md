# Employee Module

Phase 1 Employee module for iHRM.

## Aggregates

- `Employee`: master profile, status lifecycle, manager/user/org references.
- `Contract`: contract lifecycle and renewal chain.
- `EmployeeDocument`: document metadata with private MinIO file storage.

## Routes

All routes are under `/api/v1` and protected by Sanctum + permission middleware.

- `GET|POST /employees`
- `GET /employees/{id}`
- `PATCH /employees/{id}/personal-info`
- `PATCH /employees/{id}/employment`
- `PATCH /employees/{id}/manager`
- `PATCH /employees/{id}/status`
- `POST /employees/{id}/link-user`
- `GET|POST /employees/{id}/contracts`
- `POST /contracts/{id}/activate|renew|terminate`
- `GET|POST /employees/{id}/documents`
- `POST /documents/{id}/replace|archive`
- `GET /documents/{id}/download`

## Permissions

- `employee.view`, `employee.create`, `employee.update`, `employee.status.change`
- `employee.contract.view/create/activate/renew/terminate`
- `employee.document.view/upload/replace/archive/download`

`SUPER_ADMIN` and `HR_MANAGER` receive all `employee.*` permissions.

## MinIO

Uses Laravel filesystem disk `minio` backed by:

- `MINIO_ENDPOINT`
- `MINIO_ACCESS_KEY`
- `MINIO_SECRET_KEY`
- `MINIO_BUCKET`

Files are private. API responses never expose raw object URLs.

## Tests

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Employee --compact
docker compose run --rm app php artisan test tests/Feature/Modules/Employee --compact
```
