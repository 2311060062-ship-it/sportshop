# BÁO CÁO
## Log và quản lý Bug trên Jira  
### Áp dụng trên website TrendStyle Fashion (Sport Shop)

**Nhóm:** NHOM11  
**Project Jira:** `NHOM11_SOFTWARE_TESTING` (Key: `N11`)  
**Ứng dụng kiểm thử:** http://localhost/SportShop  
**Công cụ:** Jira Software (Kanban, Team-managed)

---

## 1. Giới thiệu và mục tiêu báo cáo

Trong quy trình phát triển phần mềm, **phát hiện lỗi chưa đủ** — nhóm cần một hệ thống để ghi nhận, ưu tiên, theo dõi sửa chữa và xác nhận lại (retest). **Jira** (Atlassian) là công cụ quản lý công việc phổ biến, trong đó Issue type **Bug** dùng để log và quản lý lỗi.

Báo cáo này nhằm:
1. Trình bày khái niệm Jira và vai trò trong kiểm thử phần mềm.
2. Phân tích cấu trúc Bug Report chuẩn và Bug Lifecycle.
3. Mô tả case study thực tế trên **TrendStyle Fashion**.
4. Hướng dẫn thực hành từng bước trên Jira để nhóm tái hiện / minh chứng.
5. Rút kinh nghiệm phục vụ thuyết trình.

> Lưu ý: **Jira không tự chạy test.** Tester kiểm thử trên web → ghi nhận kết quả lên Jira → Developer sửa → QA retest → cập nhật trạng thái.

---

## 2. Jira là gì? Vì sao dùng trong kiểm thử?

### 2.1. Khái niệm
**Jira** là nền tảng quản lý dự án / issue tracking của Atlassian. Trong kiểm thử, nhóm dùng Jira để:
- Tạo **Story** mô tả chức năng cần kiểm.
- Tạo **Bug** khi phát hiện lỗi.
- Gắn **Priority**, **Attachment**, **Linked Issue**.
- Theo dõi vòng đời: Open → In Progress → Resolved/Done → Closed / Reopened.

### 2.2. Phân biệt nhanh
| Công cụ | Vai trò |
|---|---|
| **Jira** | Quản lý bug, story, workflow, báo cáo tiến độ |
| **Trình duyệt / manual test** | Thực hiện kiểm thử tay |
| **Playwright / JMeter** | Tự động hóa kiểm thử (không thay Jira) |

### 2.3. Project phù hợp bài lab
Cần **Jira Software** (Kanban/Scrum), **không** dùng Jira Product Discovery (chỉ quản lý idea).

Board chuẩn: **To Do | In Progress | Done**.

---

## 3. Phân tích trường hợp (Case study)

### 3.1. Bối cảnh
Nhóm kiểm thử website thương mại điện tử thời trang **TrendStyle Fashion**, tạo project Jira `NHOM11_SOFTWARE_TESTING`, liên kết bug với Story đăng nhập.

### 3.2. Story gốc
| Trường | Nội dung |
|---|---|
| Key | N11-1 |
| Type | Story |
| Summary | Là người dùng, tôi muốn đăng nhập bằng tài khoản hợp lệ để truy cập vào hệ thống |

Story đóng vai trò **yêu cầu nghiệp vụ**; mọi Bug liên quan được **link** về Story này để truy vết.

### 3.3. Công thức Summary tốt
Theo đề bài: **[Cái gì bị lỗi] + [Ở đâu] + [Ảnh hưởng gì]**

| | Summary | Đánh giá |
|---|---|---|
| A | Login not working | Kém — quá chung |
| B | Nút Login không bấm được trên Chrome — người dùng không thể đăng nhập | Tốt — đủ 3 yếu tố |

**Viết lại A:**  
`Không đăng nhập được bằng tài khoản hợp lệ trên trang Login (Chrome) — người dùng không truy cập được hệ thống`

### 3.4. Cấu trúc Bug Report bắt buộc
| Trường | Ý nghĩa |
|---|---|
| Summary | Tiêu đề theo công thức 3 phần |
| Priority | P1–P5 kèm giải thích 2–3 câu |
| Environment | Browser, OS, Server, URL |
| Steps to Reproduce | Các bước tái hiện theo thứ tự |
| Expected Result | Kết quả đúng theo yêu cầu |
| Actual Result | Kết quả quan sát khi test **thật** |
| Attachment | Screenshot / video |
| Linked Issue | Liên kết Bug ↔ Story |

### 3.5. Thang Priority
| P | Mức | Khi chọn |
|---|---|---|
| P1 | Blocker | Chặn toàn hệ thống |
| P2 | Critical | Ảnh hưởng nghiêm trọng |
| P3 | Major | Ảnh hưởng đáng kể, hệ thống vẫn chạy |
| P4 | Minor | Nhỏ, có workaround |
| P5 | Trivial | Thẩm mỹ / rất nhỏ |

**Nguyên tắc:** Không mặc định mọi lỗi là Critical.

### 3.6. Bug Lifecycle
```
Tester log Bug → Open (To Do)
       ↓
Developer nhận → In Progress
       ↓
Developer sửa xong → Resolved / Done
       ↓
QA Retest → Closed (Done)   hoặc   Reopened (về In Progress)
```

Comment mẫu:
- Dev: `The issue has been fixed. Please retest.`
- QA OK: `Retested successfully. The issue is fixed.`
- QA FAIL: `Retested. The issue still occurs.`

---

## 4. Kết quả kiểm thử thật trên TrendStyle

Nhóm **không bịa bug**: thực hiện test trên `http://localhost/SportShop`, ghi Actual Result đúng hiện tượng.

### Bug 1 — Chức năng đăng nhập (P2 / High) — ví dụ N11-2
| Hạng mục | Nội dung |
|---|---|
| Summary | Không đăng nhập được bằng email hợp lệ trên trang Login — người dùng quen dùng email không vào được hệ thống |
| Steps | Mở Login → nhập email `hue@example.com` → nhập password → Đăng nhập |
| Expected | Đăng nhập thành công bằng email hoặc hướng dẫn rõ chỉ nhận username |
| Actual | Báo “Tài khoản hoặc mật khẩu không đúng…”; đối chứng username `jm_001`/`password` thì vào được |
| Priority | P2 Critical — chặn cách đăng nhập phổ biến (email) |

### Bug 2 — Giao diện mobile (P4 / Low) — ví dụ N11-3
| Hạng mục | Nội dung |
|---|---|
| Summary | Trang Login bị cuộn ngang trên màn hình điện thoại — giao diện lệch do lớp nền ambient-glow |
| Steps | Login → F12 Device Mode iPhone 12 (390×844) → quan sát cuộn ngang |
| Actual | `scrollWidth` > viewport; `.ambient-glow-*` tràn mép |
| Priority | P4 Minor — vẫn đăng nhập được |

### Bug 3 — Dữ liệu tìm kiếm (P3 / Medium) — ví dụ N11-4
| Hạng mục | Nội dung |
|---|---|
| Summary | Tìm kiếm “áo polo” trả về áo sơ mi không phải polo — người dùng nhận kết quả không liên quan |
| Steps | Search `áo polo` → xem kết quả |
| Actual | Có “Áo Sơ Mi Oxford…” vì search LIKE cả **tên danh mục** “Áo Polo & Sơ Mi” |
| Priority | P3 Major |
| Lifecycle đặc biệt | Done → **Reopened** (retest còn lỗi) → Done |

---

## 5. Hướng dẫn thực hành trên Jira (chi tiết nộp minh chứng)

### Bước A — Tạo project đúng loại
1. Đăng nhập Atlassian → mở app **Jira** (không phải Product Discovery).
2. Create project → template **Kanban** → Team-managed.
3. Name: `NHOM11_SOFTWARE_TESTING`, Key: `N11`.
4. Chụp Board To Do / In Progress / Done.

### Bước B — Tạo Story
1. Create → Type **Story**.
2. Summary: câu user story đăng nhập.
3. Ghi lại key (N11-1).

### Bước C — Test web rồi mới Create Bug
1. Test Login / Mobile / Search trên TrendStyle.
2. Chụp screenshot **thật**.
3. Create → Type **Bug** → dán Summary/Description đủ trường.
4. Priority đúng P2/P4/P3.
5. Attach ảnh + Link relates to N11-1.

### Bước D — Chạy Lifecycle + Retest
Với mỗi Bug: To Do → In Progress → Done + comment.  
Với ít nhất 1 Bug (Bug 3): thêm bước **Reopened**.

### Bước E — Checklist trước khi kết thúc báo cáo
- [ ] Đúng Issue Type = Bug  
- [ ] Summary đủ 3 yếu tố  
- [ ] Steps / Expected / Actual / Environment  
- [ ] Screenshot  
- [ ] Priority có giải thích  
- [ ] Linked Story  
- [ ] Lifecycle + Retest  
- [ ] Có Closed và có Reopened  

---

## 6. Trả lời câu hỏi báo cáo (mẫu)

1. **Phát hiện bao nhiêu Bug?** ≥ 03 (chức năng, UI, dữ liệu).  
2. **Priority cao nhất?** Bug 1 — P2, vì ảnh hưởng nghiêm trọng đến đăng nhập bằng email.  
3. **Bug nào Closed?** Các bug sau retest thành công (N11-2, N11-3, N11-4…).  
4. **Bug nào Reopened?** Bug tìm kiếm — retest lần 1 vẫn còn kết quả không liên quan.  
5. **Khó khăn?** Viết Summary đủ ý; chọn Priority đúng; mô tả Actual theo test thật; cập nhật status đúng vòng đời; phân biệt Discovery vs Software project.

---

## 7. Kết luận

Jira giúp nhóm **chuẩn hóa** việc log bug, ưu tiên sửa chữa và minh bạch trách nhiệm Tester–Developer–QA Retest. Trên case TrendStyle, nhóm đã:
- Tạo project Kanban và Story nghiệp vụ.
- Log 3 bug thuộc 3 nhóm lỗi khác nhau từ **test thật**.
- Thực hành đủ Bug Lifecycle, gồm Reopened.

Hướng mở rộng: tích hợp Jira với CI, gắn commit/PR, dùng filter/dashboard theo Priority, kết hợp Playwright để tự động phát hiện rồi vẫn **log tay hoặc qua API** lên Jira.

---

## 8. Tài liệu kèm theo
| File | Mục đích |
|---|---|
| `docs/Bai_thuc_hanh_Jira_Bug_That.md` | Nội dung bug theo test thật |
| `docs/Bai_thuc_hanh_Jira_Nop_Bai.md` | Hướng dẫn theo đề PDF |
| `docs/thuyet-trinh-jira.html` | Slide thuyết trình (mở Chrome / in PDF → PPT) |
| `docs/loi-thuyet-trinh-jira.md` | Kịch bản thuyết trình đầy đủ |
