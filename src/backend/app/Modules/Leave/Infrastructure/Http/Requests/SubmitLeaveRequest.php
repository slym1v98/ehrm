<?php
namespace App\Modules\Leave\Infrastructure\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SubmitLeaveRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['leave_type_id'=>['required','string','exists:leave_types,id'],'start_at'=>['required','date','after_or_equal:today'],'end_at'=>['required','date','after_or_equal:start_at'],'duration_unit'=>['required',Rule::in(['day','half_day','hour'])],'reason'=>['nullable','string','max:1000']]; } }
