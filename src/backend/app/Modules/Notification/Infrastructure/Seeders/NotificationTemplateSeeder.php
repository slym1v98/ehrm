<?php

namespace App\Modules\Notification\Infrastructure\Seeders;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplateId;
use App\Modules\Notification\Domain\Repositories\MessageTemplateRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    private array $templates = [
        ['code' => 'leave.request.submitted', 'name' => 'Leave Submitted', 'channel' => 'in_app', 'subject' => 'Leave Request Submitted', 'body' => 'Your {{leave_type}} request from {{start_date}} has been submitted.'],
        ['code' => 'leave.request.approved', 'name' => 'Leave Approved', 'channel' => 'in_app', 'subject' => 'Leave Request Approved', 'body' => 'Your {{leave_type}} request from {{start_date}} has been approved by {{approved_by}}.'],
        ['code' => 'leave.request.rejected', 'name' => 'Leave Rejected', 'channel' => 'in_app', 'subject' => 'Leave Request Rejected', 'body' => 'Your {{leave_type}} request from {{start_date}} has been rejected.'],
        ['code' => 'attendance.adjustment.approved', 'name' => 'Adjustment Approved', 'channel' => 'in_app', 'subject' => 'Attendance Adjustment Approved', 'body' => 'Your attendance adjustment for {{work_date}} has been approved.'],
        ['code' => 'attendance.adjustment.rejected', 'name' => 'Adjustment Rejected', 'channel' => 'in_app', 'subject' => 'Attendance Adjustment Rejected', 'body' => 'Your attendance adjustment for {{work_date}} has been rejected.'],
        ['code' => 'shift.assigned', 'name' => 'Shift Assigned', 'channel' => 'in_app', 'subject' => 'New Shift Assignment', 'body' => 'You have been assigned the {{shift_name}} shift starting {{start_date}}.'],
        ['code' => 'shift.assignment.ended', 'name' => 'Shift Ended', 'channel' => 'in_app', 'subject' => 'Shift Assignment Ended', 'body' => 'Your shift assignment {{shift_name}} has ended.'],
        ['code' => 'payroll.payslip.available', 'name' => 'Payslip Available', 'channel' => 'in_app', 'subject' => 'Payslip Ready', 'body' => 'Your payslip for period {{period_code}} is now available.'],
        ['code' => 'payroll.payslip.available.email', 'name' => 'Payslip Available Email', 'channel' => 'email', 'subject' => 'Payslip Ready', 'body' => 'Your payslip for period {{period_code}} is now available.'],
        ['code' => 'workflow.step.assigned', 'name' => 'Approval Step Assigned', 'channel' => 'in_app', 'subject' => 'Approval Required', 'body' => 'You have been assigned to approve {{subject_type}} request.'],
        ['code' => 'workflow.approved', 'name' => 'Workflow Approved', 'channel' => 'in_app', 'subject' => 'Request Approved', 'body' => 'Your {{subject_type}} request has been fully approved.'],
        ['code' => 'workflow.rejected', 'name' => 'Workflow Rejected', 'channel' => 'in_app', 'subject' => 'Request Rejected', 'body' => 'Your {{subject_type}} request has been rejected.'],
        ['code' => 'workflow.returned', 'name' => 'Workflow Returned', 'channel' => 'in_app', 'subject' => 'Request Returned', 'body' => 'Your {{subject_type}} request has been returned for edit.'],
        ['code' => 'security.unauthorized.access', 'name' => 'Security Alert', 'channel' => 'in_app', 'subject' => 'Security Alert', 'body' => 'Unauthorized access attempt detected on your account.'],
    ];

    public function run(MessageTemplateRepositoryInterface $repo): void
    {
        foreach ($this->templates as $t) {
            $existing = $repo->findByCode($t['code']);
            if ($existing !== null) continue;

            $template = MessageTemplate::create(
                MessageTemplateId::generate(),
                $t['code'],
                $t['name'],
                Channel::from($t['channel']),
                $t['subject'],
                $t['body'],
                [],
            );
            $repo->save($template);
        }
    }
}
