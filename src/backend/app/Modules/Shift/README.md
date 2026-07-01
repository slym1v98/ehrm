# Shift Module

Phase 2 Shift BC for iHRM Workforce Operations.

## Aggregates

- **ShiftTemplate** — reusable shift definition with overnight detection, overtime/flexibility rules as JSON VOs.
- **ShiftAssignment** — assigns template to employee or department with effective date range and recurrence.

## API Endpoints

### ShiftTemplates
- `GET|POST /api/v1/shift-templates`
- `GET|PATCH /api/v1/shift-templates/{id}`
- `POST /shift-templates/{id}/activate|deactivate`

### ShiftAssignments
- `POST /api/v1/shift-assignments`
- `PATCH /api/v1/shift-assignments/{id}`
- `POST /shift-assignments/{id}/end`
- `GET /api/v1/employees/{id}/shifts`
- `GET /api/v1/departments/{id}/shifts`

## Permissions
- `shift.template.view`, `shift.template.create`, `shift.template.update`

Granted to `SUPER_ADMIN` and `HR_MANAGER`.

## Tests

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Shift --compact
docker compose run --rm app php artisan test tests/Feature/Modules/Shift --compact
```
