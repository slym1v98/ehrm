<?php

namespace App\Modules\Employee\Infrastructure\Persistence\Repositories;

use App\Modules\Employee\Domain\Aggregates\Contract\Contract;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractStatus;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractTerm;
use App\Modules\Employee\Domain\Aggregates\Contract\DateRange;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Repositories\ContractRepositoryInterface;
use App\Modules\Employee\Infrastructure\Persistence\Eloquent\ContractModel;

class EloquentContractRepository implements ContractRepositoryInterface
{
    public function __construct(private ContractModel $model) {}

    public function findById(ContractId $id): ?Contract
    {
        $record = $this->model->find($id->value);
        return $record ? $this->toDomain($record) : null;
    }

    /** @return Contract[] */
    public function findByEmployeeId(EmployeeId $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId->value)
            ->get()
            ->map(fn($r) => $this->toDomain($r))
            ->all();
    }

    /** @return Contract[] */
    public function findActiveByEmployeeId(EmployeeId $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId->value)
            ->where('status', 'active')
            ->get()
            ->map(fn($r) => $this->toDomain($r))
            ->all();
    }

    public function findAllPaginated(int $page, int $perPage = 15, ?EmployeeId $employeeId = null): array
    {
        $q = $this->model->query();
        if ($employeeId) {
            $q->where('employee_id', $employeeId->value);
        }
        return $q->paginate($perPage, ['*'], 'page', $page)->items();
    }

    public function save(Contract $contract): void
    {
        $this->model->updateOrCreate(
            ['id' => $contract->id()->value],
            [
                'employee_id' => $contract->employeeId()->value,
                'contract_number' => $contract->contractNumber(),
                'contract_type' => $contract->contractType(),
                'start_date' => $contract->term()->start->format('Y-m-d'),
                'end_date' => $contract->term()->end?->format('Y-m-d'),
                'sign_date' => $contract->signDate()?->format('Y-m-d'),
                'status' => $contract->status()->value,
                'predecessor_contract_id' => $contract->predecessorContractId()?->value,
                'base_salary' => $contract->baseSalary(),
                'position_id' => $contract->positionId(),
            ]
        );
    }

    private function toDomain(ContractModel $record): Contract
    {
        return Contract::reconstitute(
            ContractId::fromString($record->id),
            EmployeeId::fromString($record->employee_id),
            $record->contract_number,
            new ContractTerm(
                $record->contract_type,
                new DateRange(
                    new \DateTimeImmutable($record->start_date->format('Y-m-d')),
                    $record->end_date ? new \DateTimeImmutable($record->end_date->format('Y-m-d')) : null,
                ),
                $record->base_salary ? (float) $record->base_salary : null,
            ),
            ContractStatus::from($record->status),
            $record->predecessor_contract_id ? ContractId::fromString($record->predecessor_contract_id) : null,
            $record->sign_date ? new \DateTimeImmutable($record->sign_date->format('Y-m-d')) : null,
            $record->position_id,
        );
    }
}
