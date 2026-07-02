<?php
namespace App\Modules\Leave\Infrastructure\Http\Controllers;
use App\Modules\Leave\Domain\Repositories\LeavePolicyRepositoryInterface;
use App\Modules\Leave\Infrastructure\Http\Resources\LeavePolicyResource;
use Illuminate\Routing\Controller;
class LeavePolicyController extends Controller { public function __construct(private LeavePolicyRepositoryInterface $policies) {} public function index() { return LeavePolicyResource::collection($this->policies->all()); } }
