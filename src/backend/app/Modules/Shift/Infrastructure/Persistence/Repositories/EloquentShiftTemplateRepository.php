<?php

namespace App\Modules\Shift\Infrastructure\Persistence\Repositories;

use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\FlexibilityRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\OvertimeRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplate;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftWindow;
use App\Modules\Shift\Domain\Repositories\ShiftTemplateRepositoryInterface;
use App\Modules\Shift\Infrastructure\Persistence\Eloquent\ShiftTemplateModel;
use Illuminate\Support\Facades\Event;

class EloquentShiftTemplateRepository implements ShiftTemplateRepositoryInterface
{
    public function __construct(private ShiftTemplateModel $model) {}

    public function findById(ShiftTemplateId $id): ?ShiftTemplate
    {
        $record = $this->model->find($id->value);
        return $record ? $this->toDomain($record) : null;
    }

    public function findByCode(string $code): ?ShiftTemplate
    {
        $record = $this->model->where('code', $code)->first();
        return $record ? $this->toDomain($record) : null;
    }

    public function existsByCode(string $code): bool
    {
        return $this->model->where('code', $code)->exists();
    }

    public function findAllPaginated(int $page, int $perPage = 15): array
    {
        return $this->model->query()->orderBy('name')->paginate($perPage, ['*'], 'page', $page)->items();
    }

    public function saveAndDispatch(ShiftTemplate $template): void
    {
        $this->save($template);
        foreach ($template->releaseEvents() as $event) {
            Event::dispatch($event);
        }
    }

    private function save(ShiftTemplate $template): void
    {
        $this->model->updateOrCreate(
            ['id' => $template->id()->value],
            [
                'code' => $template->code(),
                'name' => $template->name(),
                'start_time' => $template->shiftWindow()->start,
                'end_time' => $template->shiftWindow()->end,
                'is_overnight' => $template->shiftWindow()->isOvernight,
                'break_minutes' => $template->breakMinutes(),
                'late_tolerance_minutes' => $template->lateToleranceMinutes(),
                'overtime_rules' => [
                    'minOvertimeThreshold' => $template->overtimeRules()->minOvertimeThreshold,
                    'roundingInterval' => $template->overtimeRules()->roundingInterval,
                    'graceMinutes' => $template->overtimeRules()->graceMinutes,
                    'beforeShiftAllowance' => $template->overtimeRules()->beforeShiftAllowance,
                    'afterShiftAllowance' => $template->overtimeRules()->afterShiftAllowance,
                ],
                'flexibility_rules' => [
                    'maxEarlyArrival' => $template->flexibilityRules()->maxEarlyArrival,
                    'maxLateDeparture' => $template->flexibilityRules()->maxLateDeparture,
                    'coreStart' => $template->flexibilityRules()->coreStart,
                    'coreEnd' => $template->flexibilityRules()->coreEnd,
                ],
                'payroll_attribution_rule' => $template->payrollAttributionRule(),
                'active' => $template->active(),
            ]
        );
    }

    private function toDomain(ShiftTemplateModel $record): ShiftTemplate
    {
        $overtime = $record->overtime_rules ? new OvertimeRules(
            (int) ($record->overtime_rules['minOvertimeThreshold'] ?? 0),
            (int) ($record->overtime_rules['roundingInterval'] ?? 15),
            (int) ($record->overtime_rules['graceMinutes'] ?? 0),
            (int) ($record->overtime_rules['beforeShiftAllowance'] ?? 0),
            (int) ($record->overtime_rules['afterShiftAllowance'] ?? 0),
        ) : new OvertimeRules(0, 15, 0, 0, 0);

        $flex = $record->flexibility_rules ? new FlexibilityRules(
            (int) ($record->flexibility_rules['maxEarlyArrival'] ?? 0),
            (int) ($record->flexibility_rules['maxLateDeparture'] ?? 0),
            $record->flexibility_rules['coreStart'] ?? null,
            $record->flexibility_rules['coreEnd'] ?? null,
        ) : new FlexibilityRules(0, 0, null, null);

        return ShiftTemplate::reconstitute(
            ShiftTemplateId::fromString($record->id),
            $record->code,
            $record->name,
            ShiftWindow::fromStrings($record->start_time, $record->end_time),
            (int) $record->break_minutes,
            (int) $record->late_tolerance_minutes,
            $overtime,
            $flex,
            $record->payroll_attribution_rule,
            (bool) $record->active,
        );
    }
}
