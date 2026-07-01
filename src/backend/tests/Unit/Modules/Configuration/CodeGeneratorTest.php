<?php

namespace Tests\Unit\Modules\Configuration;

use App\Modules\Configuration\Application\Services\CodeGenerator;
use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\CodeGenerationRuleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_increment_next_number(): void
    {
        $rule = CodeGenerationRuleModel::create([
            'entity_type' => 'employee',
            'prefix' => 'EMP',
            'pattern' => '{prefix}-{yyyy}-{seq}',
            'next_number' => 7,
            'sequence_padding' => 4,
            'active' => true,
        ]);

        $code = app(CodeGenerator::class)->preview('employee');

        $this->assertSame('EMP-'.now()->format('Y').'-0007', $code);
        $this->assertSame(7, $rule->fresh()->next_number);
    }

    public function test_next_generates_code_and_increments_next_number(): void
    {
        $rule = CodeGenerationRuleModel::create([
            'entity_type' => 'contract',
            'prefix' => 'CTR',
            'pattern' => '{prefix}-{yy}{mm}{dd}-{seq}',
            'next_number' => 12,
            'sequence_padding' => 3,
            'active' => true,
        ]);

        $code = app(CodeGenerator::class)->next('contract');

        $this->assertSame('CTR-'.now()->format('ymd').'-012', $code);
        $this->assertSame(13, $rule->fresh()->next_number);
    }
}
