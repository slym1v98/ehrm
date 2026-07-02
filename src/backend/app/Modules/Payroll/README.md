# Payroll Module

Phase 2 workforce operations. Calculates monthly payroll from attendance/leave/contract data, applies configurable component formulas, and publishes payslips.

## Aggregates
- **PayrollPeriod** — monthly lifecycle state machine (`open → calculating → completed → reviewing → approved → locked → published`)
- **PayrollComponent** — salary component catalog (base, allowance, bonus, penalty, overtime, deduction, insurance, tax, net) with hybrid `calculation_type` (`fixed_amount`, `percent_of_component`, `manual_entry`)
- **PayrollRun** — calculation run tracking (formula_version, error_summary)
- **PayrollEntry** — per-employee calculation result with contract/attendance/leave snapshots + line breakdown
- **PayrollAdjustment** — inline approval adjustments (add/subtract/override) with `pending → approved | rejected` transition
- **Payslip** — immutable published snapshot with access tracking

## Domain Services
- `PayrollFormulaEngine` — hybrid formula: fixed formula + component `calculation_type`
- `PayrollCalculator` — orchestrates full run per employee
- `TaxCalculator` — flat/bracket tax
- `InsuranceCalculator` — VN standard rates (social 8%, health 1.5%, unemployment 1%)
- `AttendanceBasisCalculator` — normalizes attendance port data

## Ports (read-only anti-corruption layer)
- `AttendanceReadPort` — reads `attendance_timesheets`
- `LeaveReadPort` — reads `leave_requests`
- `EmployeeContractReadPort` — reads `employee_contracts`, `employees`

## Setup
```bash
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan db:seed
```

## Testing
```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Payroll --compact
docker compose run --rm app php artisan test tests/Feature/Modules/Payroll --compact
```

## API endpoints (all under `/api/v1/`)
- `GET|POST /payroll/periods`
- `GET /payroll/periods/{id}`
- `POST /payroll/periods/{id}/{start-run|submit-approval|approve|reject|lock|reopen}`
- `POST /payroll/periods/{id}/publish` — creates payslips
- `GET /payroll/periods/{id}/entries` — paginated
- `GET /payroll/entries/{id}` — with lines
- `POST /payroll/entries/{id}/review`
- `GET|POST /payroll/entries/{entryId}/adjustments`
- `POST /payroll/adjustments/{id}/{approve|reject}`
- `GET /payroll/payslips` — self for employee, all for payroll role
- `GET /payroll/payslips/{id}`, `/download`
- `GET|POST|PATCH|DELETE /payroll/components`

## Permissions
`payroll.period.view`, `payroll.period.manage`, `payroll.run.start`, `payroll.entry.view`, `payroll.entry.review`, `payroll.adjustment.manage`, `payroll.approve`, `payroll.lock`, `payroll.publish`, `payroll.payslip.view`, `payroll.payslip.view_self`, `payroll.component.manage`
