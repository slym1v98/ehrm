<?php

namespace App\Providers;

use App\Modules\Audit\Infrastructure\Listeners\AuditEventListener;
use App\Modules\Configuration\Domain\Repositories\CodeGenerationRuleRepositoryInterface;
use App\Modules\Configuration\Domain\Repositories\HolidayCalendarRepositoryInterface;
use App\Modules\Configuration\Domain\Repositories\LookupRepositoryInterface;
use App\Modules\Configuration\Domain\Repositories\NotificationThresholdRepositoryInterface;
use App\Modules\Configuration\Domain\Repositories\SystemSettingRepositoryInterface;
use App\Modules\Configuration\Infrastructure\Persistence\Repositories\EloquentCodeGenerationRuleRepository;
use App\Modules\Configuration\Infrastructure\Persistence\Repositories\EloquentHolidayCalendarRepository;
use App\Modules\Configuration\Infrastructure\Persistence\Repositories\EloquentLookupRepository;
use App\Modules\Configuration\Infrastructure\Persistence\Repositories\EloquentNotificationThresholdRepository;
use App\Modules\Configuration\Infrastructure\Persistence\Repositories\EloquentSystemSettingRepository;
use App\Modules\Employee\Domain\Repositories\ContractRepositoryInterface;
use App\Modules\Employee\Domain\Repositories\EmployeeDocumentRepositoryInterface;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Employee\Infrastructure\Persistence\Repositories\EloquentContractRepository;
use App\Modules\Employee\Infrastructure\Persistence\Repositories\EloquentEmployeeDocumentRepository;
use App\Modules\Employee\Infrastructure\Persistence\Repositories\EloquentEmployeeRepository;
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
use App\Modules\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Organization\Domain\Repositories\BranchRepositoryInterface;
use App\Modules\Organization\Domain\Repositories\DepartmentRepositoryInterface;
use App\Modules\Organization\Domain\Repositories\PositionRepositoryInterface;
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
use App\Modules\Organization\Infrastructure\Persistence\Repositories\EloquentBranchRepository;
use App\Modules\Organization\Infrastructure\Persistence\Repositories\EloquentDepartmentRepository;
use App\Modules\Organization\Infrastructure\Persistence\Repositories\EloquentPositionRepository;
use App\Modules\Identity\Infrastructure\Persistence\Repositories\EloquentRoleRepository;
use App\Modules\Identity\Infrastructure\Persistence\Repositories\EloquentUserRepository;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(LookupRepositoryInterface::class, EloquentLookupRepository::class);
        $this->app->bind(CodeGenerationRuleRepositoryInterface::class, EloquentCodeGenerationRuleRepository::class);
        $this->app->bind(SystemSettingRepositoryInterface::class, EloquentSystemSettingRepository::class);
        $this->app->bind(HolidayCalendarRepositoryInterface::class, EloquentHolidayCalendarRepository::class);
        $this->app->bind(NotificationThresholdRepositoryInterface::class, EloquentNotificationThresholdRepository::class);
        $this->app->bind(BranchRepositoryInterface::class, EloquentBranchRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, EloquentDepartmentRepository::class);
        $this->app->bind(PositionRepositoryInterface::class, EloquentPositionRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->bind(ContractRepositoryInterface::class, EloquentContractRepository::class);
        $this->app->bind(EmployeeDocumentRepositoryInterface::class, EloquentEmployeeDocumentRepository::class);
    }

    public function boot(): void
    {
        foreach ([
            UserCreated::class,
            UserLoggedIn::class,
            UserLoginFailed::class,
            UserDisabled::class,
            UserReactivated::class,
            UserPasswordChanged::class,
            UserRoleAssigned::class,
            UserRoleRevoked::class,
            UserDataScopeGranted::class,
            RoleCreated::class,
            RoleUpdated::class,
            RolePermissionGranted::class,
            RolePermissionRevoked::class,
            App\Modules\Configuration\Domain\Events\LookupValueChanged::class,
            App\Modules\Configuration\Domain\Events\CodeGenerationRuleChanged::class,
            App\Modules\Configuration\Domain\Events\SystemSettingChanged::class,
            BranchCreated::class,
            BranchUpdated::class,
            BranchActivated::class,
            BranchDeactivated::class,
            DepartmentCreated::class,
            DepartmentUpdated::class,
            DepartmentMoved::class,
            DepartmentActivated::class,
            DepartmentDeactivated::class,
            PositionCreated::class,
            PositionUpdated::class,
            PositionActivated::class,
            PositionDeactivated::class,
            EmployeeCreated::class,
            EmployeePersonalInfoUpdated::class,
            EmployeeEmploymentChanged::class,
            EmployeeManagerChanged::class,
            EmployeeStatusChanged::class,
            ContractCreated::class,
            ContractActivated::class,
            ContractRenewed::class,
            ContractExpired::class,
            ContractTerminated::class,
            EmployeeDocumentUploaded::class,
            EmployeeDocumentReplaced::class,
            EmployeeDocumentExpired::class,
            EmployeeDocumentArchived::class,
        ] as $event) {
            Event::listen($event, AuditEventListener::class);
        }
    }
}
