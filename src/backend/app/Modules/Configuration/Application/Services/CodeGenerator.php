<?php

namespace App\Modules\Configuration\Application\Services;

use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\CodeGenerationRuleModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CodeGenerator
{
    public function preview(string $entityType): string
    {
        $rule = $this->rule($entityType);

        return $this->render($rule, $rule->next_number);
    }

    public function next(string $entityType): string
    {
        return DB::transaction(function () use ($entityType) {
            $rule = CodeGenerationRuleModel::where('entity_type', $entityType)->lockForUpdate()->first();

            if (! $rule || ! $rule->active) {
                throw new RuntimeException("No active code generation rule for {$entityType}.");
            }

            $code = $this->render($rule, $rule->next_number);
            $rule->forceFill(['next_number' => $rule->next_number + 1])->save();

            return $code;
        });
    }

    private function rule(string $entityType): CodeGenerationRuleModel
    {
        $rule = CodeGenerationRuleModel::where('entity_type', $entityType)->first();

        if (! $rule || ! $rule->active) {
            throw new RuntimeException("No active code generation rule for {$entityType}.");
        }

        return $rule;
    }

    private function render(CodeGenerationRuleModel $rule, int $number): string
    {
        $now = now();

        return strtr($rule->pattern, [
            '{prefix}' => $rule->prefix,
            '{yyyy}' => $now->format('Y'),
            '{yy}' => $now->format('y'),
            '{mm}' => $now->format('m'),
            '{dd}' => $now->format('d'),
            '{seq}' => str_pad((string) $number, $rule->sequence_padding, '0', STR_PAD_LEFT),
        ]);
    }
}
