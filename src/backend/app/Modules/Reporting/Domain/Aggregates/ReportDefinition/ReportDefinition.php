<?php

namespace App\Modules\Reporting\Domain\Aggregates\ReportDefinition;

class ReportDefinition
{
    private function __construct(
        private readonly ReportDefinitionId $id,
        private readonly string $code,
        private string $name,
        private ?string $description,
        private string $queryClass,
        private array $filtersSchema,
        private array $columnsSchema,
        private bool $active,
    ) {}

    public static function create(
        ReportDefinitionId $id,
        string $code,
        string $name,
        ?string $description,
        string $queryClass,
        array $filtersSchema = [],
        array $columnsSchema = [],
        bool $active = true,
    ): self {
        return new self($id, $code, $name, $description, $queryClass, $filtersSchema, $columnsSchema, $active);
    }

    public function activate(): void { $this->active = true; }
    public function deactivate(): void { $this->active = false; }

    public function getId(): ReportDefinitionId { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getQueryClass(): string { return $this->queryClass; }
    public function getFiltersSchema(): array { return $this->filtersSchema; }
    public function getColumnsSchema(): array { return $this->columnsSchema; }
    public function isActive(): bool { return $this->active; }
}
