# Kết quả TEST THẬT – TrendStyle Fashion (SportShop)
Ngày test: 15/09/2026 | Browser: Chrome | OS: Windows 11 | Server: XAMPP localhost

URL: http://localhost/SportShop

---

## BUG 1 — Lỗi chức năng (đúng tình huống Login của đề)

### Kết quả test
| Bước đề | Việc làm | Kết quả thật |
|---|---|---|
| 1 | Mở http://localhost/SportShop/auth/login | OK |
| 2 | Nhập email hợp lệ trong DB: `hue@example.com` | Điền được |
| 3 | Nhập password | Điền được |
| 4 | Nhấn ĐĂNG NHẬP NGAY | Submit |
| 5 | Quan sát | **FAIL** — hiện: “Tài khoản hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa!” |

Đối chứng: đăng nhập bằng **username** `jm_001` / `password` → **thành công**, vào trang chủ.

Nguyên nhân quan sát: form chỉ có ô **TÀI KHOẢN** (username); code login chỉ tìm theo `username`, không nhận email dù user có email trong DB.

### Dán vào Jira (sửa N11-2 hoặc tạo Bug mới)

**Summary:**
```text
Không đăng nhập được bằng email hợp lệ trên trang Login — người dùng quen dùng email không vào được hệ thống
```

**Priority:** High / **P2 Critical**

**Description:**
```text
## Environment
- App: TrendStyle Fashion — http://localhost/SportShop
- Browser: Google Chrome
- OS: Windows 11
- Server: XAMPP (Apache + MySQL), HTTP localhost

## Steps to Reproduce
1. Mở http://localhost/SportShop/auth/login
2. Tại ô TÀI KHOẢN, nhập email hợp lệ đã có trong hệ thống (ví dụ: hue@example.com)
3. Nhập mật khẩu của tài khoản đó
4. Nhấn nút “ĐĂNG NHẬP NGAY”
5. Quan sát thông báo / trang sau submit

## Expected Result
Người dùng đăng nhập thành công bằng email (hoặc hệ thống hướng dẫn rõ chỉ chấp nhận username). Theo kịch bản kiểm thử đăng nhập bằng thông tin hợp lệ, tài khoản phải vào được hệ thống.

## Actual Result
Hệ thống báo: “Tài khoản hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa!” và vẫn ở trang Login.
Đối chứng: cùng hệ thống, đăng nhập bằng username hợp lệ (jm_001 / password) thì thành công.

## Priority Justification (P2 – Critical)
Chặn một cách đăng nhập phổ biến (email). Ảnh hưởng nghiêm trọng đến trải nghiệm đăng nhập. Site vẫn mở được nên không chọn P1.

## Attachment
Screenshot thông báo lỗi khi login bằng email + screenshot login thành công bằng username (đối chứng).
```

**Link:** relates to N11-1  
**Lifecycle:** To Do → In Progress → Done + comment Retest (đã làm ở N11-2 thì **sửa lại Description/Summary** cho khớp test thật)

---

## BUG 2 — Lỗi giao diện (P4)

### Kết quả test
1. Mở Login với viewport mobile **390×844** (iPhone 12).
2. Đo layout: `document.documentElement.scrollWidth = 565` > `clientWidth = 390` → **có thanh cuộn ngang**.
3. Phần tử gây lệch: `.ambient-glow-1`, `.ambient-glow-2` tràn khỏi mép màn hình.

Phát hiện thêm (có thể ghi trong cùng Bug hoặc Bug UI khác):
- Trang sản phẩm vẫn hiện câu: “Tìm đôi giày phù hợp…” (sót nội dung SportShop cũ) — sai ngữ cảnh thời trang.

### Dán Jira (sửa N11-3)

**Summary:**
```text
Trang Login bị cuộn ngang trên màn hình điện thoại — giao diện lệch do lớp nền ambient-glow
```

**Priority:** Low / **P4 Minor**

**Description:**
```text
## Environment
- App: TrendStyle Fashion — trang Login
- Browser: Chrome Device Mode — iPhone 12 (390×844)
- OS: Windows 11
- Server: localhost

## Steps to Reproduce
1. Mở http://localhost/SportShop/auth/login
2. F12 → Toggle device toolbar → iPhone 12 (390×844)
3. Thử vuốt ngang trang / quan sát thanh cuộn ngang
4. Quan sát các lớp nền trang trí quanh form Login

## Expected Result
Trang Login vừa khít viewport mobile, không cuộn ngang, nút/form không bị lệch do phần tử trang trí.

## Actual Result
Trang xuất hiện cuộn ngang (scrollWidth ~565px > 390px). Các lớp .ambient-glow-1 / .ambient-glow-2 tràn ra ngoài mép màn hình. Form vẫn dùng được nhưng trải nghiệm mobile kém.

## Priority Justification (P4 – Minor)
Lỗi UI/UX mobile, vẫn đăng nhập được → P4. Không chọn P5 vì cuộn ngang ảnh hưởng rõ trên điện thoại thật.
```

**Link:** N11-1 | Lifecycle → Done + Retest comments

---

## BUG 3 — Lỗi dữ liệu tìm kiếm (P3) + Reopened

### Kết quả test
1. Search từ khóa: **áo polo** → URL `http://localhost/SportShop/product?q=áo%20polo`
2. Kết quả 2 sản phẩm:
   - **Áo Sơ Mi Oxford Form Regular Thanh Lịch** (không phải áo polo) ← không liên quan đúng nghĩa từ khóa
   - Áo Polo Nam Pima Cotton Cao Cấp (đúng)
3. Lý do: search `LIKE` trên **category** “Áo Polo & Sơ Mi” nên kéo cả sơ mi không phải polo.

### Dán Jira (tạo Bug mới, ví dụ N11-4)

**Summary:**
```text
Tìm kiếm “áo polo” trả về áo sơ mi không phải polo — người dùng nhận kết quả không liên quan đến từ khóa
```

**Priority:** Medium / **P3 Major**

**Description:**
```text
## Environment
- App: TrendStyle Fashion — trang sản phẩm / tìm kiếm
- Browser: Google Chrome
- OS: Windows 11
- Server: localhost

## Steps to Reproduce
1. Mở http://localhost/SportShop/product (hoặc ô tìm trên header)
2. Nhập từ khóa: áo polo
3. Nhấn TÌM / Enter
4. Xem danh sách “KẾT QUẢ CHO ‘ÁO POLO’”

## Expected Result
Chỉ (hoặc ưu tiên) sản phẩm đúng là áo polo / tên hoặc mô tả chứa polo.
Không trả về sản phẩm rõ ràng là áo sơ mi nếu từ khóa là “áo polo”.

## Actual Result
Hệ thống trả 2 sản phẩm, trong đó có “Áo Sơ Mi Oxford Form Regular Thanh Lịch” (không phải polo), vì khớp theo tên danh mục “Áo Polo & Sơ Mi”.

## Priority Justification (P3 – Major)
Ảnh hưởng đáng kể độ tin cậy tìm kiếm và quyết định mua hàng, site vẫn chạy → P3 Major.
```

**Lifecycle bắt buộc:**
1. Done + `The issue has been fixed. Please retest.`
2. Đổi lại In Progress + `Retested. The issue still occurs.` ← **chụp Reopened**
3. Done + `Retested successfully. The issue is fixed.`

---

## Việc bạn làm trên Jira ngay

1. Mở **N11-2** → sửa Summary + Description theo **BUG 1** ở trên (test thật). Giữ Done + link N11-1. Đính kèm screenshot lỗi email.
2. Mở **N11-3** → sửa theo **BUG 2** (cuộn ngang mobile). Priority Low.
3. **Create Bug mới** theo **BUG 3** + chạy Reopened.
4. Chụp minh chứng nộp bài.

## Tài khoản test đã dùng
- Thành công: `jm_001` / `password`
- Email fail: `hue@example.com` (có trong DB, login bằng email thất bại)
