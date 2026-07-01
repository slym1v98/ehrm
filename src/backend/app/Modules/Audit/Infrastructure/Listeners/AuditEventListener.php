<?php

namespace App\Modules\Audit\Infrastructure\Listeners;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Identity\Domain\Events\RoleCreated;
use App\Modules\Identity\Domain\Events\RolePermissionGranted;
use App\Modules\Identity\Domain\Events\RolePermissionRevoked;
use App\Modules\Identity\Domain\Events\RoleUpdated;
use App\Modules\Identity\Domain\Events\UserCreated;
use App\Modules\Identity\Domain\Events\UserDataScopeGranted;
use App\Modules\Identity\Domain\Events\UserDisabled;
use App\Modules\Identity\Domain\Events\UserLoggedIn;
use App\Modules\Identity\Domain\Events\UserLoginFailed;
use App\Modules\Identity\Domain\Events\UserPasswordChanged;
use App\Modules\Identity\Domain\Events\UserReactivated;
use App\Modules\Identity\Domain\Events\UserRoleAssigned;
use App\Modules\Identity\Domain\Events\UserRoleRevoked;
use App\Modules\Organization\Domain\Events\BranchActivated;
use App\Modules\Organization\Domain\Events\BranchCreated;
use App\Modules\Organization\Domain\Events\BranchDeactivated;
use App\Modules\Organization\Domain\Events\BranchUpdated;
use App\Modules\Organization\Domain\Events\DepartmentActivated;
use App\Modules\Organization\Domain\Events\DepartmentCreated;
use App\Modules\Organization\Domain\Events\DepartmentDeactivated;
use App\Modules\Organization\Domain\Events\DepartmentMoved;
use App\Modules\Organization\Domain\Events\DepartmentUpdated;
use App\Modules\Organization\Domain\Events\PositionActivated;
use App\Modules\Organization\Domain\Events\PositionCreated;
use App\Modules\Organization\Domain\Events\PositionDeactivated;
use App\Modules\Organization\Domain\Events\PositionUpdated;
use App\Modules\Employee\Domain\Events\ContractActivated;
use App\Modules\Employee\Domain\Events\ContractCreated;
use App\Modules\Employee\Domain\Events\ContractExpired;
use App\Modules\Employee\Domain\Events\ContractRenewed;
use App\Modules\Employee\Domain\Events\ContractTerminated;
use App\Modules\Employee\Domain\Events\EmployeeCreated;
use App\Modules\Employee\Domain\Events\EmployeeDocumentArchived;
use App\Modules\Employee\Domain\Events\EmployeeDocumentExpired;
use App\Modules\Employee\Domain\Events\EmployeeDocumentReplaced;
use App\Modules\Employee\Domain\Events\EmployeeDocumentUploaded;
use App\Modules\Employee\Domain\Events\EmployeeEmploymentChanged;
use App\Modules\Employee\Domain\Events\EmployeeManagerChanged;
use App\Modules\Employee\Domain\Events\EmployeePersonalInfoUpdated;
use App\Modules\Employee\Domain\Events\EmployeeStatusChanged;
use App\Modules\Configuration\Domain\Events\LookupValueChanged;
use App\Modules\Configuration\Domain\Events\CodeGenerationRuleChanged;
use App\Modules\Configuration\Domain\Events\SystemSettingChanged;
use Illuminate\Support\Facades\Log;

class AuditEventListener
{
    public function __construct(private AuditLogger $logger) {}

    public function handle(object $event): void
    {
        $data = $this->map($event);
        if ($data === null) {
            return;
        }

        try {
            $this->logger->log(
                action: $data['action'],
                module: $data['module'],
                entityType: $data['entity_type'],
                entityId: $data['entity_id'],
                actorUserId: $data['actor_user_id'] ?? (auth()->id() ? (string) auth()->id() : null),
                beforePayload: $data['before_payload'] ?? null,
                afterPayload: $data['after_payload'] ?? null,
                result: $data['result'],
                occurredAt: $event->occurredAt ?? now(),
                ipAddress: request()?->ip(),
                userAgent: request()?->userAgent(),
            );
        } catch (\Throwable $exception) {
            Log::warning('Audit write failed.', ['event' => $event::class, 'message' => $exception->getMessage()]);
        }
    }

    private function map(object $event): ?array
    {
        return match ($event::class) {
            UserCreated::class => ['module' => 'identity', 'action' => 'created', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success', 'after_payload' => ['email' => (string) $event->email]],
            UserLoggedIn::class => ['module' => 'identity', 'action' => 'login', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserLoginFailed::class => ['module' => 'identity', 'action' => 'login_failed', 'entity_type' => 'user', 'entity_id' => null, 'result' => 'failure', 'after_payload' => ['email' => (string) $event->email, 'reason' => $event->reason]],
            UserDisabled::class => ['module' => 'identity', 'action' => 'disabled', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserReactivated::class => ['module' => 'identity', 'action' => 'reactivated', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserPasswordChanged::class => ['module' => 'identity', 'action' => 'password_changed', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserRoleAssigned::class => ['module' => 'identity', 'action' => 'role_assigned', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'actor_user_id' => $event->assignedBy ? (string) $event->assignedBy : null, 'result' => 'success', 'after_payload' => ['role_id' => (string) $event->roleId]],
            UserRoleRevoked::class => ['module' => 'identity', 'action' => 'role_revoked', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success', 'after_payload' => ['role_id' => (string) $event->roleId]],
            UserDataScopeGranted::class => ['module' => 'identity', 'action' => 'data_scope_granted', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success', 'after_payload' => ['scope_type' => $event->scope->type->value]],
            RoleCreated::class => ['module' => 'identity', 'action' => 'created', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success', 'after_payload' => ['code' => (string) $event->code]],
            RoleUpdated::class => ['module' => 'identity', 'action' => 'updated', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success'],
            RolePermissionGranted::class => ['module' => 'identity', 'action' => 'permission_granted', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success', 'after_payload' => ['permission_code' => (string) $event->code]],
            RolePermissionRevoked::class => ['module' => 'identity', 'action' => 'permission_revoked', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success', 'after_payload' => ['permission_code' => (string) $event->code]],
            BranchCreated::class => ['module' => 'organization', 'action' => 'created', 'entity_type' => 'branch', 'entity_id' => (string) $event->branchId, 'result' => 'success'],
            BranchUpdated::class => ['module' => 'organization', 'action' => 'updated', 'entity_type' => 'branch', 'entity_id' => (string) $event->branchId, 'result' => 'success'],
            BranchActivated::class => ['module' => 'organization', 'action' => 'activated', 'entity_type' => 'branch', 'entity_id' => (string) $event->branchId, 'result' => 'success'],
            BranchDeactivated::class => ['module' => 'organization', 'action' => 'deactivated', 'entity_type' => 'branch', 'entity_id' => (string) $event->branchId, 'result' => 'success'],
            DepartmentCreated::class => ['module' => 'organization', 'action' => 'created', 'entity_type' => 'department', 'entity_id' => (string) $event->departmentId, 'result' => 'success'],
            DepartmentUpdated::class => ['module' => 'organization', 'action' => 'updated', 'entity_type' => 'department', 'entity_id' => (string) $event->departmentId, 'result' => 'success'],
            DepartmentMoved::class => ['module' => 'organization', 'action' => 'moved', 'entity_type' => 'department', 'entity_id' => (string) $event->departmentId, 'result' => 'success'],
            DepartmentActivated::class => ['module' => 'organization', 'action' => 'activated', 'entity_type' => 'department', 'entity_id' => (string) $event->departmentId, 'result' => 'success'],
            DepartmentDeactivated::class => ['module' => 'organization', 'action' => 'deactivated', 'entity_type' => 'department', 'entity_id' => (string) $event->departmentId, 'result' => 'success'],
            PositionCreated::class => ['module' => 'organization', 'action' => 'created', 'entity_type' => 'position', 'entity_id' => (string) $event->positionId, 'result' => 'success'],
            PositionUpdated::class => ['module' => 'organization', 'action' => 'updated', 'entity_type' => 'position', 'entity_id' => (string) $event->positionId, 'result' => 'success'],
            PositionActivated::class => ['module' => 'organization', 'action' => 'activated', 'entity_type' => 'position', 'entity_id' => (string) $event->positionId, 'result' => 'success'],
            PositionDeactivated::class => ['module' => 'organization', 'action' => 'deactivated', 'entity_type' => 'position', 'entity_id' => (string) $event->positionId, 'result' => 'success'],
            EmployeeCreated::class => ['module' => 'employee', 'action' => 'created', 'entity_type' => 'employee', 'entity_id' => (string) $event->employeeId, 'result' => 'success'],
            EmployeePersonalInfoUpdated::class => ['module' => 'employee', 'action' => 'personal_info_updated', 'entity_type' => 'employee', 'entity_id' => (string) $event->employeeId, 'result' => 'success'],
            EmployeeEmploymentChanged::class => ['module' => 'employee', 'action' => 'employment_changed', 'entity_type' => 'employee', 'entity_id' => (string) $event->employeeId, 'result' => 'success'],
            EmployeeManagerChanged::class => ['module' => 'employee', 'action' => 'manager_changed', 'entity_type' => 'employee', 'entity_id' => (string) $event->employeeId, 'result' => 'success'],
            EmployeeStatusChanged::class => ['module' => 'employee', 'action' => 'status_changed', 'entity_type' => 'employee', 'entity_id' => (string) $event->employeeId, 'result' => 'success'],
            ContractCreated::class => ['module' => 'employee', 'action' => 'created', 'entity_type' => 'contract', 'entity_id' => (string) $event->contractId, 'result' => 'success'],
            ContractActivated::class => ['module' => 'employee', 'action' => 'activated', 'entity_type' => 'contract', 'entity_id' => (string) $event->contractId, 'result' => 'success'],
            ContractRenewed::class => ['module' => 'employee', 'action' => 'renewed', 'entity_type' => 'contract', 'entity_id' => (string) $event->newContractId, 'result' => 'success'],
            ContractExpired::class => ['module' => 'employee', 'action' => 'expired', 'entity_type' => 'contract', 'entity_id' => (string) $event->contractId, 'result' => 'success'],
            ContractTerminated::class => ['module' => 'employee', 'action' => 'terminated', 'entity_type' => 'contract', 'entity_id' => (string) $event->contractId, 'result' => 'success'],
            EmployeeDocumentUploaded::class => ['module' => 'employee', 'action' => 'uploaded', 'entity_type' => 'employee_document', 'entity_id' => (string) $event->documentId, 'result' => 'success'],
            EmployeeDocumentReplaced::class => ['module' => 'employee', 'action' => 'replaced', 'entity_type' => 'employee_document', 'entity_id' => (string) $event->documentId, 'result' => 'success'],
            EmployeeDocumentExpired::class => ['module' => 'employee', 'action' => 'expired', 'entity_type' => 'employee_document', 'entity_id' => (string) $event->documentId, 'result' => 'success'],
            EmployeeDocumentArchived::class => ['module' => 'employee', 'action' => 'archived', 'entity_type' => 'employee_document', 'entity_id' => (string) $event->documentId, 'result' => 'success'],
            LookupValueChanged::class => ['module' => 'configuration', 'action' => $event->action, 'entity_type' => 'lookup_value', 'entity_id' => $event->valueId, 'result' => 'success'],
            CodeGenerationRuleChanged::class => ['module' => 'configuration', 'action' => $event->action, 'entity_type' => 'code_generation_rule', 'entity_id' => $event->ruleId, 'result' => 'success'],
            SystemSettingChanged::class => ['module' => 'configuration', 'action' => $event->action, 'entity_type' => 'system_setting', 'entity_id' => $event->settingId, 'result' => 'success'],
            default => null,
        };
    }
}
