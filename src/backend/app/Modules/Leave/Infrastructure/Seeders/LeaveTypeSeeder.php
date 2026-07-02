<?php

namespace App\Modules\Leave\Infrastructure\Seeders;

use App\Modules\Leave\Infrastructure\Persistence\Eloquent\LeaveTypeModel;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['annual', 'Annual Leave', true, 10],
            ['sick', 'Sick Leave', false, 20],
            ['unpaid', 'Unpaid Leave', false, 30],
            ['maternity', 'Maternity Leave', true, 40],
        ] as [$code, $name, $tracked, $sortOrder]) {
            LeaveTypeModel::updateOrCreate(
                ['code' => $code],
                [
                    'id' => (string) Uuid::uuid4(),
                    'name' => $name,
                    'is_balance_tracked' => $tracked,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }
}
