Dựa trên ROADMAP hiện tại, hệ thống đã mô tả khá đầy đủ các **module** nhưng mới chỉ ở mức **feature list**. Để có thể thiết kế và triển khai một hệ thống iHRM thực tế (đặc biệt theo hướng Enterprise), cần phân tích thêm rất nhiều yêu cầu nghiệp vụ, quy tắc, luồng xử lý và kiến trúc.

Dưới đây là bản Brainstorming theo góc nhìn Business Analyst + Solution Architect.

> Tham chiếu ROADMAP:

---

# 1. Business Requirement

Đầu tiên cần xác định phạm vi của hệ thống.

## Hệ thống dành cho ai?

Có thể có nhiều mô hình:

* Một công ty sử dụng
* Nhiều công ty (SaaS)
* Tập đoàn
* Holding
* Franchise

=> ảnh hưởng rất lớn tới kiến trúc dữ liệu.

Ví dụ:

```
Tenant
    Company
        Branch
            Department
```

---

## Đối tượng sử dụng

ROADMAP mới chỉ liệt kê role mẫu.

Thực tế cần phân tích:

* CEO
* HR Director
* HR Manager
* HR Staff
* Accountant
* Payroll
* Department Manager
* Team Leader
* Employee
* Recruiter
* Interviewer
* Trainer
* IT
* Security
* Receptionist
* External Candidate

Mỗi role có quyền gì?

Có được xem thông tin lương?

Có được xem nhân viên phòng khác?

Có được duyệt đơn?

Có được export dữ liệu?

---

# 2. Permission Model

ROADMAP mới chỉ ghi:

* module permission
* department permission

Nhưng thực tế cần rõ hơn.

Ví dụ:

```
Role

Permission

Policy

Data Scope

Approval Scope
```

Ví dụ:

HR Manager

```
Employee.View.All
Employee.Edit.All
Salary.View
Salary.Edit
```

Department Manager

```
Employee.View.Department

Leave.Approve.Department
Attendance.Approve.Department
```

Employee

```
Employee.View.Self

Leave.Create

Attendance.View.Self
```

Ngoài RBAC còn cần

ABAC

Ví dụ

```
Employee chỉ xem hồ sơ của mình

Manager xem nhân viên thuộc mình

CEO xem toàn bộ

Payroll chỉ xem thông tin lương
```

---

# 3. Organization

ROADMAP mới chỉ có

Company

Branch

Department

Position

Org Chart

Thực tế còn thiếu:

Division

Business Unit

Cost Center

Project

Working Location

Grade

Job Level

Job Family

Employment Type

Employment Status

Reporting Line

Matrix Manager

---

Ví dụ

```
Company

Branch

Building

Floor

Department

Team

Position

Job Grade

```

---

# 4. Employee

Đây là Aggregate lớn nhất.

Không nên chỉ có bảng Employee.

Nên tách thành

Employee

Employee Contact

Emergency Contact

Identity

Passport

Visa

Tax

Insurance

Education

Experience

Family

Bank Account

Document

Skill

Language

Certification

Medical

Custom Fields

History

Address

Photo

Signature

---

Ngoài ra cần

Employee Number Rule

Ví dụ

```
EMP000001

HN240001

DEV001

```

Có auto generate không?

---

# 5. Employee Lifecycle

ROADMAP chưa nói rõ lifecycle.

Ví dụ

Candidate

↓

Offer

↓

Onboarding

↓

Probation

↓

Active

↓

Promotion

↓

Transfer

↓

Suspended

↓

Resigned

↓

Archived

---

Đây là state machine.

---

# 6. Contract

Không chỉ có

Tạo

Gia hạn

Theo dõi

Mà còn

Version

Renew History

Appendix

Digital Signature

Approval

Auto Renewal

Reminder

Template

Contract Number

Multiple Contracts

Future Contract

---

# 7. Attendance

Đây là module cực khó.

ROADMAP mới chỉ có feature.

Cần phân tích:

Nguồn dữ liệu

```
Fingerprint

FaceID

GPS

QR

Manual

Import

API

```

Rule

```
Late

Early

Absent

Overtime

Holiday

Weekend

Flexible

Night Shift

Cross Day

```

Ca qua đêm

Ví dụ

22:00

↓

06:00

Ngày công thuộc ngày nào?

---

# 8. Shift

Thiếu rất nhiều.

Ví dụ

Recurring Shift

Shift Rotation

Flexible Shift

Open Shift

Split Shift

Temporary Shift

Public Holiday

Overtime Rule

Shift Template

Shift Calendar

---

# 9. Leave

Không chỉ là

Nghỉ phép

Mà còn

Accrual

Carry Forward

Expiry

Negative Leave

Half Day

Hour Leave

Approval

Replacement Employee

Holiday Calendar

Leave Balance

Encashment

---

# 10. Payroll

Đây là module lớn nhất.

ROADMAP mới chỉ là

```
Basic Salary

Allowance

Bonus

Tax
```

Thực tế cần

Payroll Period

Payroll Lock

Payroll Adjustment

Retroactive Payroll

Multiple Salary Structure

Currency

Exchange Rate

Tax Region

Insurance Region

Loan

Advance

Commission

OT Formula

Night Shift Formula

Attendance Integration

Leave Integration

Payslip Template

Approval

Journal Export

Bank Transfer File

---

# 11. Recruitment

Cần thêm

Job Posting

Career Site

Talent Pool

Resume Parsing

Interview Pipeline

Interview Scorecard

Offer Approval

Candidate Portal

Recruitment Cost

Recruitment KPI

---

# 12. Performance

Không chỉ KPI

Mà còn

Goal

Competency

Behavior

360 Feedback

Calibration

Performance Cycle

Weight

Promotion Recommendation

Career Path

---

# 13. Training

Nên bổ sung

Course

Session

Trainer

Assessment

Exam

Certificate

Learning Path

Mandatory Training

Training Cost

Evaluation

---

# 14. Asset

Thiếu

Serial Number

Warranty

Supplier

Purchase

Maintenance

Repair

Lost

Broken

Disposal

Depreciation

Barcode

QR Code

---

# 15. Workflow Engine

ROADMAP chỉ nói

Approval Workflow

Nên tách thành

Workflow Engine

Ví dụ

```
Condition

Step

Approver

Escalation

Delegation

Timeout

Parallel

Sequential

Reject

Cancel

Rollback
```

Để dùng chung cho

Leave

OT

Expense

Recruitment

Contract

Asset

Purchase

---

# 16. Notification

Cần Event Center.

Ví dụ

```
Event

↓

Queue

↓

Notification

↓

Email

↓

Mobile

↓

WebSocket

↓

SMS

↓

Zalo

```

Có template.

Có variables.

---

# 17. Dashboard

Không chỉ là report.

Nên có

Widget

Filter

Drill Down

Export

Saved Dashboard

Role Dashboard

Realtime Dashboard

---

# 18. Configuration

Đây là module thường bị thiếu.

Cần thêm

Business Rule

Master Data

Lookup

Holiday

Working Calendar

Approval Matrix

Email Template

Document Template

Code Generator

System Parameter

Feature Toggle

---

# 19. Audit

ROADMAP chỉ có Audit Log trong MVP 4.

Enterprise thường cần

Login Log

Data Change Log

Approval Log

API Log

Export Log

Import Log

Security Log

File Download Log

---

# 20. Integration

ROADMAP chỉ ghi API Integration.

Nên phân tích:

ERP

SAP

Oracle

Active Directory

SSO

Google Workspace

Microsoft 365

Slack

Teams

Payroll

Fingerprint Machine

FaceID

Bank

Tax

Insurance

Email

SMS

Webhook

REST

GraphQL

---

# 21. Mobile

Không chỉ là Mobile App.

Cần xác định

Employee Self Service

Manager Self Service

Offline Attendance

Push Notification

QR Attendance

Leave

Payslip

Approval

Training

Asset

Profile

---

# 22. Security

Thiếu hoàn toàn.

Enterprise HRM thường cần

SSO

OAuth

OpenID Connect

2FA

Password Policy

Session Policy

Device Management

IP Restriction

Encryption

Mask Salary

PII Protection

GDPR

Backup

Disaster Recovery

---

# 23. Non-functional Requirements (NFR)

ROADMAP hiện chưa đề cập nhưng đây là phần bắt buộc trong tài liệu SRS:

| Nhóm            | Nội dung cần làm rõ                                                                           |
| --------------- | --------------------------------------------------------------------------------------------- |
| Performance     | Số lượng nhân viên tối đa, thời gian phản hồi API, xử lý bảng lương hàng chục nghìn nhân viên |
| Scalability     | Multi-company, Multi-tenant, khả năng mở rộng module                                          |
| Availability    | SLA, High Availability, Backup, Disaster Recovery                                             |
| Security        | RBAC, ABAC, MFA, mã hóa dữ liệu, Audit Log                                                    |
| Maintainability | Modular Monolith hoặc Microservices, Plugin Architecture                                      |
| Compliance      | Luật Lao động Việt Nam, Thuế TNCN, BHXH, lưu trữ hồ sơ                                        |
| Observability   | Logging, Metrics, Tracing, Alerting                                                           |

---

# 24. Cross-module Business Flow

Ngoài các module độc lập, cần xác định rõ các luồng nghiệp vụ xuyên suốt vì đây là phần quyết định kiến trúc hệ thống:

```text
Recruitment
        │
        ▼
Offer
        │
        ▼
Onboarding
        │
        ▼
Employee
        │
        ├────────► Contract
        ├────────► Attendance
        ├────────► Shift
        ├────────► Leave
        ├────────► Payroll
        ├────────► Performance
        ├────────► Training
        ├────────► Asset
        │
        ▼
Promotion / Transfer
        │
        ▼
Offboarding
```

Các luồng này cần được định nghĩa rõ về trạng thái, sự kiện (event), quy trình phê duyệt và dữ liệu trao đổi giữa các module để đảm bảo tính nhất quán toàn hệ thống.

---

## Kết luận

ROADMAP hiện tại đã bao quát **18 module chức năng và lộ trình MVP** rất tốt, nhưng mới ở mức **danh sách tính năng**. Để triển khai thành một hệ thống iHRM hoàn chỉnh, cần bổ sung các lớp yêu cầu sau:

1. **Business Requirements** (nghiệp vụ và quy tắc kinh doanh).
2. **Functional Requirements** (chi tiết use case, workflow, validation, state machine).
3. **Data Model** (Aggregate, Entity, Value Object, quan hệ dữ liệu).
4. **Permission & Security Model** (RBAC, ABAC, Data Scope).
5. **Workflow & Approval Engine** dùng chung cho toàn hệ thống.
6. **Integration & Event Architecture** để các module liên kết và mở rộng.
7. **Non-functional Requirements (NFR)** về hiệu năng, bảo mật, mở rộng và tuân thủ.
8. **Cross-module Business Flows** mô tả đầy đủ vòng đời nhân sự từ tuyển dụng đến nghỉ việc.
