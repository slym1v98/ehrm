<?php

namespace App\Modules\Reporting\Infrastructure\Console;

use App\Modules\Reporting\Application\Contracts\ReportQueryInterface;
use Illuminate\Contracts\Container\Container;

class ReportQueryRegistry
{
    public function __construct(private Container $container) {}

    public function resolve(string $class): ReportQueryInterface
    {
        if (!class_exists($class)) throw new \RuntimeException("Report query class not found: {$class}");
        $query = $this->container->make($class);
        if (!$query instanceof ReportQueryInterface) throw new \RuntimeException("Report query class must implement ReportQueryInterface: {$class}");
        return $query;
    }
}
