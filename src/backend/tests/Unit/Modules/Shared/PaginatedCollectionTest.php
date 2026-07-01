<?php

namespace Tests\Unit\Modules\Shared;

use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PaginatedCollectionTest extends TestCase
{
    public function test_paginated_collection_returns_correct_meta(): void
    {
        $items = collect([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $paginator = new LengthAwarePaginator(
            $items,
            total: 10,
            perPage: 2,
            currentPage: 1,
        );

        $result = (new PaginatedCollection($paginator))->toArray(request());

        $this->assertSame(1, $result['meta']['current_page']);
        $this->assertSame(10, $result['meta']['total']);
        $this->assertSame(5, $result['meta']['last_page']);
        $this->assertCount(2, $result['data']);
        $this->assertArrayHasKey('first', $result['links']);
    }
}
