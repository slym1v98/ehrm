<?php

namespace App\Modules\Employee\Infrastructure\Persistence\Repositories;

use App\Modules\Employee\Domain\Aggregates\Employee\Address;
use App\Modules\Employee\Domain\Aggregates\Employee\Employee;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeCode;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeStatus;
use App\Modules\Employee\Domain\Aggregates\Employee\PersonalName;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Employee\Infrastructure\Persistence\Eloquent\EmployeeModel;
use Illuminate\Support\Facades\Event;

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(private EmployeeModel $model) {}

    public function findById(EmployeeId $id): ?Employee
    {
        $record = $this->model->find($id->value);
        return $record ? $this->toDomain($record) : null;
    }

    public function findByCode(string $code): ?Employee
    {
        $record = $this->model->where('employee_code', $code)->first();
        return $record ? $this->toDomain($record) : null;
    }

    public function findByUserId(string $userId): ?Employee
    {
        $record = $this->model->where('user_id', $userId)->first();
        return $record ? $this->toDomain($record) : null;
    }

    public function findAllPaginated(int $page, int $perPage = 15): array
    {
        return $this->model->query()->paginate($perPage, ['*'], 'page', $page)->items();
    }

    public function existsByCode(string $code): bool
    {
        return $this->model->where('employee_code', $code)->exists();
    }

    public function save(Employee $employee): void
    {
        $address = $employee->address();

        $this->model->updateOrCreate(
            ['id' => $employee->id()->value],
            [
                'employee_code' => $employee->code()->value,
                'first_name' => $employee->name()->firstName,
                'last_name' => $employee->name()->lastName,
                'dob' => $employee->dob()?->format('Y-m-d'),
                'gender' => $employee->gender(),
                'personal_email' => $employee->personalEmail(),
                'phone' => $employee->phone(),
                'address_street' => $address?->street,
                'address_city' => $address?->city,
                'address_province' => $address?->province,
                'address_postal_code' => $address?->postalCode,
                'address_country' => $address?->country,
                'status' => $employee->status()->value,
                'manager_id' => $employee->managerId()?->value,
                'branch_id' => $employee->branchId(),
                'department_id' => $employee->departmentId(),
                'position_id' => $employee->positionId(),
                'user_id' => $employee->userId(),
            ]
        );
    }

    public function saveAndDispatch(Employee $employee): void
    {
        $this->save($employee);
        foreach ($employee->releaseEvents() as $event) {
            Event::dispatch($event);
        }
    }

    private function toDomain(EmployeeModel $record): Employee
    {
        return Employee::reconstitute(
            EmployeeId::fromString($record->id),
            EmployeeCode::fromString($record->employee_code),
            PersonalName::of($record->first_name, $record->last_name),
            $record->dob ? new \DateTimeImmutable($record->dob->format('Y-m-d')) : null,
            $record->gender,
            $record->personal_email,
            $record->phone,
            ($record->address_street || $record->address_city || $record->address_province || $record->address_postal_code || $record->address_country)
                ? new Address($record->address_street, $record->address_city, $record->address_province, $record->address_postal_code, $record->address_country)
                : null,
            EmployeeStatus::from($record->status),
            $record->manager_id ? EmployeeId::fromString($record->manager_id) : null,
            $record->branch_id,
            $record->department_id,
            $record->position_id,
            $record->user_id,
            [],
        );
    }
}
