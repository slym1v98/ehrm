<?php

namespace App\Modules\Payroll\Infrastructure\Http\Controllers;

use App\Modules\Identity\Application\Services\AuthorizationService;
use App\Modules\Payroll\Application\Commands\Payslip\PublishPayslipsCommand;
use App\Modules\Payroll\Application\CommandHandlers\Payslip\PublishPayslipsHandler;
use App\Modules\Payroll\Domain\Aggregates\Payslip\PayslipId;
use App\Modules\Payroll\Domain\Events\PayslipAccessed;
use App\Modules\Payroll\Domain\Repositories\PayslipRepositoryInterface;
use App\Modules\Payroll\Infrastructure\Http\Resources\PayslipResource;
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayslipModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController
{
    public function __construct(
        private PublishPayslipsHandler $publishHandler,
        private PayslipRepositoryInterface $payslipRepo,
        private AuthorizationService $authz,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = (string)$user->id;
        $query = PayslipModel::query();

        if (!$this->authz->userHasPermission($userId, 'payroll.payslip.view')) {
            $employeeId = $user->employee_id ?? null;
            if (!$employeeId) return response()->json(['data' => []]);
            $query->where('employee_id', $employeeId);
        }

        $payslips = $query->where('status', 'published')->orderByDesc('published_at')->paginate(20);
        return response()->json([
            'data' => $payslips->map(fn($m) => (new PayslipResource($m))->toArray($request)),
            'meta' => ['total' => $payslips->total(), 'per_page' => $payslips->perPage()],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $model = PayslipModel::findOrFail($id);
        $user = $request->user();
        $userId = (string)$user->id;

        if (!$this->authz->userHasPermission($userId, 'payroll.payslip.view')) {
            if (($user->employee_id ?? null) !== $model->employee_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $payslip = $this->payslipRepo->findById(PayslipId::fromString($id));
        if ($payslip) {
            $payslip->recordAccess();
            $this->payslipRepo->save($payslip);
            event(new PayslipAccessed($id, $model->employee_id, $userId));
        }

        return response()->json(['data' => new PayslipResource($model->fresh())]);
    }

    public function download(Request $request, string $id): JsonResponse
    {
        return $this->show($request, $id);
    }

    public function publish(Request $request, string $periodId): JsonResponse
    {
        $this->publishHandler->handle(new PublishPayslipsCommand($periodId, (string)$request->user()->id));
        return response()->json(['message' => 'Payslips published']);
    }
}
