# Lời thuyết trình Playwright — Sport Shop

Dùng kèm file slide: `docs/thuyet-trinh-playwright.html`  
Mở bằng trình duyệt, phím mũi tên để chuyển. In PDF nếu cần dán vào PowerPoint.

Thời lượng gợi ý: **8–10 phút** nói + **2 phút** demo.

---

## Slide 1 — Bìa (30 giây)

Em xin trình bày về **Playwright** và cách nhóm dùng công cụ này để kiểm thử tự động website **Sport Shop**.

Playwright là framework mã nguồn mở do Microsoft phát triển, giấy phép Apache 2.0. Bộ kiểm thử của bài viết bằng **Python**, chạy trên engine **Chromium**.

---

## Slide 2 — Mục lục (20 giây)

Phần trình bày gồm sáu ý: Playwright là gì, dùng để làm gì, nguyên lý, tính năng nổi bật, ngôn ngữ, và phần áp dụng đúng code trong bài.

---

## Slide 3 — Playwright là gì? (1 phút)

Playwright dùng để **tự động hóa trình duyệt** và kiểm thử web hiện đại. Viết một bộ mã, chạy trên ba engine:

- **Chromium** — nền tảng Chrome và Edge. Đây là engine bài đang dùng.
- **Firefox**
- **WebKit** — engine của Safari

Playwright Test bản TypeScript có sẵn test runner, assertion, chạy song song, HTML Report, Trace Viewer, chạy được Windows / Linux / macOS, có giao diện hoặc chạy nền.

Bài này **không** dùng Playwright Test TypeScript mà dùng **API Python** `sync_playwright`, vì cần đọc Excel và gọi fixture PHP. Ý tưởng vẫn giống: mở trình duyệt, thao tác, so sánh kết quả.

---

## Slide 4 — Dùng để làm gì? (1 phút)

Playwright thường dùng cho: kiểm thử giao diện, E2E, đăng nhập/đăng ký/phân quyền, CRUD, form, tải ảnh, API, nhiều trình duyệt, giả lập mobile, chụp ảnh/quay video, CI/CD.

Trên Sport Shop, bộ test đã phủ:

| Module | Số case Excel |
|---|---|
| Đăng ký thành viên | 100 |
| Quản lý người dùng | 64 |
| Quản lý sản phẩm | 54 |
| Quản lý đánh giá | 32 |
| Dashboard / doanh thu | 24 |
| **Tổng** | **274** |

Ngoài ra có case phân quyền ADMIN–USER và case xóa user không làm mất cấu hình **thanh toán QR**.

---

## Slide 5 — Nguyên lý hoạt động (1 phút)

Quy trình tám bước: khởi động browser → tạo BrowserContext → mở trang → tìm Locator → thao tác như người dùng → so sánh expected → Pass/Fail → xuất báo cáo, ảnh, video hoặc trace.

Điểm then chốt: **mỗi BrowserContext là một phiên độc lập**. Cookie và tài khoản không lẫn nhau.

Trong `playwright_qlcuahang.py`, em tạo **ba context**:

- `guest` — chưa đăng nhập, dùng case “vào admin khi chưa login”
- `user` — phiên khách hàng, dùng case “USER không vào được trang quản trị”
- `admin` — phiên quản trị, dùng CRUD

Cả ba cùng locale `vi-VN`, viewport 1366×768.

---

## Slide 6 — Tính năng nổi bật (1 phút)

Chỉ nêu những tính năng **đã dùng trong bài**, tránh đọc cả bảng:

1. **Locator** — tìm dòng khách theo `@username`, nút `has-text('Lưu thay đổi')`
2. **Auto-waiting** — `wait_for_selector("#adminUsername")` trước khi `fill`
3. **Test isolation** — 3 context + PHP xóa user `e2e_*` sau mỗi lần chạy
4. **Screenshot** — mỗi case một ảnh full page
5. **API testing** — `page.request.fetch` kiểm tra HTTP 405, CSRF giả, upload ảnh
6. **Codegen** — hỗ trợ soạn locator, mã vẫn phải chuẩn hóa
7. Báo cáo bài này là **Excel** đúng form test case, không dùng HTML Report mặc định

---

## Slide 7 — Auto-waiting (45 giây)

Trước khi click, Playwright tự kiểm tra: phần tử có tồn tại, có hiện, có bật, có ổn định, có nhận chuột.

Nhờ đó ít dùng `waitForTimeout()` mù. File `pw_common.py` hàm `login_admin`: chờ `#adminUsername` → điền → click nút `.admin-login-submit` → kiểm tra URL có `admin-dashboard`.

---

## Slide 8 — Codegen (30 giây)

Lệnh: `npx playwright codegen http://localhost/SportShop`

Thao tác trên web, Playwright sinh `click()` / `fill()`. **Không đưa nguyên mã sinh ra vào bài.** Nhóm gom thành helper dùng chung để 64 case viết cùng một kiểu.

---

## Slide 9 — Ngôn ngữ (45 giây)

Playwright hỗ trợ JS, TS, Python, Java, .NET. TypeScript + Playwright Test là lựa chọn “đủ đồ” nhất.

Sport Shop là **PHP MVC**, không phải Spring Boot Java. Chọn Python vì:

- `openpyxl` đọc/ghi đúng file Excel thầy/cô yêu cầu
- `subprocess` gọi `fixtures.php` để tạo dữ liệu test trên MySQL
- Thư mục `tests/e2e/` tách khỏi mã cửa hàng

Thư viện: Playwright 1.55.0, openpyxl 3.1.5.

---

## Slide 10 — Kiến trúc bài (1 phút)

Luồng:

**Excel gốc** → `catalog.py` (chỉ đọc) → `sheet_nguoidung.py` (Playwright) → `pw_common.write_excel()` (file mới) + thư mục ảnh.

Không sửa cột A–G của Excel gốc. Chỉ ghi cột H Actual, I Pass/Fail, J Date.

`fixtures.php` nhận JSON stdin: tạo user `e2e_*`, đơn hàng, đánh giá, rồi `cleanup` khi xong.

---

## Slide 11 — Phạm vi 274 case (30 giây)

Năm sheet tương ứng năm file `sheet_*.py`. Runner mặc định chỉ chạy **nguoidung (64 case)** — phù hợp demo. Các sheet kia đã viết sẵn.

---

## Slide 12 — Phân quyền (1 phút, nên đọc code)

Case 1: `guest` mở `/admin-user/index` → bị đẩy về `/admin-auth/login`.

Case 2: login USER rồi mở cùng URL → vẫn về trang login admin. Phiên bán hàng **không** thế phiên quản trị.

Case 3: admin login thành công, thấy tiêu đề Người dùng, ô tìm kiếm, bảng.

Đây là ví dụ sống của BrowserContext.

---

## Slide 13 — UI + API (45 giây)

Hai mặt của Playwright:

- UI: `fill`, `select_option`, `click("button:has-text('Lưu thay đổi')")`
- API: POST trang danh sách phải ra **405**; CSRF `fake` không được lưu tên `HACK`

Không cần Postman riêng cho các case bảo vệ thao tác.

---

## Slide 14 — Đầu ra (30 giây)

Mỗi case: so sánh → `Pass`/`Fail` → chụp PNG. File `ketqua_nguoidung.xlsx` copy từ Excel gốc. Ô Pass tô xanh, Fail tô đỏ. Nếu crash giữa chừng, case còn lại ghi lý do thiếu, không im lặng bỏ sót.

---

## Slide 15 — Cách chạy / Demo (2 phút)

Nhắc giám khảo: Apache + MySQL đang bật.

```powershell
$env:SPORTSHOP_HEADLESS = "0"
python tests\e2e\playwright_qlcuahang.py
```

Demo ngắn nếu còn giờ: để Chromium hiện, chỉ cần thấy login admin + danh sách người dùng. Không cần đợi hết 64 case.

---

## Slide 16 — Kết luận (30 giây)

Playwright tự động hóa ba engine, phù hợp E2E. Sport Shop đã gắn vào đúng chức năng quản trị, tách phiên guest/user/admin, đọc Excel và trả Excel + ảnh. Giấy phép Apache 2.0 dùng được cho đồ án.

---

## Câu hỏi thường gặp (chuẩn bị trước)

**1. Sao không dùng Selenium?**  
Playwright có auto-waiting, đa engine, API testing trong cùng Page, context isolation sẵn. Selenium cũng làm được nhưng phải tự viết nhiều phần chờ và quản lý phiên.

**2. Sao không TypeScript?**  
Cần Excel + PHP fixture. Python gắn ba thứ đó ngắn hơn trong phạm vi đồ án.

**3. Test có làm hỏng dữ liệu thật không?**  
User test có prefix `e2e_`. `cleanup` xóa đúng nhóm đó, không xóa admin.

**4. Flaky test?**  
Vẫn còn vài `wait_for_timeout` sau load. Hướng cải thiện: chờ locator cụ thể (flash, bảng) thay vì ngủ 250–400ms.

**5. CI/CD?**  
Chưa gắn GitHub Actions. Có thể chạy headless trên Linux sau khi cài `playwright install chromium`.

---

## Câu nên nhấn khi bảo vệ

> “Em không chỉ giới thiệu Playwright, mà đã chạy đúng file Excel test case: đầu vào `catalog.py`, xử lý trên ba BrowserContext, đầu ra Excel + screenshot từng case.”
