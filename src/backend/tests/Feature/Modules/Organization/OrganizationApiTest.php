<?php

namespace Tests\Feature\Modules\Organization;

use App\Modules\Organization\Infrastructure\Persistence\Eloquent\BranchModel;
use App\Modules\Organization\Infrastructure\Persistence\Eloquent\DepartmentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->seed(\App\Modules\Organization\Infrastructure\Seeders\OrgStructureSeeder::class);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@ihrm.local',
            'password' => 'password',
        ]);
        $this->token = $response->json('data.access_token');
    }

    // Branches
    public function test_list_branches(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/branches')->assertStatus(200);
    }

    public function test_create_branch(): void
    {
        $r = $this->withToken($this->token)->postJson('/api/v1/branches', ['code' => 'DN-OFFICE', 'name' => 'Da Nang Office']);
        $r->assertStatus(201);
        $this->assertDatabaseHas('branches', ['code' => 'DN-OFFICE']);
    }

    public function test_duplicate_branch_code_409(): void
    {
        $r = $this->withToken($this->token)->postJson('/api/v1/branches', ['code' => 'HCM-HQ', 'name' => 'Dup']);
        $r->assertStatus(409);
        $r->assertJsonPath('error.code', 'DUPLICATE_BRANCH_CODE');
    }

    public function test_branch_deactivate_with_departments_409(): void
    {
        $b = BranchModel::where('code', 'HCM-HQ')->first();
        $r = $this->withToken($this->token)->postJson("/api/v1/branches/{$b->id}/deactivate");
        $r->assertStatus(409);
        $r->assertJsonPath('error.code', 'BRANCH_HAS_ACTIVE_DEPARTMENTS');
    }

    public function test_branch_show(): void
    {
        $b = BranchModel::where('code', 'HCM-HQ')->first();
        $this->withToken($this->token)->getJson("/api/v1/branches/{$b->id}")->assertStatus(200);
    }

    public function test_branch_update(): void
    {
        $b = BranchModel::where('code', 'HCM-HQ')->first();
        $r = $this->withToken($this->token)->patchJson("/api/v1/branches/{$b->id}", ['name' => 'Updated HQ']);
        $r->assertStatus(200);
        $this->assertDatabaseHas('branches', ['id' => $b->id, 'name' => 'Updated HQ']);
    }

    // Departments
    public function test_list_departments(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/departments')->assertStatus(200);
    }

    public function test_create_department(): void
    {
        $b = BranchModel::where('code', 'HCM-HQ')->first();
        $r = $this->withToken($this->token)->postJson('/api/v1/departments', [
            'branch_id' => $b->id, 'code' => 'TEST', 'name' => 'Test Dept',
        ]);
        $r->assertStatus(201);
        $this->assertDatabaseHas('departments', ['code' => 'TEST']);
    }

    public function test_move_department_to_self_422(): void
    {
        $d = DepartmentModel::where('code', 'IT')->first();
        $r = $this->withToken($this->token)->postJson("/api/v1/departments/{$d->id}/move", [
            'new_parent_id' => $d->id,
        ]);
        $r->assertStatus(422);
        $r->assertJsonPath('error.code', 'CIRCULAR_MOVE');
    }

    public function test_move_department_to_descendant_422(): void
    {
        $parent = DepartmentModel::where('code', 'IT')->first();
        $child = DepartmentModel::where('code', 'IT-DEV')->first();
        $r = $this->withToken($this->token)->postJson("/api/v1/departments/{$parent->id}/move", [
            'new_parent_id' => $child->id,
        ]);
        $r->assertStatus(422);
        $r->assertJsonPath('error.code', 'CIRCULAR_MOVE');
    }

    public function test_department_deactivate_with_children_409(): void
    {
        $d = DepartmentModel::where('code', 'IT')->first();
        $r = $this->withToken($this->token)->postJson("/api/v1/departments/{$d->id}/deactivate");
        $r->assertStatus(409);
        $r->assertJsonPath('error.code', 'DEPARTMENT_HAS_ACTIVE_CHILDREN');
    }

    // Positions
    public function test_list_positions(): void
    {
        $this->withToken($this->token)->getJson('/api/v1/positions')->assertStatus(200);
    }

    public function test_create_position(): void
    {
        $r = $this->withToken($this->token)->postJson('/api/v1/positions', [
            'code' => 'QA', 'name' => 'QA Engineer', 'level' => 3,
        ]);
        $r->assertStatus(201);
        $this->assertDatabaseHas('positions', ['code' => 'QA']);
    }

    public function test_duplicate_position_409(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/positions', ['code' => 'QA', 'name' => 'Q1']);
        $r = $this->withToken($this->token)->postJson('/api/v1/positions', ['code' => 'QA', 'name' => 'Q2']);
        $r->assertStatus(409);
    }

    // Org tree
    public function test_org_tree(): void
    {
        $r = $this->withToken($this->token)->getJson('/api/v1/org-tree');
        $r->assertStatus(200);
    }

    public function test_org_tree_filtered(): void
    {
        $b = BranchModel::where('code', 'HCM-HQ')->first();
        $this->withToken($this->token)->getJson("/api/v1/org-tree?branch_id={$b->id}")->assertStatus(200);
    }

    // Permission enforcement
    public function test_unauthenticated_401(): void
    {
        $this->getJson('/api/v1/branches')->assertStatus(401);
    }
}
