<?php

namespace App\Modules\Configuration\Infrastructure\Seeders;

use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\CodeGenerationRuleModel;
use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\HolidayCalendarModel;
use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\LookupGroupModel;
use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\NotificationThresholdModel;
use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\SystemSettingModel;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $this->lookupGroups();
        $this->codeRules();
        $this->settings();
        $this->holidayCalendar();
        $this->thresholds();
    }

    private function lookupGroups(): void
    {
        foreach (['gender', 'employment_type', 'contract_type', 'marital_status', 'education_level', 'employee_status'] as $code) {
            LookupGroupModel::updateOrCreate(['code' => $code], ['name' => str($code)->replace('_', ' ')->title(), 'active' => true]);
        }
    }

    private function codeRules(): void
    {
        foreach ([['employee', 'EMP'], ['contract', 'CTR']] as [$entityType, $prefix]) {
            CodeGenerationRuleModel::updateOrCreate(
                ['entity_type' => $entityType],
                ['prefix' => $prefix, 'pattern' => '{prefix}-{yyyy}-{seq}', 'next_number' => 1, 'sequence_padding' => 5, 'active' => true]
            );
        }
    }

    private function settings(): void
    {
        foreach ([
            ['company.name', 'iHRM', 'string'],
            ['company.tax_id', '', 'string'],
            ['locale.default', 'vi', 'string'],
            ['locale.timezone', 'Asia/Ho_Chi_Minh', 'string'],
            ['employee.code_generation_rule', 'employee', 'string'],
        ] as [$key, $value, $type]) {
            SystemSettingModel::updateOrCreate(['key' => $key], ['value' => $value, 'value_type' => $type, 'editable' => true]);
        }
    }

    private function holidayCalendar(): void
    {
        $year = (int) now()->format('Y');
        $calendar = HolidayCalendarModel::updateOrCreate(
            ['code' => "vn-{$year}"],
            ['name' => "Vietnam {$year}", 'year' => $year, 'active' => true]
        );

        $calendar->holidays()->updateOrCreate(
            ['date' => "{$year}-01-01"],
            ['name' => 'New Year', 'paid' => true, 'metadata' => []]
        );
    }

    private function thresholds(): void
    {
        foreach ([['contract.expiry', 'contract', 30], ['document.expiry', 'document', 30]] as [$code, $targetType, $daysBefore]) {
            NotificationThresholdModel::updateOrCreate(['code' => $code], ['target_type' => $targetType, 'days_before' => $daysBefore, 'active' => true, 'metadata' => []]);
        }
    }
}
