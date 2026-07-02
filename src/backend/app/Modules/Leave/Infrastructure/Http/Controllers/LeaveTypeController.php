<?php
namespace App\Modules\Leave\Infrastructure\Http\Controllers;
use App\Modules\Leave\Domain\Repositories\LeaveTypeRepositoryInterface;
use App\Modules\Leave\Infrastructure\Http\Resources\LeaveTypeResource;
use Illuminate\Routing\Controller;
class LeaveTypeController extends Controller { public function __construct(private LeaveTypeRepositoryInterface $types) {} public function index() { return LeaveTypeResource::collection($this->types->all()); } }
