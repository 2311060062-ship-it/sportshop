# Kịch bản thuyết trình Jira — Log & quản lý Bug
## TrendStyle Fashion · NHOM11

**File slide:** `docs/thuyet-trinh-jira.html`  
Mở bằng Chrome → phím ← → để chuyển. In PDF (Ctrl+P) nếu cần dán vào PowerPoint.

**Thời lượng gợi ý:** 8–10 phút nói + 1–2 phút demo Jira + Q&A.

**Phân vai gợi ý (nhóm 3–4):**
| Vai | Phần nói |
|---|---|
| Người 1 | Slide 1–3 (mở đầu, Jira là gì) |
| Người 2 | Slide 4–7 (Bug Report, Priority, Lifecycle) |
| Người 3 | Slide 8–10 (3 bug TrendStyle + demo) |
| Người 4 / Leader | Slide 11–12 (thực hành, kết luận) + điều phối Q&A |

---

## Slide 1 — Bìa (30 giây)

Em xin trình bày về **Jira** và cách nhóm dùng công cụ này để **log và quản lý Bug** trên website **TrendStyle Fashion**.

Nhóm đã tạo project Jira **NHOM11_SOFTWARE_TESTING**, key **N11**, dùng board Kanban với các cột To Do, In Progress và Done.

Điểm quan trọng ngay từ đầu: **Jira không thay thế việc kiểm thử** — nhóm test trên web, rồi ghi nhận lỗi lên Jira để theo dõi sửa và retest.

---

## Slide 2 — Mục lục (20 giây)

Phần trình bày gồm sáu ý: Jira là gì; cấu trúc Bug Report; Priority và Lifecycle; case TrendStyle với ba bug thật; quy trình thực hành; và bài học rút ra.

---

## Slide 3 — Jira là gì? (1 phút)

Jira là nền tảng **issue tracking** của Atlassian. Trong kiểm thử phần mềm, nhóm dùng Jira để tạo Story mô tả chức năng, tạo Bug khi phát hiện lỗi, gắn mức ưu tiên, đính kèm minh chứng, và theo dõi ai đang sửa, đã sửa xong chưa, retest đạt chưa.

Nhóm nhấn mạnh: Jira **không tự chạy test case**. Muốn có bug đúng, phải test tay hoặc dùng tool như Playwright — còn Jira là nơi **quản lý** kết quả đó.

Khi thực hành, cần chọn đúng **Jira Software** Kanban. Nếu vào nhầm Product Discovery thì chỉ có “ideas”, không có Bug lifecycle chuẩn.

---

## Slide 4 — Summary tốt / xấu (1 phút)

Đề bài yêu cầu Summary theo công thức: **lỗi gì + ở đâu + ảnh hưởng gì**.

Ví dụ A: “Login not working” — quá chung, Developer khó hình dung và tái hiện.  
Ví dụ B: “Nút Login không bấm được trên Chrome — người dùng không thể đăng nhập” — đủ ba yếu tố.

Nhóm chọn B là Summary tốt hơn, và viết lại A thành câu mô tả rõ ràng tương tự. Đây cũng là tiêu chí nhóm dùng khi đặt tên ba bug trên project N11.

---

## Slide 5 — Trường bắt buộc của Bug Report (50 giây)

Một Bug trên Jira của nhóm luôn có: Environment (trình duyệt, OS, server, URL); Steps to Reproduce theo thứ tự; Expected Result và Actual Result; Priority kèm giải thích; Attachment screenshot; và Linked Issue nối về Story.

Thiếu một trong các phần này sẽ làm Dev mất thời gian hỏi lại hoặc không tái hiện được lỗi.

---

## Slide 6 — Priority P1 đến P5 (1 phút)

Nhóm không gắn mọi lỗi thành Critical.  
P1 Blocker khi hệ thống gần như không dùng được.  
P2 Critical khi ảnh hưởng nghiêm trọng — ví dụ không login được bằng email.  
P3 Major khi ảnh hưởng đáng kể nhưng site vẫn chạy — như search trả kết quả lệch.  
P4 Minor khi lỗi nhỏ còn workaround — như cuộn ngang trên mobile.  
P5 Trivial cho lỗi thẩm mỹ rất nhỏ.

Trên Jira team-managed, nhóm map High ≈ P2, Medium ≈ P3, Low ≈ P4 và vẫn ghi rõ mức P trong Description.

---

## Slide 7 — Bug Lifecycle (50 giây)

Quy trình nhóm thực hiện: Tester tạo Bug ở To Do; Developer chuyển In Progress; sửa xong chuyển Done và comment “Please retest”; QA retest — nếu hết lỗi thì Closed/Done với comment thành công; nếu còn lỗi thì Reopened, comment “The issue still occurs”, rồi Dev sửa tiếp.

Lifecycle này chứng minh nhóm không chỉ “tạo ticket” mà còn **đóng vòng kiểm soát chất lượng**.

---

## Slide 8 — Case project N11 (40 giây)

Trên TrendStyle Fashion tại localhost/SportShop, nhóm tạo Story N11-1 về đăng nhập, rồi log ba bug thuộc ba nhóm: chức năng, giao diện, dữ liệu — đều từ **test thật**, có screenshot và link về Story.

---

## Slide 9 — Bug 1 Login email (1 phút)

Theo tình huống đề: mở Login, nhập thông tin hợp lệ, quan sát kết quả.

Khi nhập **email** đã có trong database, hệ thống báo sai tài khoản hoặc mật khẩu. Đối chứng: đăng nhập bằng **username** `jm_001` mật khẩu `password` thì vào được trang chủ.

Kết luận: form và logic hiện chỉ nhận username, trong khi người dùng thường quen đăng nhập bằng email. Nhóm xếp **P2 Critical** vì ảnh hưởng nghiêm trọng đến trải nghiệm đăng nhập, nhưng chưa phải P1 vì site vẫn mở được.

---

## Slide 10 — Bug 2 UI & Bug 3 Search (1 phút 15 giây)

**Bug 2 — UI, P4:** Trên viewport iPhone 12, trang Login bị cuộn ngang do lớp nền ambient-glow tràn mép. Người dùng vẫn bấm đăng nhập được nên chỉ Minor.

**Bug 3 — Dữ liệu, P3:** Tìm “áo polo” vẫn ra “Áo sơ mi Oxford”, vì truy vấn tìm cả trong tên danh mục “Áo Polo & Sơ Mi”. Ảnh hưởng độ tin cậy tìm kiếm và quyết định mua hàng.

Với Bug 3, nhóm cố ý chạy thêm vòng **Reopened**: Dev báo đã sửa, QA retest vẫn còn lỗi, mở lại ticket, rồi mới Closed sau lần sửa tiếp theo — đúng yêu cầu đề về lifecycle.

---

## Slide 11 — Quy trình thực hành 5 bước + demo (1 phút + demo)

Tóm tắt thao tác: tạo project Kanban → tạo Story → test web và Create Bug đủ trường → link Story và chọn Priority → chạy lifecycle kèm Retest.

*(Demo 1–2 phút: mở board N11 trên trình duyệt, chỉ Story N11-1, mở một Bug, scroll Description, chỉ Linked Issue, chỉ comment Retest, kéo status nếu cần.)*

Trong demo, em sẽ không đọc hết Description — chỉ chỉ các mục Environment, Steps, Actual và Priority Justification để hội đồng thấy Bug Report đủ chuẩn.

---

## Slide 12 — Kết luận & cảm ơn (40 giây)

Qua bài thực hành, nhóm thấy Jira giúp chuẩn hóa cách log lỗi, ưu tiên sửa chữa và làm rõ trách nhiệm giữa Tester, Developer và QA Retest.

Bài học chính: Actual Result phải từ test thật; Summary và Priority phải có lý do; Lifecycle — nhất là Reopened — mới thể hiện quản lý bug đúng nghĩa.

Hướng mở rộng sau này: dashboard theo Priority, gắn commit/PR, hoặc đẩy bug từ test tự động lên Jira qua API.

Em xin cảm ơn thầy/cô và các bạn. Nhóm sẵn sàng nhận câu hỏi.

---

## Phần hỏi đáp — câu dự phòng

**Jira có tự test được không?**  
Không. Jira quản lý issue; việc test diễn ra trên ứng dụng hoặc tool tự động hóa.

**Vì sao không chọn P1 cho Bug login email?**  
Vì toàn site vẫn truy cập được; người dùng vẫn login bằng username. P1 dành cho tình huống chặn gần như toàn bộ hệ thống.

**Vì sao Bug search là P3 không phải P2?**  
Ảnh hưởng đáng kể nhưng không chặn đăng nhập/thanh toán; hệ thống vẫn hoạt động.

**Làm sao chứng minh test thật?**  
Screenshot Actual Result, Environment cụ thể, đối chứng case pass (username login), và mô tả khớp URL localhost/SportShop.

**Khó khăn lớn nhất?**  
Lúc đầu vào nhầm Product Discovery; sau đó phải sửa Summary/Actual cho khớp test thật thay vì copy mẫu; và nhớ chạy đủ Reopened cho một bug.

---

## Checklist trước giờ thuyết trình

- [ ] Mở sẵn `thuyet-trinh-jira.html` full màn hình  
- [ ] Đăng nhập Jira, mở board N11 (tab sẵn)  
- [ ] Mở sẵn 3 Bug + Story để demo  
- [ ] Có screenshot trong Attachment  
- [ ] Phân vai & đồng hồ 8–10 phút  
- [ ] In PDF slide dự phòng nếu mất mạng  

---

## Gợi ý chuyển sang PowerPoint

1. Mở `thuyet-trinh-jira.html` → Ctrl+P → Save as PDF (mỗi slide 1 trang nhờ CSS print).  
2. Trong PowerPoint: **Insert → New Slide** theo từng trang PDF, hoặc dùng **Insert → Object / Pictures** từ ảnh chụp từng slide.  
3. Dán **Notes** từ file này vào phần Notes của từng slide PPT.  
4. Theme gợi ý: nền trắng, accent xanh Atlassian `#0052CC`, font Calibri/Segoe, tránh quá nhiều chữ — nói theo kịch bản, slide chỉ gạch đầu dòng.
