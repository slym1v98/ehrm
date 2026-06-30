Để hiện thực hóa tài liệu này thành một hệ thống thực tế — đặc biệt là khi áp dụng các bộ tiêu chuẩn cao như **Clean Architecture / Domain-Driven Design (DDD)** và mô hình hệ thống decoupled (như cách bạn thường tối ưu backend/frontend hoặc thiết kế hệ thống lớn) — chúng ta cần bóc tách và làm rõ các yêu cầu kỹ thuật chuyên sâu ("hidden requirements") đằng sau các gạch đầu dòng nghiệp vụ đó.

Dưới đây là phân tích chi tiết, bẻ nhỏ bài toán từ kiến trúc, cơ sở dữ liệu đến logic nghiệp vụ cốt lõi cho từng phân hệ.

---

## I. Phân tích Kiến trúc Hệ thống & Yêu cầu Kỹ thuật Nền tảng (Cross-Cutting Concerns)

Trước khi đi vào từng module nghiệp vụ, hệ thống eHRM cần một "bộ khung" vững chắc để xử lý các bài toán dữ liệu lớn, bảo mật và khả năng mở rộng (scalability).

### 1. Phân quyền phức tạp (Dynamic RBAC & Matrix Permission)

* **Yêu cầu cốt lõi:** Không chỉ là phân quyền theo Vai trò (Role-based), eHRM đòi hỏi phân quyền theo **Trục dọc (Phòng ban/Chi nhánh)** và **Trục ngang (Cấp bậc quản lý)**.
* **Làm rõ chi tiết:**
* *Data Scope (Phạm vi dữ liệu):* `HR Staff` thuộc Chi nhánh A chỉ được xem hồ sơ nhân viên Chi nhánh A. `Department Manager` phòng IT chỉ được duyệt đơn của nhân viên phòng IT.
* *Hierarchical Permission:* Quản lý cấp cao hơn mặc định có quyền phê duyệt/xem dữ liệu của các cấp dưới trong sơ đồ cây (Org Chart).
* *Audit Log (Nhật ký thao tác):* Phải lưu trữ theo dạng bất biến (Immutable), ghi lại rõ: Ai (User), Làm gì (Action), Vào lúc nào (Timestamp), Dữ liệu trước và sau khi thay đổi (Payload `before` -> `after`).



### 2. Mô hình Dữ liệu Lịch sử (Temporal Data / Event Sourcing sơ khai)

* **Yêu cầu cốt lõi:** Nhân sự là ngành liên quan chặt chẽ đến đường mốc thời gian (Timeline). Hệ thống cần biết tại một ngày bất kỳ trong quá khứ, mức lương hay chức vụ của nhân viên X là bao nhiêu để phục vụ việc tính lương hoặc truy thu.
* **Làm rõ chi tiết:**
* Áp dụng bảng lưu lịch sử trạng thái (`Effective Date` - Ngày có hiệu lực). Ví dụ: Nhân viên được duyệt tăng lương từ ngày 15/03 nhưng đến 30/03 mới chạy bảng lương, hệ thống phải tự tách block lương thành 2 giai đoạn (1-14 lương cũ, 15-31 lương mới).



---

## II. Bóc tách & Làm rõ Yêu cầu từng Module (Deep-Dive)

### Phân hệ 1: Core, Organization & Employee (Nền tảng Quản trị)

#### 1. Org Chart (Sơ đồ tổ chức)

* **Bài toán:** Quản lý cấu trúc cây (Tree Structure) của Phòng ban và Mối quan hệ Quản lý trực tiếp (`Report-to`).
* **Làm rõ yêu cầu:**
* Hỗ trợ mô hình một nhân viên thuộc nhiều phòng ban (ví dụ: kiêm nhiệm) hoặc có 2 người quản lý (Direct Manager & Functional Manager trong mô hình Matrix Organization).
* Cần cơ chế "Phẳng hóa" (Flattening) dữ liệu từ cấu trúc cây đệ quy khi hiển thị danh sách hoặc tính toán quyền hạn để tối ưu tốc độ truy vấn cơ sở dữ liệu.



#### 2. Employee Profile & History

* **Làm rõ yêu cầu:**
* *Mã nhân viên (Employee Code):* Cần cấu hình quy tắc sinh tự động (ví dụ: `COMP-2026-0001`).
* *Trạng thái làm việc:* Chuyển đổi trạng thái tự động dựa trên ngày tháng (Ứng viên -> Thử việc -> Chính thức -> Nghỉ thai sản/Tạm hoãn -> Đã nghỉ việc).
* *Tài liệu (Documents):* Quản lý thời hạn của giấy tờ (ví dụ: CCCD hết hạn, Visa hết hạn cho người nước ngoài) và cơ chế phân quyền bảo mật file (S3 Private Bucket pre-signed URL) để tránh lộ thông tin lương/hồ sơ nhạy cảm.



### Phân hệ 2: Chấm công, Ca kíp & Đơn từ (Time & Attendance Engine)

Đây là phân hệ phức tạp nhất, dễ phát sinh bug nếu logic không tường minh.

```
[Máy vân tay / GPS / Web App] ---> [Dữ liệu Chấm công Thô (Raw Logs)] 
                                              |
                                              v
[Cấu hình Ca (Shift) / Lịch làm việc] -> [Công cụ Xử lý (Calculation Engine)]
                                              |
                                              v
[Đơn từ được duyệt (Nghỉ, Tăng ca,...)] --> [Bảng công Tổng hợp (Timesheet)]

```

#### 1. Shift & Work Schedule (Lịch làm việc & Ca kíp)

* **Làm rõ yêu cầu:**
* Hỗ trợ ca xuyên đêm (ví dụ: Check-in 22h hôm nay, Check-out 6h sáng hôm sau). Hệ thống phải tự hiểu log check-out thuộc về ca làm việc của ngày hôm trước.
* Hỗ trợ "Điểm danh linh hoạt" (Flexitime): Không tính đi trễ về sớm, chỉ tính đủ số giờ làm việc trong ngày (ví dụ: đủ 8 tiếng).
* Cơ chế đổi ca giữa các nhân viên và quy trình duyệt đổi ca từ quản lý.



#### 2. Attendance Management (Xử lý dữ liệu Chấm công)

* **Làm rõ yêu cầu:**
* *Thiết bị đầu vào đa dạng:* Đồng bộ dữ liệu thô (Raw logs) từ máy vân tay (qua SDK/API), tọa độ GPS (Mobile app giới hạn bán kính bán kính $R$ mét từ văn phòng), hoặc IP Wifi văn phòng.
* *Công cụ đối soát (Matching Engine):* Logic tự động khớp log vân tay thô vào ca làm việc được gán để tính toán: *Đi trễ bao nhiêu phút? Về sớm bao nhiêu phút? Có tính tăng ca (OT) hay không?*



#### 3. Leave Management (Quản lý Nghỉ phép)

* **Làm rõ yêu cầu:**
* *Cơ chế tích lũy phép (Accrual Rules):* Ví dụ mỗi tháng làm đủ công được cộng 1 ngày phép năm. Hỗ trợ cấu hình cộng dồn phép thâm niên (làm 5 năm được thêm 1 ngày).
* *Hạn mức sử dụng (Expiration):* Phép năm cũ chỉ được dùng đến hết ngày 31/03 năm sau, sau ngày đó hệ thống tự động reset về 0 hoặc chuyển thành tiền quyết toán phép.



#### 4. Request / Approval Workflow (Công cụ cấu hình quy trình phê duyệt)

* **Làm rõ yêu cầu:**
* Hệ thống không nên fix cứng quy trình duyệt. Cần một **Workflow Engine** động.
* *Duyệt nhiều cấp:* Cấp 1 (Quản lý trực tiếp) -> Cấp 2 (Trưởng phòng) -> Cấp 3 (HR).
* *Duyệt điều hướng (Conditional Routing):* Đơn nghỉ < 3 ngày chỉ cần Quản lý trực tiếp duyệt; Đơn nghỉ >= 3 ngày phải lên Giám đốc phê duyệt.
* *Ủy quyền phê duyệt (Delegation):* Khi Quản lý đi công tác, họ có thể ủy quyền cho một nhân sự khác duyệt đơn thay trong khoảng thời gian chỉ định.



### Phân hệ 3: Tính lương & Đãi ngộ (Payroll Engine)

#### 1. Công thức lương động (Flexible Formula Engine)

* **Bài toán:** Mỗi công ty có một cách tính lương khác nhau, thậm chí giữa các phòng ban trong cùng một công ty cũng khác nhau (Kinh doanh tính theo doanh số, Kỹ thuật tính theo lương cứng + OT).
* **Làm rõ yêu cầu:**
* Hệ thống cần cung cấp một trình cấu hình công thức dựa trên các biến số (Variables) như: `Tong_Cong_Chuan`, `Ngay_Cong_Thuc_Te`, `Luong_Co_Ban`, `So_Phut_Tre`, `Tien_OT`.
* Hỗ trợ toán tử toán học và logic (`IF/ELSE`) để tính Thuế TNCN (Luỹ tiến) và Bảo hiểm bắt buộc theo đúng luật lao động hiện hành.



#### 2. Chốt và Khóa Bảng Lương (Payroll Lock)

* **Làm rõ yêu cầu:**
* Sau khi bảng lương tháng được duyệt, phải có chức năng "Khóa" (Lock). Dữ liệu lương của tháng đó sẽ trở thành bất biến, không thể bị thay đổi ngay cả khi hồ sơ nhân viên có cập nhật muộn.
* Cơ chế phát hành và bảo mật phiếu lương (Payslip): Mã hóa file PDF phiếu lương hoặc yêu cầu mật khẩu (ví dụ: ngày tháng năm sinh của nhân viên) để mở xem trên mobile/email.



### Phân hệ 4: Tuyển dụng, Đào tạo & Vòng đời Nhân sự (Talent Management)

#### 1. Recruitment & Onboarding/Offboarding

* **Làm rõ yêu cầu:**
* *Tuyển dụng:* Lưu trữ dữ liệu ứng viên tập trung, tránh trùng lặp CV (bằng cách kiểm tra Email/Số điện thoại).
* *Onboarding/Offboarding Checklist:* Tạo ra các template đầu việc tự động giao cho các phòng ban liên quan khi có nhân sự mới hoặc nghỉ việc (ví dụ: IT chuẩn bị laptop/tạo mail; Hành chính cấp thẻ xe; Kế toán thu hồi tài sản).



#### 2. Performance Management (KPI / OKR)

* **Làm rõ yêu cầu:**
* Thiết lập trọng số (Weight) cho từng mục tiêu (ví dụ: KPI gồm 3 chỉ số, trọng số lần lượt là 40% - 40% - 20%).
* Luồng đánh giá: Nhân viên tự đánh giá -> Quản lý đánh giá -> Thống nhất và ký duyệt.



---

## III. Đề xuất Lộ trình Triển khai thực tế dựa trên chiến lược MVP

Dựa trên gợi ý chia MVP trong file Roadmap, đây là thứ tự ưu tiên tối ưu để phát triển hệ thống mà không bị chồng chéo logic:

```
[MVP 1: Nền tảng & Cốt lõi] 
   └── Tạo Org Chart, Hồ sơ nhân viên, Hợp đồng lao động.
   └── Cài đặt module phân quyền chặn chẽ từ đầu.
   └── Đơn xin nghỉ phép & Check-in/out cơ bản để tích lũy dữ liệu.
            │
            ▼
[MVP 2: Tự động hóa vận hành]
   └── Cấu hình Ca phức tạp (Xuyên đêm, Đổi ca).
   └── Workflow Engine (Duyệt đơn từ nhiều cấp, tự động điều hướng).
   └── Payroll Engine cơ bản (Đọc dữ liệu từ bảng công MVP 1 để tính lương).
            │
            ▼
[MVP 3 & 4: Mở rộng chuyên sâu & Doanh nghiệp]
   └── Tuyển dụng, Đánh giá hiệu suất (KPI/OKR), Quản lý tài sản.
   └── Tối ưu hóa hiệu năng Database, Audit log, API Integration.

```

## IV. Một vài lưu ý về tối ưu hóa Database

* **Truy vấn Chấm công & Lương:** Số lượng bản ghi chấm công (Raw logs) phát sinh hàng ngày là rất lớn ($Number\ of\ Employees \times Logs\ per\ day$). Cần cân nhắc giải pháp **Partitioning** bảng dữ liệu theo tháng/năm trên SQL Server hoặc PostgreSQL để đảm bảo tốc độ truy vấn không bị chậm theo thời gian.
* **Chỉ mục (Indexes):** Các trường thường xuyên tìm kiếm và đối soát như `employee_code`, `check_time`, `status`, `department_id` cần được quy hoạch Clustered/Non-Clustered Index chính xác để tránh tình trạng Table Scan khi chạy bảng công cuối tháng.