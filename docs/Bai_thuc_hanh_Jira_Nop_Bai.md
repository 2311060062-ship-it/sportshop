# BÀI THỰC HÀNH — LOG VÀ QUẢN LÝ BUG TRÊN JIRA
## Làm theo file: `Bai_thuc_hanh_Log_va_quan_ly_Bug_tren_Jira.pdf`
## Ứng dụng kiểm thử: Sport Shop (`http://localhost/SportShop`)

> Copy–paste từng khối vào Jira. Chụp ảnh theo mục 11 + checklist mục 13.

---

## 1. Mục tiêu (đã cover khi làm xong file này)

- Tạo Bug Issue trên Jira  
- Summary theo: **[Cái gì bị lỗi] + [Ở đâu] + [Ảnh hưởng gì]**  
- Có Steps / Expected / Actual / Environment / Attachment  
- Chọn Priority P1–P5 và giải thích  
- Lifecycle: Open → In Progress → Resolved → Closed/Reopened  
- Retest sau khi Dev sửa  

---

## 2. Phân vai nhóm (ghi vào báo cáo)

| Vai trò | Ai làm | Nhiệm vụ |
|---|---|---|
| Tester/QA | … | Phát hiện & log Bug |
| Developer | … | Nhận Bug, đổi status, comment |
| QA Retest | … | Retest → Closed hoặc Reopened |
| Team Leader | … | Theo dõi Issue, tổng hợp nộp |

---

## 3. Công cụ

- Tài khoản Jira (Atlassian)  
- Chrome / Edge  
- Sport Shop trên XAMPP  
- Công cụ chụp màn hình (Win+Shift+S)  

---

## 4. PHẦN 1 — Tạo Project và Story

### Bước 1 — Project
1. Vào Jira → **Create project**  
2. Tên gợi ý PDF: `NHOM01_SOFTWARE_TESTING` (đổi `01` = số nhóm)  
3. Template: **Kanban** hoặc **Scrum** (Team-managed)

### Bước 2 — Tạo 01 Story

| Trường | Dán vào Jira |
|---|---|
| **Issue Type** | Story |
| **Summary** | Là người dùng, tôi muốn đăng nhập bằng tài khoản hợp lệ để truy cập vào hệ thống |
| **Description** | Là khách hàng Sport Shop, tôi muốn đăng nhập bằng email/password hợp lệ để vào trang tài khoản / mua hàng. Acceptance: (1) Login đúng → vào hệ thống; (2) UI form dùng được; (3) Tìm kiếm sản phẩm trả kết quả liên quan. |

Ghi lại key Story (ví dụ `NST-1`) để **Linked Issue** với 3 Bug.

---

## 5. PHẦN 2 — Phát hiện và log Bug (tình huống Login theo PDF)

Thực hiện trên Sport Shop:

1. Mở trang Login: `http://localhost/SportShop/auth/login`  
2. Nhập email hợp lệ  
3. Nhập password hợp lệ  
4. Nhấn nút Login  
5. Quan sát kết quả → nếu lỗi → **Create Issue Type = Bug**

---

## 6. Nội dung Bug Report bắt buộc (PDF)

Mỗi Bug phải có đủ:

| Trường | Yêu cầu PDF |
|---|---|
| Summary | [Lỗi gì] + [Ở đâu] + [Ảnh hưởng] |
| Priority | P1–P5 + giải thích 2–3 câu |
| Environment | Browser, version, OS, Server |
| Steps to Reproduce | Theo đúng thứ tự |
| Expected Result | Theo yêu cầu/thiết kế |
| Actual Result | Thực tế đang xảy ra |
| Attachment | Screenshot (video/log nếu có) |
| Linked Issue | Link với Story |

Ví dụ Summary (đúng mẫu PDF):  
`Nút Login không bấm được trên Chrome — người dùng không thể đăng nhập`

---

## 7. PHẦN 3 — Bảng Priority (dùng đúng PDF)

| Priority | Mức độ | Khi nào chọn |
|---|---|---|
| **P1** | Blocker | Chặn toàn bộ hệ thống |
| **P2** | Critical | Ảnh hưởng nghiêm trọng |
| **P3** | Major | Ảnh hưởng đáng kể, hệ thống vẫn chạy |
| **P4** | Minor | Ảnh hưởng nhỏ, có workaround |
| **P5** | Trivial | Lỗi rất nhỏ/thẩm mỹ, không ảnh hưởng chức năng |

**Lưu ý PDF:** Không mặc định mọi lỗi là Critical. Phải giải thích 2–3 câu.

Map sang Jira (nếu không có nhãn P1–P5): Highest=P1, High=P2, Medium=P3, Low=P4, Lowest=P5 — vẫn ghi **P2 Critical**… trong Description.

---

## 8. PHẦN 4 — Bug Lifecycle (làm trên từng Bug)

1. Tester tạo Bug → **Open**  
2. Developer nhận → **In Progress**  
3. Developer sửa xong → **Resolved**  
4. QA Retest hết lỗi → **Closed**  
5. QA Retest còn lỗi → **Reopened**

Comment mẫu (đúng PDF):

- Dev: `The issue has been fixed. Please retest.`  
- QA OK: `Retested successfully. The issue is fixed.`  
- QA FAIL: `Retested. The issue still occurs.`

---

## 9. PHẦN 5 — Bài tập 3 Bug (nội dung dán Jira)

### BUG 1 — Lỗi chức năng (theo ví dụ PDF: không đăng nhập được)

| Trường | Nội dung |
|---|---|
| **Issue Type** | Bug |
| **Summary** | Không thể đăng nhập bằng tài khoản hợp lệ trên trang Login Sport Shop — người dùng không truy cập được hệ thống |
| **Priority** | **P2 – Critical** |
| **Linked Issue** | relates to → Story |

```
## Environment
- App: Sport Shop — http://localhost/SportShop
- Browser: Google Chrome (ghi phiên bản: chrome://version)
- OS: Windows 11
- Server: XAMPP (Apache + MySQL)

## Steps to Reproduce
1. Mở trang Login: http://localhost/SportShop/auth/login
2. Nhập email hợp lệ đã đăng ký
3. Nhập password hợp lệ
4. Nhấn nút Login / Đăng nhập
5. Quan sát kết quả

## Expected Result
Đăng nhập thành công, chuyển vào trang chủ hoặc khu vực đã đăng nhập.

## Actual Result
Không đăng nhập được (ở lại trang Login / báo lỗi / không vào hệ thống) dù tài khoản hợp lệ.

## Priority Justification (P2 – Critical)
Lỗi ảnh hưởng nghiêm trọng đến luồng chính: khách không vào được hệ thống thì không mua hàng/xem đơn. Site vẫn mở được trang công khai nên không chọn P1 Blocker. Không chọn P3 vì đây là chức năng cốt lõi bị hỏng.
```

**Lifecycle gợi ý nộp:** Open → In Progress → Resolved → **Closed**  
Attachment: screenshot Login + URL sau khi bấm.

---

### BUG 2 — Lỗi giao diện (theo ví dụ PDF: nút lệch mobile → P4 hoặc P5)

| Trường | Nội dung |
|---|---|
| **Issue Type** | Bug |
| **Summary** | Nút Login bị lệch trên màn hình điện thoại (Chrome DevTools) — giao diện khó thao tác nhưng vẫn bấm được |
| **Priority** | **P4 – Minor** |
| **Linked Issue** | relates to → Story |

```
## Environment
- App: Sport Shop — trang Login
- Browser: Chrome DevTools Device Mode — iPhone 12 (390×844)
- OS: Windows 11
- Server: XAMPP localhost

## Steps to Reproduce
1. Mở http://localhost/SportShop/auth/login
2. F12 → Toggle device toolbar → chọn iPhone 12
3. Quan sát vị trí nút Login / Đăng nhập
4. Thử bấm nút

## Expected Result
Nút Login căn đều, không tràn/lệch mép, dễ bấm trên mobile.

## Actual Result
Nút Login bị lệch / sát mép / layout lệch trên mobile. Vẫn bấm được (có workaround).

## Priority Justification (P4 – Minor)
Đúng yêu cầu PDF chọn P4 hoặc P5. Đây là lỗi UI ảnh hưởng nhỏ, người dùng vẫn đăng nhập được bằng cách bấm nút → P4 Minor. Không chọn P5 vì vẫn ảnh hưởng trải nghiệm rõ (không chỉ lệch 1px thẩm mỹ).
```

**Lifecycle:** Open → In Progress → Resolved → **Closed**

---

### BUG 3 — Lỗi dữ liệu (theo ví dụ PDF: tìm kiếm trả kết quả không liên quan)

| Trường | Nội dung |
|---|---|
| **Issue Type** | Bug |
| **Summary** | Tìm kiếm sản phẩm trả về kết quả không liên quan đến từ khóa — người dùng khó tìm đúng hàng cần mua |
| **Priority** | **P3 – Major** |
| **Linked Issue** | relates to → Story |

```
## Environment
- App: Sport Shop — trang sản phẩm / tìm kiếm
- Browser: Microsoft Edge (ghi phiên bản)
- OS: Windows 11
- Server: XAMPP localhost

## Steps to Reproduce
1. Mở trang sản phẩm: http://localhost/SportShop/product
2. Nhập từ khóa cụ thể, ví dụ: "giày chạy bộ"
3. Nhấn Search / Enter
4. Quan sát danh sách kết quả

## Expected Result
Hiển thị sản phẩm có tên/mô tả liên quan đến từ khóa đã nhập.

## Actual Result
Hệ thống trả về nhiều sản phẩm không liên quan đến từ khóa.

## Priority Justification (P3 – Major)
Ảnh hưởng đáng kể đến mua hàng và độ tin cậy dữ liệu tìm kiếm, nhưng hệ thống vẫn hoạt động (xem SP, giỏ hàng…) → P3 Major theo bảng PDF. Không chọn P2 vì không chặn toàn bộ nghiệp vụ đăng nhập/thanh toán.
```

**Lifecycle bắt buộc có Reopened (để trả lời câu hỏi mục 12):**

1. Open (Tester)  
2. In Progress → Resolved (Dev) + comment: `The issue has been fixed. Please retest.`  
3. **Reopened** (QA) + `Retested. The issue still occurs.`  
4. In Progress → Resolved (Dev)  
5. **Closed** (QA) + `Retested successfully. The issue is fixed.`

---

## 10. Bài tập phân tích Summary (PDF mục 10)

Cho:

- **A.** `Login not working`  
- **B.** `Nút Login không bấm được trên Chrome — người dùng không thể đăng nhập`

### Trả lời nộp bài

1. **Chọn Summary tốt hơn:** **B**  
2. **Vì sao:** B đúng công thức PDF `[Lỗi gì] + [Ở đâu] + [Ảnh hưởng]`. A quá chung, không nói lỗi cụ thể, môi trường, ảnh hưởng → khó tái hiện và ước lượng.  
3. **Viết lại Summary A:**  
   `Không đăng nhập được bằng tài khoản hợp lệ trên trang Login (Chrome) — người dùng không truy cập được hệ thống`

---

## 11. Yêu cầu bài nộp (checklist PDF)

- [ ] Link Jira Project của nhóm  
- [ ] Danh sách tối thiểu **03 Bug**  
- [ ] Ảnh minh chứng: Project, danh sách Issue, chi tiết Bug  
- [ ] Ảnh minh chứng trạng thái Bug **sau khi Retest**  
- [ ] Báo cáo ngắn trả lời mục 12  

---

## 12. Câu hỏi báo cáo — đáp án mẫu

1. **Nhóm phát hiện bao nhiêu Bug?**  
   **03 Bug** (chức năng Login, UI mobile, dữ liệu tìm kiếm).

2. **Bug nào Priority cao nhất? Vì sao?**  
   **Bug 1 – P2 Critical.** Chặn đăng nhập → ảnh hưởng nghiêm trọng đến sử dụng hệ thống. Không chọn P1 vì toàn site chưa sập.

3. **Bug nào đã Closed?**  
   Bug 1, Bug 2, và Bug 3 (sau retest lần cuối). Ghi key thật: `NST-…`.

4. **Bug nào bị Reopened? Vì sao?**  
   **Bug 3.** QA retest lần 1 vẫn thấy tìm kiếm trả kết quả không liên quan → Reopened theo quy trình PDF.

5. **Khó khăn khi log Bug trên Jira?**  
   Viết Summary đủ 3 yếu tố; chọn Priority đúng (không mặc định Critical); đủ Environment + screenshot để Dev tái hiện; link Bug–Story; cập nhật status đúng lifecycle và ghi comment Retest.

---

## 13. Checklist trước khi nộp (đúng PDF)

- [ ] Issue Type = Bug  
- [ ] Summary rõ lỗi, vị trí, ảnh hưởng  
- [ ] Có Steps to Reproduce  
- [ ] Có Expected Result và Actual Result  
- [ ] Có Environment  
- [ ] Có Screenshot/minh chứng  
- [ ] Priority đã chọn và giải thích  
- [ ] Bug đã liên kết với Story  
- [ ] Đã thực hiện Bug Lifecycle  
- [ ] Đã Retest → Closed hoặc Reopened phù hợp  

---

## Thứ tự làm trên Jira (15–20 phút)

1. Tạo Project `NHOMXX_SOFTWARE_TESTING`  
2. Tạo Story (mục 4)  
3. Tạo Bug 1 → chạy lifecycle → Closed  
4. Tạo Bug 2 → Closed  
5. Tạo Bug 3 → Resolved → **Reopened** → Resolved → Closed  
6. Chụp ảnh mục 11  
7. Điền báo cáo mục 12 + checklist 13  

— HẾT (theo PDF) —
