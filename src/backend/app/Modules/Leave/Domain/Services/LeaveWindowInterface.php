<?php
namespace App\Modules\Leave\Domain\Services;
use Carbon\CarbonImmutable;
interface LeaveWindowInterface { public function getLeaveWindows(string $employeeId, CarbonImmutable $start, CarbonImmutable $end): array; }
