<?php

namespace App\Modules\Employee\Domain\Repositories;

use App\Modules\Employee\Domain\Aggregates\Contract\Contract;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;

interface ContractRepositoryInterface
{
    public function findById(ContractId $id): ?Contract;
    /** @return Contract[] */
    public function findByEmployeeId(EmployeeId $employeeId): array;
    /** @return Contract[] */
    public function findActiveByEmployeeId(EmployeeId $employeeId): array;
    public function findAllPaginated(int $page, int $perPage = 15, ?EmployeeId $employeeId = null): array;
    public function save(Contract $contract): void;
}
