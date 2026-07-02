<?php
namespace App\Modules\Leave\Infrastructure\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ApproveLeaveRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return []; } }
